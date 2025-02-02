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
            $requete = BDD::getInstance()->prepare("INSERT INTO messages (user_id, conversation_id, text, image_repository, image_file_name, created_at, updated_at) VALUES (:user_id, :conversation_id, :text, :image_repository, :image_file_name, :created_at, :updated_at)");

            $requete->bindValue(':user_id', $message->getUserId());
            $requete->bindValue(':conversation_id', $message->getConversationId());
            $requete->bindValue(':text', $message->getText());
            $requete->bindValue(':image_repository', $message->getImageRepository());
            $requete->bindValue(':image_file_name', $message->getImageFileName());
            $requete->bindValue(':created_at', $message->getCreatedAt()?->format('Y-m-d H:i:s'));
            $requete->bindValue(':updated_at', $message->getUpdatedAt()?->format('Y-m-d H:i:s'));

            $requete->execute();
            return BDD::getInstance()->lastInsertId();
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
            "updatedAt" => $this->getUpdatedAt()?->format("Y-m-d H:i:s")
        ];
    }

}