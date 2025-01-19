<?php

namespace src\Controller;

use src\Exception\ApiException;
use src\Model\User;
use src\Service\JwtService;

class UserController {
    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public function register() {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
//            header("HTTP/1.1 405 Method Not Allowed");
//            return json_encode(["code" => 1, "Message" => "POST Attendu"]);
            throw new ApiException("POST Attendu", 405);
        }

        $data = file_get_contents("php://input");
        $json = json_decode($data);


        if (empty($json)) {
//            header("HTTP/1.1 400 Bad Request");
//            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
            throw new ApiException("Il faut des données", 400);
        }

        if (!isset($json->Firstname) || !isset($json->Lastname)) {
//            header("HTTP/1.1 400 Bad Request");
//            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
            throw new ApiException("Il faut des donnéeeees", 400);
        }

        $user = new User();
        $hashPassword = password_hash($json->Password, PASSWORD_BCRYPT, ['cost' => 12]);
        $user->setFirstname($json->Firstname)
            ->setLastname($json->Lastname)
            ->setphone($json->Phone)
            ->setBirthDate(new \DateTime($json->BirthDate))
            ->setUsername($json->Username)
            ->setPassword($hashPassword)
            ->setCreatedAt(new \DateTime($json->CreatedAt))
            ->setUpdatedAt(new \DateTime($json->UpdatedAt));

        $id = User::SqlAdd($user);
        return json_encode(["code" => 0, "Message" => "User ajouté avec succès", "Id" => $id], JSON_THROW_ON_ERROR);
    }

    public function login()
    {
        header("Content-type: application/json; charset=utf-8");

        if($_SERVER["REQUEST_METHOD"] != "POST") {
//            header("HTTP/1.1 405 Method Not Allowed");
//            return json_encode(
//                [
//                    "status" => "error",
//                    "message" => "Post Attendu"]
//            );
            throw new ApiException("Post Attendu", 405);
        }

        // Récupération du Body en String
        $data  = file_get_contents("php://input");
        //Conversion du String en JSON
        $json = json_decode($data);

        if(empty($json)) {
//            header("HTTP/1.1 400 Bad Request");
//            return json_encode(
//                [
//                    "status" => "error",
//                    "message" => "Il faut des données"]
//            );
            throw new ApiException("Il faut des données", 400);
        }

        if(!isset($json->Username) || !isset($json->Password) ) {
//            header("HTTP/1.1 400 Bad Request");
//            return json_encode(
//                [
//                    "status" => "error",
//                    "message" => "Il faut des données"]
//            );
            throw new ApiException("Il faut des données", 400);
        }

        $user = User::SqlGetByUsername($json->Username);
        if($user == null) {
//            header("HTTP/1.1 403 Forbiden");
//            return json_encode(
//                [
//                    "status" => "error",
//                    "message" => "Username inconnu dans notre système"]
//            );
            throw new ApiException("Username inconnu dans notre système", 403);
        }

        // Comparer le mot de passe
        if(!password_verify($json->Password, $user->getPassword())){
//            header("HTTP/1.1 403 Forbiden");
//            return json_encode(
//                [
//                    "status" => "error",
//                    "message" => "Mot de passe incorrect"]
//            );
            throw new ApiException("Mot de passe incorrect", 403);
        }

        // Retourne le JWT
        return JwtService::createToken([
            "id" => $user->getId(),
            "username" => $user->getUsername()
        ]);
    }


    public function getAll() // récupère la liste de tous les utilisateurs
    {
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
//            header("HTTP/1.1 405 Method Not Allowed");
//            return json_encode(["code" => 1, "Message" => "Get Attendu"]);
            throw new ApiException("Get Attendu", 405);
        }

        $result = JwtService::checkToken();
        if($result["code"] == "1")
        {
            return json_encode($result);
        }

        $users = User::SqlGetAll();
        return json_encode($users);

    }

    public function show() // récupère la "fiche" de l'utilisateur connecté
    {
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
//            header("HTTP/1.1 405 Method Not Allowed");
//            return json_encode(["code" => 1, "Message" => "Get Attendu"]);
            throw new ApiException("Get Attendu", 405);
        }

        $result = JwtService::checkToken();
        if($result["code"] == "1")
        {
            return json_encode($result);
        }

        $userId = (int)$result["data"]->id;
        $user = User::SqlGetById($userId);

        if($user == null) {
//            header("HTTP/1.1 403 Forbiden");
//            return json_encode(
//                [
//                    "status" => "error",
//                    "message" => "Username inconnu dans notre système"]
//            );
            throw new ApiException("Username inconnu dans notre système", 403);
        }

        return json_encode($user);
    }
}