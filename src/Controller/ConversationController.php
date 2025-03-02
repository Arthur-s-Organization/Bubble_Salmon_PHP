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


    public function getAll() // récupère toutes les conversations de l'utilisateur (nom de la conversation ou nom du destinataire suivant le type de conversation), avec le dernier message envoyé
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();

        $conversations = Conversation::sqlGetAllbyUserId((int)$tokensDatas->id);
        return json_encode($conversations);
    }


    public function show(int $conversationId) // récupère toutes les infos d'une conversation avec son nom si conversation de groupe et avec le nom du dest si conversation à deux (penser à implémenter vérif que si la conversation demandée n'appartient pas à l'utilsateur on lance une erreur)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $username = (string)$tokensDatas->username;
        $userId = (int)$tokensDatas->id;

        $conversation = Conversation::sqlGetById($conversationId, $userId);
        return json_encode($conversation);
    }


    public function getOrCreate() // créé un conversation à 1 ou à 2 suivant le RecipientId envoyé. Si elle existe déjà on se contente de la renvoyer.
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $userId = (int)$tokensDatas->id;
//        $username = (string)$tokensDatas->username;

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->RecipientId)){
            throw new ApiException("Missing required fields : RecipientId is required", 400);
        }

        $recipentId = $jsonDatasObj->RecipientId;
        $now = new \DateTime();

        //cas ou on créé une conversation avec soi même
        if ($userId == $recipentId)
        {
            if (Conversation::sqlSelfExists($userId))
            {
                $conversationId = Conversation::sqlGetSelfIdByUserId($userId);
            }
            else
            {
                $conversation = new Conversation();
                $conversation->setType(1)
                    ->setCreatedAt($now)
                    ->setUpdatedAt($now);
                $conversationId = Conversation::sqlAddSelf($conversation, $userId);
            }
        }
        // cas ou on créé une conversation à 2
        else
        {
            if (Conversation::sqlExists($userId, $recipentId)) {
                // recupération de l'id de conversation existante entre les deux users
                $conversationId = Conversation::sqlGetIdByUsersId($userId, $recipentId);
            }
            else
            {
                $conversation = new Conversation();
                $conversation->setType(2)
                    ->setCreatedAt($now)
                    ->setUpdatedAt($now);
                $conversationId = Conversation::sqlAdd($conversation, $userId, $recipentId);
            }
        }

        $conversation = Conversation::sqlGetById($conversationId, $userId);
        return json_encode($conversation);
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
            throw new ApiException("Missing required fields : Name and RecipientIds are required", 400);
        }

        if (count($jsonDatasObj->RecipientIds) < 2)
        {
            throw new ApiException("A group conversation must have at least 3 users", 400);
        }

        if (count($jsonDatasObj->RecipientIds) !== count(array_unique($jsonDatasObj->RecipientIds)))
        {
            throw new ApiException("User list provided contains duplicates", 400);
        }

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
            ->setUpdatedAt($now)
            ->setType(3);

        $conversationId = Conversation::sqlAddGroup($conversation, $recipentsIds, $userId);
        return json_encode(["status" => "success", "Message" => "Conversation successfully added", "conversationId" => $conversationId]);
    }


    public function addUser() // Ajoute un utilisateur à une conversation de groupe
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

        Conversation::sqlAddUserToGroup($userId, $conversationId);
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
        $oldSqlRepository = Conversation::getSqlImageRepositoryById($conversationId);
        $oldSqlImageName = Conversation::getSqlImageNameById($conversationId);

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
            // Suppression de l'ancienne image si il y en a une
            if (!empty($oldSqlRepository) && !empty($oldSqlImageName)) {
                if (file_exists("{$_SERVER["DOCUMENT_ROOT"]}/uploads/images/{$oldSqlRepository}/{$oldSqlImageName}")) {
                    unlink("{$_SERVER["DOCUMENT_ROOT"]}/uploads/images/{$oldSqlRepository}/{$oldSqlImageName}");
                }
            }
        }

        $oldName = Conversation::sqlGetNamebyId($conversationId);
        if (isset($jsonDatasObj->Name)) {
            $name = $jsonDatasObj->Name;
        }
        else {
            $name = $oldName;
        }

        $conversation = new Conversation();
        $conversation->setId($conversationId)
            ->setName($name)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setUpdatedAt($now);

       Conversation::sqlUpdate($conversation);
        return json_encode(["status" => "success", "Message" => "Conversation successfully Updated", "conversationId" => $conversationId]);
    }


    public function search($filter) { // récupère la liste de toutes les conversations/utilisateurs correpondant à un critère de recherche
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $userId = (int)$tokensDatas->id;

        $users = Conversation::SqlGetFileredConversations($filter, $userId);

        return json_encode($users);
    }
}