<?php

namespace src\Model;

use src\Service\JwtService;

class Conversation {
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

    public function SqlGetAllbyUserId(int $userId) {
        $requete = BDD::getInstance()->prepare('SELECT * FROM conversations c JOIN conversations_users cu ON c.id = cu.conversation_id WHERE cu.user_id = :userId');
        $requete->bindValue(':userId', $userId);
        $requete->execute();

        $conversationsSql = $requete->fetchAll(\PDO::FETCH_ASSOC);
        $conversationsObject = [];
        foreach ($conversationsSql as $conversationSql) {
            $conversation = new Conversation();
            $conversation->setName($conversationsSql["name"])
                ->setId($conversationsSql["id"]);
            $conversationsObject[] = $conversation;
        }
        return $conversationsObject;
    }

    public function SqlGetById(int $id) {
        $requete = BDD::getInstance()->prepare('SELECT * FROM conversations WHERE id = :id');
        $requete->bindValue(':id', $id);
        $requete->execute();
        $sqlConversation = $requete->fetch(\PDO::FETCH_ASSOC);

        if ($sqlConversation != null) {
            $conversation = new Conversation();
            $conversation->setId($sqlConversation['Id'])
                ->setName($sqlConversation['Name']);

            return $conversation;
        }

        return null;
    }

    public function Sqladd(Conversation $conversation) {

        try {
            $requete = BDD::getInstance()->prepare("INSERT INTO conversations (name) VALUES (:name)");

            $requete->bindValue(':name', $conversation->getName());

            $requete->execute();
            return BDD::getInstance()->lastInsertId();
        } catch (\PDOException $e) {
            return $e->getMessage();
        }
    }
    
}