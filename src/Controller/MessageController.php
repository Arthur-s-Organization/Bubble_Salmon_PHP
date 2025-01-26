<?php

namespace src\Controller;

use src\Exception\ApiException;
use src\Model\Message;
use src\Service\JwtService;

class MessageController {

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }


    public function add() //ajoute un message à une conversation
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        $tokensDatas = JwtService::checkToken();

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);


        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->ConversationId) || !isset($jsonDatasObj->Text)){
            throw new ApiException("Missing required fields : ConversationId and Text are required", 400);
        }

        $sqlRepository = null;
        $imageName = null;

        $now = new \DateTime();

        $message = new Message();
        $message->setUserId((int)$tokensDatas->id)
            ->setConversationId($jsonDatasObj->ConversationId)
            ->setText($jsonDatasObj->Text)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $id = Message::SqlAdd($message);
        return json_encode(["code" => 0, "Message" => "Message ajouté avec succès", "Id" => $id]);
    }
}