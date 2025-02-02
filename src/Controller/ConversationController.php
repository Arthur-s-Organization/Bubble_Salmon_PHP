<?php

namespace src\Controller;

use src\Exception\ApiException;
use src\Model\Conversation;
use src\Service\JwtService;

class ConversationController
{

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }


    public function getAll() // récupère toutes les conversations de l'utilisateur (sans le dernier message de chaque conv pour l'instant)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();

        $conversations = Conversation::SqlGetAllbyUserId((int)$tokensDatas->id);
        return json_encode($conversations);
    }


    public function show(int $id) // récupére tous les messages d'une conversation
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        JwtService::checkToken();

        $conversation = Conversation::SqlGetById($id);
        return json_encode($conversation);
    }


    public function add() // créé une nouvelle conversation (sans utilisateur pour l'instant)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        JwtService::checkToken();

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->Name)) {
            throw new ApiException("Missing required fields : Name is required", 400);
        }

//        if (!isset($jsonDatasObj->Image)) {
//            throw new ApiException("Missing required fields : Image is required", 400);
//        }

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

        $conversation = new Conversation();
        $conversation->setName($jsonDatasObj->Name)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $conversationId = Conversation::SqlAdd($conversation);
        return json_encode(["status" => "success", "Message" => "Conversation successfully added", "conversationId" => $conversationId]);

    }


    public function addUser() // Ajoute un user à une conversation
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        JwtService::checkToken();

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->UserId) || !isset($jsonDatasObj->ConversationId)) {
            throw new ApiException("Missing required fields : UserId and ConversationId are required", 400);
        }

        $userId = $jsonDatasObj->UserId;
        $conversationId = $jsonDatasObj->ConversationId;

        Conversation::SqlAddUser($userId, $conversationId);
        return json_encode(["status" => "success", "message" => "User $userId succesfuly add to conversation $conversationId"]);
    }


    public function update(int $conversationId) {
        if ($_SERVER["REQUEST_METHOD"] !== "PUT") {
            throw new ApiException("Method PUT expected", 405);
        }

        JwtService::checkToken();

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        // ici on récupére le path et le nom actuel de l'image
        $oldSqlRepository = Conversation::getSqlImageRepository($conversationId);
        $oldSqlImageName = Conversation::getSqlImageName($conversationId);

        $sqlRepository = $oldSqlRepository;
        $imageName = $oldSqlImageName;
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
            // implémenter la suppression de l'ancienne image si il y en a une
            if (file_exists("{$_SERVER["DOCUMENT_ROOT"]}/uploads/images/{$oldSqlRepository}/{$oldSqlImageName}")) {
                unlink("{$_SERVER["DOCUMENT_ROOT"]}/uploads/images/{$oldSqlRepository}/{$oldSqlImageName}");
            }
        }

        $conversation = new Conversation();
        $conversation->setId($conversationId)
            ->setName($jsonDatasObj->Name)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setUpdatedAt($now);

       Conversation::SqlUpdate($conversation);
        return json_encode(["status" => "success", "Message" => "Conversation successfully Updated", "conversationId" => $conversationId]);
    }
}