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

    public static function sqlAdd(Message $message){
        try {

            $conversationExistQuery= BDD::getInstance()->prepare('
                SELECT COUNT(*) 
                FROM conversations
                WHERE id = :conversationId
            ');
            $conversationExistQuery->bindValue(':conversationId', $message->getConversationId());
            $conversationExistQuery->execute();
            if ($conversationExistQuery->fetchColumn() === 0) {
                throw new ApiException("Conversation {$message->getConversationId()} doesn't exists", 404);
            }

            $userBelongsToConversationQuery= BDD::getInstance()->prepare('
                SELECT COUNT(*)
                FROM conversations_users
                WHERE conversation_id = :conversationId 
                    AND user_id = :userId
            ');
            $userBelongsToConversationQuery->bindValue(':conversationId', $message->getConversationId());
            $userBelongsToConversationQuery->bindValue(':userId', $message->getUserId());
            $userBelongsToConversationQuery->execute();
            if ($userBelongsToConversationQuery->fetchColumn() === 0) {
                throw new ApiException("User {$message->getUserId()} doesn't belong to conversation {$message->getConversationId()}", 404);
            }

            $addConversationQuery = BDD::getInstance()->prepare("INSERT INTO messages (user_id, conversation_id, text, image_repository, image_file_name, created_at, updated_at) VALUES (:user_id, :conversation_id, :text, :image_repository, :image_file_name, :created_at, :updated_at)");
            $addConversationQuery->bindValue(':user_id', $message->getUserId());
            $addConversationQuery->bindValue(':conversation_id', $message->getConversationId());
            $addConversationQuery->bindValue(':text', $message->getText());
            $addConversationQuery->bindValue(':image_repository', $message->getImageRepository());
            $addConversationQuery->bindValue(':image_file_name', $message->getImageFileName());
            $addConversationQuery->bindValue(':created_at', $message->getCreatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':updated_at', $message->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->execute();
            $lastInsertMessageId = BDD::getInstance()->lastInsertId();

            $updateConversationQuery = BDD::getInstance()->prepare("UPDATE conversations SET updated_at = :messageCreatedAt WHERE id = :conversationId");
            $updateConversationQuery->bindValue(':messageCreatedAt', $message->getCreatedAt()?->format('Y-m-d H:i:s'));
            $updateConversationQuery->bindValue(':conversationId', $message->getConversationId());
            $updateConversationQuery->execute();

            return $lastInsertMessageId;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlGetAllByConversationId(int $conversationId, $userId) {
        try {
            $conversationExistQuery= BDD::getInstance()->prepare('
                SELECT COUNT(*) 
                FROM conversations
                WHERE id = :conversationId
            ');
            $conversationExistQuery->bindValue(':conversationId', $conversationId);
            $conversationExistQuery->execute();
            if ($conversationExistQuery->fetchColumn() === 0) {
                throw new ApiException("Conversation {$conversationId} doesn't exists", 404);
            }

            $userBelongsToConversationQuery= BDD::getInstance()->prepare('
                SELECT COUNT(*)
                FROM conversations_users
                WHERE conversation_id = :conversationId 
                    AND user_id = :userId
            ');
            $userBelongsToConversationQuery->bindValue(':conversationId', $conversationId);
            $userBelongsToConversationQuery->bindValue(':userId', $userId);
            $userBelongsToConversationQuery->execute();
            if ($userBelongsToConversationQuery->fetchColumn() === 0) {
                throw new ApiException("User {$userId} doesn't belong to conversation {$conversationId}", 404);
            }

            $getAllMessagesQuery = BDD::getInstance()->prepare('
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
            $getAllMessagesQuery->bindValue(':conversationId', $conversationId);
            $getAllMessagesQuery->execute();
            $messagesSql = $getAllMessagesQuery->fetchall(\PDO::FETCH_ASSOC);

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