<?php

namespace src\Controller;

use src\Model\Message;
use src\Service\JwtService;

class MessageController {

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public function add() //ajoute un message à une conversation
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

        if (!isset($json->ConversationId) ||!isset($json->Text)){
            header("HTTP/1.1 400 Bad Request");
            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
        }

        $sqlRepository = null;
        $imageName = null;

        $message = new Message();
        $message->setUserId((int)$result["data"]->id)
            ->setConversationId($json->ConversationId)
            ->setText($json->Text)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setCreatedAt(new \DateTime($json->CreatedAt))
            ->setUpdatedAt(new \DateTime($json->UpdatedAt));

        $id = Message::SqlAdd($message);
        return json_encode(["code" => 0, "Message" => "Message ajouté avec succès", "Id" => $id]);
    }
}