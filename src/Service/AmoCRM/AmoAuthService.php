<?php

namespace App\Service\AmoCRM;

use App\Kernel\Http\Response;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Grant\AuthorizationCode;
use League\OAuth2\Client\Token\AccessToken;

define('AMO_TOKEN_FILE', APP_PATH . '/var/tmp/amo_token_info.json');

class AmoAuthService
{
    private AmoCRM $provider;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function initializeToken(bool $useWebHook = false): AccessToken|string|null
    {


        $this->initializeProvider();
        $accessToken = $this->getToken();


        if ($this->isTokenValid($accessToken)) {
            $this->displayAccountInfo($accessToken);
        } elseif (!$useWebHook) {


            $accessToken = $this->handleAuthorization();
        }

        return $accessToken;
    }

    private function initializeProvider(): void
    {
        $this->provider = new AmoCRM([
            'clientId' => AMO_CLIENT_ID,
            'clientSecret' => AMO_CLIENT_SECRET,
            'redirectUri' => "https://" . APP_URL . APP_PROJECT . "/auth/callback"
        ]);


        if (isset($_GET['referer'])) {
            $this->provider->setBaseDomain($_GET['referer']);
        }


    }

    private function isTokenValid(?AccessToken $accessToken): bool
    {
        return $accessToken && !$accessToken->hasExpired();
    }

    private function handleAuthorization(): ?AccessToken
    {
        if (empty($_GET['code'])) {
            $this->showAuthButton();
            return null;
        }


        if ($_GET['state'] !== ($_SESSION['oauth2state'] ?? '')) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        }

        try {
            $accessToken = $this->provider->getAccessToken(new AuthorizationCode(), ['code' => $_GET['code']]);
            $this->saveToken($accessToken);
            $this->displayAccountInfo($accessToken);

            return $accessToken;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    private function showAuthButton(): void
    {


        $_SESSION['oauth2state'] = bin2hex(random_bytes(16));
        echo '<div>
            <script
                class="amocrm_oauth"
                charset="utf-8"
                data-client-id="' . $this->provider->getClientId() . '"
                data-title="Установить интеграцию"
                data-compact="false"
                data-state="' . $_SESSION['oauth2state'] . '"
                data-error-callback="handleOauthError"
                src="https://www.amocrm.ru/auth/button.min.js"
            ></script>
        </div>';
        echo '<script>
            handleOauthError = function(event) {
                alert(\'Ошибка: \' + event.error);
            }
        </script>';
    }

    public function callback(): Response
    {
        if ($_GET['state'] !== ($_SESSION['oauth2state'] ?? '')) {
            return new Response('Invalid state.', 400);
        }


        try {
            $this->initializeProvider();
            $this->processAuthorizationCode($_GET['code'], $_GET['referer'] ?? null);
        } catch (Exception $e) {
            return new Response('Error processing authorization: ' . $e->getMessage(), 500);
        }

        header('Location: ' . APP_PROJECT);
        exit;
    }

    /**
     * @throws AmoCRMException
     */
    private function processAuthorizationCode(string $code, ?string $referer): void
    {

        try {
            $accessToken = $this->provider->getAccessToken(new AuthorizationCode(), ['code' => $code]);
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getCode(), $e);
        }


        $this->saveToken($accessToken);
    }

    private function displayAccountInfo(AccessToken $accessToken): void
    {

        try {
            $data = $this->provider->getHttpClient()
                ->request('GET', $this->provider->urlAccount() . 'api/v2/account', [
                    'headers' => $this->provider->getHeaders($accessToken)
                ]);

            $parsedBody = json_decode($data->getBody()->getContents(), true);

            printf('Account ID: %s, Name: %s', $parsedBody['id'], $parsedBody['name']);
        } catch (GuzzleException $e) {
            var_dump($e->getMessage());
        }
    }

    private function saveToken(AccessToken $accessToken): void
    {
        $data = [
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires' => $accessToken->getExpires(),
            'baseDomain' => $this->provider->getBaseDomain(),
        ];

        file_put_contents(AMO_TOKEN_FILE, json_encode($data));
        chmod(AMO_TOKEN_FILE, 0777);
    }

    private function getToken(): ?AccessToken
    {
        if (!file_exists(AMO_TOKEN_FILE)) {
            return null;
        }

        $data = json_decode(file_get_contents(AMO_TOKEN_FILE), true);

        return $data ? new AccessToken($data) : null;
    }
}
