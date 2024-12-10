<?php

declare(strict_types=1);

namespace App\Kernel\Http;

class Response
{
    public function send(mixed $content, int $status = 200, array $headers = []): void
    {
        $this->setHeaders($headers);
        $this->setStatus($status);
        $this->setContent($content);
    }

    private function setHeaders(array $headers): void
    {
        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                header("$name: $value");
            }
        }
    }

    private function setStatus(int $status): void
    {
        http_response_code($status);
    }

    private function setContent(mixed $content): void
    {
        echo $content;
    }
}