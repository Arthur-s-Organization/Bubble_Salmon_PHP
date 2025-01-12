<?php

namespace src\Controller;

use src\Model\Conversation;
use src\Service\JwtService;

class ConversationController {

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public function getAll() // récupère toutes les conversations de l'utilisateur
    {
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            header("HTTP/1.1 405 Method Not Allowed");
            return json_encode(["code" => 1, "Message" => "Get Attendu"]);
        }
        $result = JwtService::checkToken();

        if($result["code"] == "1")
        {
            return json_encode($result);
        }

        $conversations = Conversation::SqlGetAllbyUserId((int)$result["data"]->id);
        return json_encode($conversations);
    }

    public function add() // créé une nouvelle conversation
    {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            return json_encode(["code" => 1, "Message" => "POST Attendu"]);
        }

        $result = JwtService::checkToken();
        if($result["code"] == "1")
        {
            return json_encode($result);
        }

        $data = file_get_contents("php://input");
        $json = json_decode($data);


        if (empty($json)) {
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        if (!isset($json->Name)) {
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        $sqlRepository = null;
        $imageName = null;


        $conversation = new Conversation();
        $conversation->setName($json->Name)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setCreatedAt(new \DateTime($json->CreatedAt))
            ->setUpdatedAt(new \DateTime($json->UpdatedAt));

        $id = Conversation::SqlAdd($conversation);
        return json_encode(["code" => 0, "Message" => "Conversation ajoutée avec succès", "Id" => $id]);

    }

    public function addUser() // ajoute un user à une conversation
    {
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            header("HTTP/1.1 405 Method Not Allowed");
            return json_encode(["code" => 1, "Message" => "POST Attendu"]);
        }

        $result = JwtService::checkToken();
        if($result["code"] == "1")
        {
            return json_encode($result);
        }

        $data = file_get_contents("php://input");
        $json = json_decode($data);


        if (empty($json)) {
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        if (!isset($json->UserId) || !isset($json->ConversationId) ) {
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        $userId = $json->UserId;
        $conversationId = $json->ConversationId;

        $id = Conversation::SqlAddUser($userId, $conversationId);
        return json_encode(["code" => 0, "Message" => "Utilisateur ajouté avec succès à la conversation", "Id" => $id]);
    }

}