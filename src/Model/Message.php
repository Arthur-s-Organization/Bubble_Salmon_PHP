<?php

namespace src\Model;
use JsonSerializable;
use src\Exception\ApiException;

class Message implements JsonSerializable{
    private ?int $id = null;
    private ?int $userId = null;
    private ?int $conversationId = null;
    private ?string $text= null;
    private ?string $imageRepository = null;
    private ?string $imageFileName = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;
    private ?string $username = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): Message
    {
        $this->username = $username;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): Message
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): Message
    {
        $this->userId = $userId;
        return $this;
    }

    public function getConversationId(): ?int
    {
        return $this->conversationId;
    }

    public function setConversationId(?int $conversationId): Message
    {
        $this->conversationId = $conversationId;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): Message
    {
        $this->text = $text;
        return $this;
    }

    public function getImageRepository(): ?string
    {
        return $this->imageRepository;
    }

    public function setImageRepository(?string $imageRepository): Message
    {
        $this->imageRepository = $imageRepository;
        return $this;
    }

    public function getImageFileName(): ?string
    {
        return $this->imageFileName;
    }

    public function setImageFileName(?string $imageFileName): Message
    {
        $this->imageFileName = $imageFileName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): Message
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): Message
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public static function SqlAdd(Message $message){
        try {
            $query = BDD::getInstance()->prepare("INSERT INTO messages (user_id, conversation_id, text, image_repository, image_file_name, created_at, updated_at) VALUES (:user_id, :conversation_id, :text, :image_repository, :image_file_name, :created_at, :updated_at)");

            $query->bindValue(':user_id', $message->getUserId());
            $query->bindValue(':conversation_id', $message->getConversationId());
            $query->bindValue(':text', $message->getText());
            $query->bindValue(':image_repository', $message->getImageRepository());
            $query->bindValue(':image_file_name', $message->getImageFileName());
            $query->bindValue(':created_at', $message->getCreatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':updated_at', $message->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $query->execute();
            $lastInsertMessageId = BDD::getInstance()->lastInsertId();

            $query = BDD::getInstance()->prepare("UPDATE conversations SET updated_at = :messageCreatedAt WHERE id = :conversationId");
            $query->bindValue(':messageCreatedAt', $message->getCreatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':conversationId', $message->getConversationId());
            $query->execute();

            return $lastInsertMessageId;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlGetAllByConversationId(int $conversationId) {
        try {
            $query = BDD::getInstance()->prepare('
            SELECT 
                m.id,
                m.user_id,
                m.conversation_id,
                m.text,
                m.image_repository,
                m.image_file_name,
                m.created_at,
                m.updated_at,
                u.username 
            FROM messages m 
            JOIN users u
                ON m.user_id = u.id
            WHERE conversation_id = :conversationId 
            ORDER BY created_at desc
            ');
            $query->bindValue(':conversationId', $conversationId);
            $query->execute();
            $messagesSql = $query->fetchall(\PDO::FETCH_ASSOC);

            $messagesObject = [];
            foreach ($messagesSql as $messageSql) {
                $message = new Message();
                $message->setId($messageSql["id"])
                    ->setConversationId($messageSql["conversation_id"])
                    ->setUserId($messageSql["user_id"])
                    ->settext($messageSql["text"])
                    ->setImageRepository($messageSql["image_repository"])
                    ->setImageFileName($messageSql["image_file_name"])
                    ->setcreatedAt(new \DateTime($messageSql["created_at"]))
                    ->setupdatedAt(new \DateTime($messageSql["updated_at"]))
                    ->setUsername($messageSql["username"]);
                $messagesObject[] = $message;
            }
            return $messagesObject;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "user_id" => $this->getUserId(),
            "conversation_id" => $this->getConversationId(),
            "text" => $this->getText(),
            "imageRepository" => $this->getImageRepository(),
            "imageFileName" => $this->getImageFileName(),
            "createdAt" => $this->getCreatedAt()?->format("Y-m-d H:i:s"),
            "updatedAt" => $this->getUpdatedAt()?->format("Y-m-d H:i:s"),
            "username" => $this->getUsername(),
        ];
    }

}