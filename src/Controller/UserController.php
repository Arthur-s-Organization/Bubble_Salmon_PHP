<?php

namespace src\Controller;

use src\Model\User;

class UserController {
    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public function add() {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            return json_encode(["code" => 1, "Message" => "POST Attendu"]);
        }

        $data = file_get_contents("php://input");
        $json = json_decode($data);


        if (empty($json)) {
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        if (!isset($json->Firstname) || !isset($json->Lastname)) {
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        $user = new User();
        $user->setFirstname($json->Firstname)
            ->setLastname($json->Lastname)
            ->setphone($json->Phone)
            ->setBirthDate(new \DateTime($json->BirthDate))
            ->setUsername($json->Username)
            ->setPassword($json->Password)
            ->setCreatedAt(new \DateTime($json->CreatedAt))
            ->setUpdatedAt(new \DateTime($json->UpdatedAt));

        $id = User::SqlAdd($user);
        return json_encode(["code" => 0, "Message" => "User ajouté avec succès", "Id" => $id], JSON_THROW_ON_ERROR);

    }
}