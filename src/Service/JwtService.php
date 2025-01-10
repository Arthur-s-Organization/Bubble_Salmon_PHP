<?php

namespace src\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    public static string $secretKey = "cesi";

    public static function createToken(array $datas): string
    {
        $issuedAt = new \DateTimeImmutable();

        $expire = $issuedAt->modify('+6 minutes')->getTimestamp();
        $serverName = "cesi.local";

        $data = [
            "iat" => $issuedAt->getTimestamp(),
            "iss" => $serverName,
            "nbf" => $issuedAt->getTimestamp(),
            "exp" => $expire,
            "data" => CryptService::encrypt(json_encode($datas))
        ];

        $jwt = JWT::encode($data, self::$secretKey, 'HS256');

        return $jwt;
    }


    public static function checkToken(): array
    {
        if (!preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $result = ["code" => 1, "body" => "Token non trouvé dans la requête"];
            return $result;
        }

        $jwt = $matches[1];
        if (!$jwt) {
            $result = ["code" => 1, "body" => "Aucun jeton n'a pu être extrait de l'en-tête d'autorisation."];
            return $result;
        }

        try
        {
            $token = JWT::decode($jwt, new Key(self::$secretKey, 'HS256'));
        }
        catch (\Exception $e)
        {
            $result = ["code" => 1, "body" => "Les données du jeton ne sont pas compatibles : {$e->getMessage()}"];
            return $result;
        }

        $now = new \DateTimeImmutable();
        $serverName = "cesi.local";

        if ($token->iss !== $serverName || $token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp()) {
            $result = ["code" => 1, "body" => "Les données du jeton ne sont pas compatibles"];
            return $result;
        }

        $result = [
            "code" => 0,
            "body" => "Token OK",
            "data" => json_decode(CryptService::decrypt($token->data)) //On récupère le champs datas du payload du JWT pour pouvoir par exemple comparer les roles avec ceux attendus
        ];
        return $result;
    }


}