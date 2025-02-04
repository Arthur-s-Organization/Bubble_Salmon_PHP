<?php

namespace src\Model;

use src\Exception\ApiException;
use src\Service\JwtService;
use JsonSerializable;

class Conversation implements JsonSerializable {
    private ?int $id = null;
    private ?string $name = null;
    private ?string $imageRepository = null;
    private ?string $imageFileName = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Conversation
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): Conversation
    {
        $this->name = $name;
        return $this;
    }

    public function getImageRepository(): ?string
    {
        return $this->imageRepository;
    }

    public function setImageRepository(?string $imageRepository): Conversation
    {
        $this->imageRepository = $imageRepository;
        return $this;
    }

    public function getImageFileName(): ?string
    {
        return $this->imageFileName;
    }

    public function setImageFileName(?string $imageFileName): Conversation
    {
        $this->imageFileName = $imageFileName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): Conversation
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): Conversation
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

//    public function add() {
//        if ($_SERVER["REQUEST_METHOD"] != "POST") {
//            header("HTTP/1.1 405 Method Not Allowed");
//            return json_encode(["code" => 1, "Message" => "POST Attendu"]);
//        }
//
//        $data = file_get_contents("php://input");
//        $json = json_decode($data);
//
//
//        if (empty($json)) {
//            header("HTTP/1.1 400 Bad Request");
//            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
//        }
//
//        if (!isset($json->Name) || !isset($json->Description)) {
//            header("HTTP/1.1 400 Bad Request");
//            return json_encode(["code" => 1, "Message" => "Il faut des données"]);
//        }
//    }

    public static function SqlGetAllbyUserId(int $userId) {
        try {
            $requete = BDD::getInstance()->prepare('SELECT * FROM conversations c JOIN conversations_users cu ON c.id = cu.conversation_id WHERE cu.user_id = :userId');
            $requete->bindValue(':userId', $userId);
            $requete->execute();

            $conversationsSql = $requete->fetchAll(\PDO::FETCH_ASSOC);
            $conversationsObject = [];
            foreach ($conversationsSql as $conversationSql) {
                $conversation = new Conversation();
                $conversation->setName($conversationSql["name"])
                    ->setId($conversationSql["id"])
                    ->setImageRepository($conversationSql["image_repository"])
                    ->setImageFileName($conversationSql["image_file_name"])
                    ->setcreatedAt(new \DateTime($conversationSql["created_at"]))
                    ->setupdatedAt(new \DateTime($conversationSql["updated_at"]));
                $conversationsObject[] = $conversation;
            }
            return $conversationsObject;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }

    }

    public static function SqlGetById(int $conversationId, string $username) {
        try {
//            $requete = BDD::getInstance()->prepare('SELECT * FROM conversations WHERE id = :id');
            $requete = BDD::getInstance()->prepare("
                SELECT 
                DISTINCT
                    c.id,
                    CASE 
                        WHEN LENGTH(c.name) = 0 THEN u.username
                        ELSE c.name
                    END as conversation_name,
                    c.image_repository,
                    c.image_file_name,
                    c.created_at,
                    c.updated_at
                FROM conversations c
                JOIN conversations_users cu on c.id = cu.conversation_id
                JOIN users u on cu.user_id = u.id
                WHERE c.id = :id and u.username not like :username");
            $requete->bindValue(':id', $conversationId);
            $requete->bindValue(':username', $username);
            $requete->execute();

            $sqlConversation = $requete->fetch(\PDO::FETCH_ASSOC);
            if ($sqlConversation !== false) {
                $conversation = new Conversation();
                $conversation->setId($sqlConversation["id"])
                    ->setName($sqlConversation["conversation_name"])
                    ->setImageRepository($sqlConversation["image_repository"])
                    ->setImageFileName($sqlConversation["image_file_name"])
                    ->setcreatedAt(new \DateTime($sqlConversation["created_at"]))
                    ->setupdatedAt(new \DateTime($sqlConversation["updated_at"]));

                return $conversation;
            }
            return null;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlAdd(Conversation $conversation) {

        try {
            $requete = BDD::getInstance()->prepare("INSERT INTO conversations (name, image_repository, image_file_name ,created_at, updated_at) VALUES (:name, :image_repository, :image_file_name, :createdAt, :updatedAt)");

            $requete->bindValue(':name', $conversation->getName());
            $requete->bindValue(':image_repository', $conversation->getImageRepository());
            $requete->bindValue(':image_file_name', $conversation->getImageFileName());
            $requete->bindValue(':createdAt', $conversation->getCreatedAt()?->format('Y-m-d H:i:s'));
            $requete->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));

            $requete->execute();
            return BDD::getInstance()->lastInsertId();
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlAddUser(int $userId, int $conversationId) {

        try {
            $db = BDD::getInstance();

            // Vérifie si la conversation existe
            $conversationCheck = $db->prepare('SELECT COUNT(*) FROM conversations WHERE id = :conversationId');
            $conversationCheck->bindValue(':conversationId', $conversationId);
            $conversationCheck->execute();
            if ($conversationCheck->fetchColumn() === 0) {
                throw new ApiException("ConversationId {$conversationId} doesn''t exist", 404);
            }

            // Vérifie si l'utilisateur existe
            $userCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :userId');
            $userCheck->bindValue(':userId', $userId);
            $userCheck->execute();
            if ($userCheck->fetchColumn() === 0) {
                throw new ApiException("UserId {$userId} doesn''t exist", 404);
            }

            // Vérifie si une association existe déjà
            $associationCheck = $db->prepare('SELECT COUNT(*) FROM conversations_users WHERE conversation_id = :conversationId AND user_id = :userId');
            $associationCheck->bindValue(':conversationId', $conversationId);
            $associationCheck->bindValue(':userId', $userId);
            $associationCheck->execute();
            if ($associationCheck->fetchColumn() > 0) {
                throw new ApiException("User {$userId} already belongs to conversation {$conversationId}", 409);
            }

            $requete = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $requete->bindValue(':conversationId', $conversationId);
            $requete->bindValue(':userId', $userId);
            $requete->execute();
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }

    }

    public static function SqlUpdate(Conversation $conversation) {
        try {
            $requete = BDD::getInstance()->prepare("UPDATE conversations SET name =:name, image_repository = :image_repository,image_file_name = :image_file_name, updated_at = :updatedAt WHERE id = :id");

            $requete->bindValue(':name', $conversation->getName());
            $requete->bindValue(':image_repository', $conversation->getImageRepository());
            $requete->bindValue(':image_file_name', $conversation->getImageFileName());
            $requete->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $requete->bindValue(':id', $conversation->getId());

            $requete->execute();
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function getSqlImageRepository($conversationId) {
        try {
            $requete = BDD::getInstance()->prepare("SELECT image_repository FROM conversations WHERE id = :conversationId");
            $requete->bindValue(':conversationId', $conversationId);
            $requete->execute();

            $result = $requete->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['image_repository'] : null;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function getSqlImageName($conversationId) {
        try {
            $requete = BDD::getInstance()->prepare("SELECT image_file_name FROM conversations WHERE id = :conversationId");
            $requete->bindValue(':conversationId', $conversationId);
            $requete->execute();

            $result = $requete->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['image_file_name'] : null;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlGetFileredConversations(string $filter) // pb ! on renvoie juste les conversations et pas les noms des users
    {
        try
        {
            $requete = BDD::getInstance()->prepare("SELECT * FROM users WHERE username LIKE :filter");
            $requete->bindValue(':filter', "%{$filter}%");
            $requete->execute();

            $sqlConversations = $requete->fetchAll(\PDO::FETCH_ASSOC);
            if ($sqlConversations !== false)
            {
                $conversations = [];
                foreach ($sqlConversations as $sqlConversation)
                {
                    $conversation = new Conversation();
                    $conversation->setId($sqlConversation['id'])
                        ->setName($sqlConversation['name'])
                        ->setImageRepository($sqlConversation["image_repository"])
                        ->setImageFileName($sqlConversation["image_file_name"])
                        ->setCreatedAt(new \DateTime($sqlConversation["created_at"]))
                        ->setupdatedAt(new \DateTime($sqlConversation["updated_at"]));
                    $conversations[] = $conversation;
                }
                return $conversations;
            }
        }
        catch (\PDOException $e)
        {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "imageRepository" => $this->getImageRepository(),
            "imageFileName" => $this->getImageFileName(),
            "createdAt" => $this->getCreatedAt()?->format("Y-m-d H:i:s"),
            "updatedAt" => $this->getUpdatedAt()?->format("Y-m-d H:i:s")
        ];
    }

}