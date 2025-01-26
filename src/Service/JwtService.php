<?php

namespace src\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use src\Exception\ApiException;

class JwtService
{
    public static string $secretKey = "cesi";

    public static function createToken(array $datas): string
    {
        $issuedAt = new \DateTimeImmutable();

        $expire = $issuedAt->modify('+16 minutes')->getTimestamp();
        $serverName = "cesi.local";

        $data = [
            "iat" => $issuedAt->getTimestamp(),
            "iss" => $serverName,
            "nbf" => $issuedAt->getTimestamp(),
            "exp" => $expire,
            "data" =>  CryptService::encrypt(json_encode($datas))//$datas
        ];

        $jwt = JWT::encode($data, self::$secretKey, 'HS256');

        return $jwt;
    }


    public static function checkToken()
    {
        if (!preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            throw new ApiException("Token not found in the request", 401);
        }

        $jwt = $matches[1];
        if (!$jwt) {
            throw new ApiException("No token could be extracted from the authorization header.", 401);
        }

        try
        {
            $token = JWT::decode($jwt, new Key(self::$secretKey, 'HS256'));
        }
        catch (\Exception $e) // a gérer le try catach ici
        {
            throw new ApiException("The token data is not compatible : {$e->getMessage()}", 401);
        }

        $now = new \DateTimeImmutable();
        $serverName = "cesi.local";

        if ($token->iss !== $serverName || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp()) {
            throw new ApiException("The token data is not compatible", 401);
        }

        return json_decode(CryptService::decrypt($token->data)); //On récupère le champs datas du payload du JWT pour pouvoir par exemple comparer les roles avec ceux attendus;
    }


}