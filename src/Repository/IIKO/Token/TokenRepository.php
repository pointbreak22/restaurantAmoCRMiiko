<?php

namespace App\Repository\IIKO\Token;

use App\Repository\IIKO\MainRepository;

class TokenRepository extends MainRepository
{
    private string $method = '/api/1/access_token';


    public function get(): array
    {
        $params = ['apiLogin' => IIKO_API_KEY];
        return $this->request($this->method, $params) ?? [];
    }


}