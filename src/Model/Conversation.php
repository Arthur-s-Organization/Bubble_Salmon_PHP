<?php

namespace src\Model;

use src\Service\JwtService;
use JsonSerializable;

class Conversation implements JsonSerializable {
    private ?int $id = null;
    private ?string $name = null;
    private ?string $imageRepository = null;
    private ?string $imageFileName = null;
    private ?\DateTime $createdAt;
    private ?\DateTime $updatedAt;

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
        $requete = BDD::getInstance()->prepare('SELECT * FROM conversations c JOIN conversations_users cu ON c.id = cu.conversation_id WHERE cu.user_id = :userId');
        $requete->bindValue(':userId', $userId);
        $requete->execute();

        $conversationsSql = $requete->fetchAll(\PDO::FETCH_ASSOC);
        $conversationsObject = [];
        foreach ($conversationsSql as $conversationSql) {
            $conversation = new Conversation();
            $conversation->setName($conversationSql["name"])
                ->setId($conversationSql["id"]);
            $conversationsObject[] = $conversation;
        }
        return $conversationsObject;
    }

    public static function SqlGetById(int $id) {
        $requete = BDD::getInstance()->prepare('SELECT * FROM conversations WHERE id = :id');
        $requete->bindValue(':id', $id);
        $requete->execute();
        $sqlConversation = $requete->fetch(\PDO::FETCH_ASSOC);

        if ($sqlConversation != null) {
            $conversation = new Conversation();
            $conversation->setId($sqlConversation['id'])
                ->setName($sqlConversation['name']);

            return $conversation;
        }

        return null;
    }

    public static function SqlAdd(Conversation $conversation) {

        try {
            $requete = BDD::getInstance()->prepare("INSERT INTO conversations (name) VALUES (:name)");

            $requete->bindValue(':name', $conversation->getName());

            $requete->execute();
            return BDD::getInstance()->lastInsertId();
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    public static function SqlAddUser(User $user, int $conversationId) {
        $db = BDD::getInstance();


        // Vérifie si la conversation existe
        $conversationCheck = $db->prepare('SELECT COUNT(*) FROM conversations WHERE id = :conversationId');
        $conversationCheck->bindValue(':conversationId', $conversationId);
        $conversationCheck->execute();
        if ($conversationCheck->fetchColumn() == 0) {
            return false; // La conversation n'existe pas
        }

        // Vérifie si l'utilisateur existe
        $userCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :userId');
        $userCheck->bindValue(':userId', $user->getId());
        $userCheck->execute();
        if ($userCheck->fetchColumn() == 0) {
            return false; // L'utilisateur n'existe pas
        }

        // Vérifie si une association existe déjà
        $associationCheck = $db->prepare('SELECT COUNT(*) FROM conversations_users WHERE conversation_id = :conversationId AND user_id = :userId');
        $associationCheck->bindValue(':conversationId', $conversationId);
        $associationCheck->bindValue(':userId', $user->getId());
        $associationCheck->execute();
        if ($associationCheck->fetchColumn() > 0) {
            return false; // L'association existe déjà
        }

        $requete = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
        $requete->bindValue(':conversationId', $conversationId);
        $requete->bindValue(':userId', $user->getId());
        $requete->execute();
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "name" => $this->getName(),
            "imageRepository" => $this->getImageRepository(),
            "imageFileName" => $this->getImageFileName(),
            "createdAt" => $this->getCreatedAt()?->format("Y-m-d"),
            "updatedAt" => $this->getUpdatedAt()?->format("Y-m-d")
        ];
    }

}