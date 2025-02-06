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


    public function getAll() // récupère toutes les conversations de l'utilisateur (nom de la conv ou nom du desti suivant le type de conv) (avec le dernier message )
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();

        $conversations = Conversation::SqlGetAllbyUserId((int)$tokensDatas->id, $tokensDatas->username);
        return json_encode($conversations);
    }

    public function getOrCreateConversation(int $recipentId)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $userId = (int)$tokensDatas->id;
        $username = (string)$tokensDatas->username;
        $now = new \DateTime();

        if (Conversation::exists($userId, $recipentId)) {
            // recup l'id de conversation existante entre les deux users
            $conversationId = Conversation::SqlGetIdByUsersId($userId, $recipentId);
        }
        else {
            $conversation = new Conversation();
            $conversation->setCreatedAt($now)
                ->setUpdatedAt($now);
            $conversationId = Conversation::SqlAdd($conversation, $userId, $recipentId);
        }

        $conversation = Conversation::SqlGetById($conversationId, $username, $userId);
        return json_encode($conversation);
    }


    public function show(int $conversationId) // récupère toutes les infos d'une conversation avec son nom si conv de groupe et avec le nom du dest si conversation à deux (penser à implémenter vérif que si la conversation demandée n'appartient pas à l'utilsateur on lance une erreur)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $username = (string)$tokensDatas->username;
        $userId = (int)$tokensDatas->id;

        $conversation = Conversation::SqlGetById($conversationId, $username, $userId);
        return json_encode($conversation);
    }


//    public function addold() // créé une nouvelle conversation (sans utilisateur pour l'instant)
//    {
//        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
//            throw new ApiException("Method POST expected", 405);
//        }
//
//        JwtService::checkToken();
//
//        $jsonDatasStr = file_get_contents("php://input");
//        $jsonDatasObj = json_decode($jsonDatasStr);
//
//        if (empty($jsonDatasObj)) {
//            throw new ApiException("No data provided in the request body", 400);
//        }
//
//        if (!isset($jsonDatasObj->Name)) {
//            throw new ApiException("Missing required fields : Name is required", 400);
//        }
//
////        if (!isset($jsonDatasObj->Image)) {
////            throw new ApiException("Missing required fields : Image is required", 400);
////        }
//
//        $sqlRepository = null;
//        $imageName = null;
//        $now = new \DateTime();
//
//        if (isset($jsonDatasObj->Image)) {
//            $imageName = uniqid() . ".jpg";
//            //Fabriquer le répertoire d'accueil
//            $dateNow = new \DateTime();
//            $sqlRepository = $now->format('Y/m');
//            $repository = './uploads/images/' . $now->format('Y/m');
//            if (!is_dir($repository)) {
//                mkdir($repository, 0777, true);
//            }
//            //Fabriquer l'image
//            $ifp = fopen($repository . "/" . $imageName, "wb");
//            fwrite($ifp, base64_decode($jsonDatasObj->Image));
//            fclose($ifp);
//        }
//
//        $conversation = new Conversation();
//        $conversation->setName($jsonDatasObj->Name)
//            ->setImageRepository($sqlRepository)
//            ->setImageFileName($imageName)
//            ->setCreatedAt($now)
//            ->setUpdatedAt($now);
//
//        $conversationId = Conversation::SqlAdd($conversation);
//        return json_encode(["status" => "success", "Message" => "Conversation successfully added", "conversationId" => $conversationId]);
//    }

    public function add() // créé une nouvelle conversation à deux (utilisateur connecté plus un autre)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $userId = (int)$tokensDatas->id;

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->RecipientId)){
            throw new ApiException("Missing required fields : RecipientId is required", 400);
        }

//        if (!isset($jsonDatasObj->Image)) {
//            throw new ApiException("Missing required fields : Image is required", 400);
//        }
        $recipentId = $jsonDatasObj->RecipientId;

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

        $conversationId = Conversation::SqlAdd($conversation, $userId, $recipentId);
        return json_encode(["status" => "success", "Message" => "Conversation successfully added", "conversationId" => $conversationId]);
    }


    public function addGroup() // créé une nouvelle conversation à plusieurs (utilisateur connecté plus les autres)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $userId = (int)$tokensDatas->id;

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->Name) || !isset($jsonDatasObj->RecipientIds)){
            throw new ApiException("Missing required fields : RecipientIds is required", 400);
        }

//        if (!isset($jsonDatasObj->Image)) {
//            throw new ApiException("Missing required fields : Image is required", 400);
//        }
        $recipentsIds = $jsonDatasObj->RecipientIds;

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

        $conversationId = Conversation::SqlAddGroup($conversation, $recipentsIds, $userId);
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
            if (!empty($oldSqlRepository) && !empty($oldSqlImageName)) {
                if (file_exists("{$_SERVER["DOCUMENT_ROOT"]}/uploads/images/{$oldSqlRepository}/{$oldSqlImageName}")) {
                    unlink("{$_SERVER["DOCUMENT_ROOT"]}/uploads/images/{$oldSqlRepository}/{$oldSqlImageName}");
                }
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


    public function search($filter) { // récupère la liste de toutes les conversations/utilisateurs correpondant à un critère de recherche
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        JwtService::checkToken();

        $users = Conversation::SqlGetFileredUsers($filter);

        return json_encode($users);
    }
}