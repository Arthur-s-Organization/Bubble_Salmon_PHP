<?php

namespace src\Controller;

use src\Exception\ApiException;
use src\Model\Conversation;
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

        if (!isset($jsonDatasObj->ConversationId)){
            throw new ApiException("Missing required fields : ConversationId is required", 400);
        }

        if (!isset($jsonDatasObj->Text) and (!isset($jsonDatasObj->Image))){
            throw new ApiException("Missing required fields : Text or Image is required", 400);
        }

        $sqlRepository = null;
        $imageName = null;
        $now = new \DateTime();

        if (isset($jsonDatasObj->Image)) {
            $imageName = uniqid() . ".jpg";
            //Fabriquer le répertoire d'accueil
            $dateNow = new \DateTime();
            $sqlRepository = $now->format('Y/m');
            $repository = './uploads/images/' . $now->format('Y/m');
            if (!is_dir($repository)) {
                mkdir($repository, 0777, true);
            }
            //Fabriquer l'image
            $ifp = fopen($repository . "/" . $imageName, "wb");
            fwrite($ifp, base64_decode($jsonDatasObj->Image));
            fclose($ifp);
        }

        if(!isset($jsonDatasObj->Text)){
            $content = null;
        }
        else {
            $content = $jsonDatasObj->Text;
        }

        $message = new Message();
        $message->setUserId((int)$tokensDatas->id)
            ->setConversationId($jsonDatasObj->ConversationId)
            ->setText($content)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $id = Message::SqlAdd($message);
        return json_encode(["code" => 0, "Message" => "Message ajouté avec succès", "Id" => $id]);
    }

    public function getAll(int $conversationId) // récupére tous les messages d'une conversation avec son id
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        JwtService::checkToken();

        $messages = Message::SqlGetAllByConversationId($conversationId);
        return json_encode($messages);
    }
}