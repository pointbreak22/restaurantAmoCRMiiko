<?php

namespace App\Service\AmoCRM;

use App\Kernel\Http\Response;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Grant\AuthorizationCode;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Random\RandomException;

define('AMO_TOKEN_FILE', APP_PATH . '/var/tmp/amo_token_info.json');

include_once APP_PATH . '/vendor/autoload.php';
include_once APP_PATH . '/src/Service/AmoCRM/AmoCRM.php';

class AmoAuthService
{
    private AmoCRM $provider;

    /**
     * @throws Exception
     */
    function __construct()
    {
        // Запускаем сессию один раз
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        //$this->initializeProvider();
    }

    /**
     * Инициализация провайдера
     * @throws RandomException
     */
    public function initializeToken(bool $useWebHook = false): AccessToken|string|null
    {

        // Создаем провайдера для взаимодействия с API
        $this->provider = new AmoCRM([
            'clientId' => AMO_CLIENT_ID,
            'clientSecret' => AMO_CLIENT_SECRET,
            //  'redirectUri' =>    AMO_REDIRECT_URI,
            'redirectUri' => "https://" . HOST_SERVER . "/auth/callback"

        ]);

        if (isset($_GET['referer'])) {
            $this->provider->setBaseDomain($_GET['referer']);
        }

        // Получаем токен
        $accessToken = $this->getToken();


        // Если токен действителен, показываем информацию об аккаунте
        if ($this->isTokenValid($accessToken)) {
            $this->displayAccountInfo($accessToken);
        } else {
            // Если токен не действителен, начинаем процесс авторизации
            if ($useWebHook) {
                return null;
            } else {
                $accessToken = $this->handleAuthorization();
            }
        }
        return $accessToken;
    }

    /**
     * Проверка валидности токена (существует, не истек)
     */
    private function isTokenValid(?AccessToken $accessToken): bool
    {
        return $accessToken && !$accessToken->hasExpired();
    }

    /**
     * Обрабатываем авторизацию пользователя
     * @throws RandomException
     */
    private function handleAuthorization(): string|null
    {


        // Если в запросе нет кода авторизации, показываем кнопку
        if (!isset($_GET['code'])) {
            $this->showAuthButton();
            return null;
        }

        // Проверка состояния
        if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        }

        try {
            // Получаем токен доступа с использованием кода авторизации
            $accessToken = $this->provider->getAccessToken(new AuthorizationCode(), [
                'code' => $_GET['code'],
            ]);

            if (!$accessToken->hasExpired()) {
                $this->saveToken([
                    'accessToken' => $accessToken->getToken(),
                    'refreshToken' => $accessToken->getRefreshToken(),
                    'expires' => $accessToken->getExpires(),
                    'baseDomain' => $this->provider->getBaseDomain(),
                ]);
            }

            $this->displayAccountInfo($accessToken);

            return $accessToken;

        } catch (Exception $e) {
            die((string)$e);
        }
    }

    /**
     * Показываем кнопку для авторизации
     * @throws RandomException
     */
    private function showAuthButton(): void
    {

        // Генерация уникального состояния для защиты от CSRF атак
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

    /**
     * Обработчик callback (callback метод)
     */
    public function callback(): Response
    {


        // Получаем параметры из запроса
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;
        $referer = $_GET['referer'] ?? null;


        // Проверка наличия необходимых параметров
        if (!$code || !$state) {
            return new Response('Invalid request: missing code or state.', 400);
        }

        // Проверка состояния
        if ($state !== $_SESSION['oauth2state']) {
            return new Response('Invalid state.', 400);
        }

        // Обработка получения токена
        try {

            $this->processAuthorizationCode($code, $referer);

        } catch (Exception $e) {

            return new Response('Error processing authorization: ' . $e->getMessage(), 500);
        }


        header('Location: /');
        exit;
    }

    /**
     * @throws IdentityProviderException
     */
    private function processAuthorizationCode(string $code, ?string $referer): void
    {

        $provider = $this->initializeProvider2($referer);


        // Получаем токен с использованием авторизационного кода
        $accessToken = $provider->getAccessToken(new AuthorizationCode(), ['code' => $code]);


        // Сохранение токена
        $this->saveToken([
            'accessToken' => $accessToken->getToken(),
            'refreshToken' => $accessToken->getRefreshToken(),
            'expires' => $accessToken->getExpires(),
            'baseDomain' => $referer,
        ]);
    }

    private function initializeProvider2(?string $referer): AmoCRM
    {
        $provider = new AmoCRM([
            'clientId' => AMO_CLIENT_ID,
            'clientSecret' => AMO_CLIENT_SECRET,
            //   'redirectUri' => AMO_REDIRECT_URI,
            'redirectUri' => "https://" . HOST_SERVER . "/auth/callback"
        ]);
        // dd($provider);

        if ($referer) {
            $provider->setBaseDomain($referer);
        }

        return $provider;
    }

    /**
     * Отображаем информацию о аккаунте
     *
     * @param AccessToken $accessToken
     */
    private function displayAccountInfo(AccessToken $accessToken): void
    {
        try {
            $data = $this->provider->getHttpClient()
                ->request('GET', $this->provider->urlAccount() . 'api/v2/account', [
                    'headers' => $this->provider->getHeaders($accessToken)
                ]);


            $parsedBody = json_decode($data->getBody()->getContents(), true);

            //  dd($accessToken);

            printf('Вы успешно авторизированны в AmoCRM, ID аккаунта - %s, название - %s', $parsedBody['id'], $parsedBody['name']);
        } catch (GuzzleException $e) {
            var_dump((string)$e);
        }
    }

    /**
     * Сохранить токен в файл
     *
     * @param array $accessToken
     */
    private function saveToken(array $accessToken): void
    {
        if (
            isset($accessToken['accessToken']) && isset($accessToken['refreshToken']) && isset($accessToken['expires']) && isset($accessToken['baseDomain'])
        ) {
            $data = [
                'accessToken' => $accessToken['accessToken'],
                'expires' => $accessToken['expires'],
                'refreshToken' => $accessToken['refreshToken'],
                'baseDomain' => $accessToken['baseDomain'],
            ];

            file_put_contents(AMO_TOKEN_FILE, json_encode($data));
            chmod(AMO_TOKEN_FILE, 0777);  // Устанавливаем права для файла
        } else {
            exit('Invalid access token ' . var_export($accessToken, true));
        }
    }

    /**
     * Получить токен из файла
     *
     * @return AccessToken|null
     */
    private function getToken(): ?AccessToken
    {
        if (!file_exists(AMO_TOKEN_FILE)) {
            return null;  // Токен не найден
        }

        $accessToken = json_decode(file_get_contents(AMO_TOKEN_FILE), true);

        if (
            isset($accessToken['accessToken']) && isset($accessToken['refreshToken']) && isset($accessToken['expires']) && isset($accessToken['baseDomain'])
        ) {
            return new AccessToken([
                'access_token' => $accessToken['accessToken'],
                'refresh_token' => $accessToken['refreshToken'],
                'expires' => $accessToken['expires'],
                'baseDomain' => $accessToken['baseDomain'],
            ]);
        } else {
            return null;  // Некорректный токен
        }
    }
}
