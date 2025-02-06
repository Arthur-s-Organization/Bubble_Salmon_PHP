<?php

namespace src\Model;

use src\Exception\ApiException;
use src\Service\JwtService;
use JsonSerializable;

class Conversation implements JsonSerializable
{
    private ?int $id = null;
    private ?string $name = null;
    private ?string $imageRepository = null;
    private ?string $imageFileName = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;
    private ?Message $lastMessage = null;
    private ?int $type = null;

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(?int $type): Conversation
    {
        $this->type = $type;
        return $this;
    }


    public function getLastMessage(): ?Message
    {
        return $this->lastMessage;
    }

    public function setLastMessage(?Message $lastMessage): Conversation
    {
        $this->lastMessage = $lastMessage;
        return $this;
    }

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


    public static function SqlGetAllbyUserId(int $userId, string $username) // rajouter le case 1 conv à 1
    {
        try {
            $query = BDD::getInstance()->prepare("  
                    SELECT 
                        c.id,
                        CASE 
                            WHEN c.type = 3 THEN c.name
                            ELSE (SELECT u.username 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS conversation_name,
                        CASE 
                            WHEN c.type = 3 THEN c.image_repository
                            ELSE (SELECT u.image_repository 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS image_repository,
                        CASE 
                            WHEN c.type = 3 THEN c.image_file_name
                            ELSE (SELECT u.image_file_name 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS image_file_name,
                        c.created_at,
                        c.updated_at,
                        c.type,
                        m_last.id AS last_message_id,
                        m_last.text AS last_message,
                        m_last.conversation_id AS last_message_conversation_id,
                        m_last.user_id AS last_message_user_id,
                        m_last.image_repository AS last_message_image_repository,
                        m_last.image_file_name AS last_message_image_file_name,
                        m_last.created_at AS last_message_date,
                        m_last.updated_at AS last_message_update
                    FROM conversations c
                    JOIN conversations_users cu ON c.id = cu.conversation_id
                    LEFT JOIN messages m_last ON c.id = m_last.conversation_id 
                        AND m_last.created_at = (
                            SELECT MAX(m.created_at) 
                            FROM messages m 
                            WHERE m.conversation_id = c.id
                        )
                    WHERE cu.user_id = :userId;

            ");

            $query->bindValue(':userId', $userId);
            $query->bindValue(':username', $username);
            $query->execute();

            $conversationsSql = $query->fetchAll(\PDO::FETCH_ASSOC);
            $conversationsObject = [];
            foreach ($conversationsSql as $conversationSql) {

                $lastMessage = new Message();
                $lastMessage->setText($conversationSql['last_message'])
                    ->setId($conversationSql['last_message_id'])
                    ->setConversationId($conversationSql['last_message_conversation_id'])
                    ->setUserId($conversationSql['last_message_user_id'])
                    ->setImageFileName($conversationSql['last_message_image_file_name'])
                    ->setImageRepository($conversationSql['last_message_image_repository'])
                    ->setUpdatedAt($conversationSql['last_message_update'] ? new \DateTime($conversationSql['last_message_update']) : null)
                    ->setCreatedAt($conversationSql['last_message_date'] ? new \DateTime($conversationSql['last_message_date']) : null);

                $conversation = new Conversation();
                $conversation->setName($conversationSql["conversation_name"])
                    ->setId($conversationSql["id"])
                    ->setImageRepository($conversationSql["image_repository"])
                    ->setImageFileName($conversationSql["image_file_name"])
                    ->setcreatedAt(new \DateTime($conversationSql["created_at"]))
                    ->setupdatedAt(new \DateTime($conversationSql["updated_at"]))
                    ->setType($conversationSql["type"])
                    ->setLastMessage($lastMessage);
                $conversationsObject[] = $conversation;
            }
            return $conversationsObject;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }

    }

    public static function SqlGetById(int $conversationId, string $username, int $userId)
    {
        try {
            // Vérifie si l'association existe bien
            $associationCheck = BDD::getInstance()->prepare('SELECT COUNT(*) FROM conversations_users WHERE conversation_id = :conversationId AND user_id = :userId');
            $associationCheck->bindValue(':conversationId', $conversationId);
            $associationCheck->bindValue(':userId', $userId);
            $associationCheck->execute();
            if ($associationCheck->fetchColumn() < 1) {
                throw new ApiException("User {$userId} doesn't belongs to conversation {$conversationId}", 409);
            }
//            $requete = BDD::getInstance()->prepare('SELECT * FROM conversations WHERE id = :id');
            $query = BDD::getInstance()->prepare("
                SELECT 
                    c.id,
                    CASE 
                        WHEN c.type = 3 THEN c.name
                        ELSE u_other.username
                    END AS conversation_name,
                    CASE 
                        WHEN c.type = 3 THEN c.image_repository
                        ELSE u_other.image_repository
                    END AS image_repository,
                    CASE 
                        WHEN c.type = 3 THEN c.image_file_name
                        ELSE u_other.image_file_name
                    END AS image_file_name,
                    c.created_at,
                    c.updated_at,
                    c.type
                FROM conversations c
                JOIN conversations_users cu ON c.id = cu.conversation_id
                JOIN users u ON cu.user_id = u.id
                LEFT JOIN (
                    SELECT u.id, u.username, u.image_repository, u.image_file_name, cu.conversation_id
                    FROM users u
                    JOIN conversations_users cu ON u.id = cu.user_id
                ) u_other ON u_other.conversation_id = c.id AND u_other.id <> :userId
                WHERE c.id = :id
                AND cu.user_id = :userId;
                ");
            $query->bindValue(':id', $conversationId);
            $query->bindValue(':userId', $userId);
            $query->execute();

            $sqlConversation = $query->fetch(\PDO::FETCH_ASSOC);
            if ($sqlConversation !== false) {
                $conversation = new Conversation();
                $conversation->setId($sqlConversation["id"])
                    ->setName($sqlConversation["conversation_name"])
                    ->setImageRepository($sqlConversation["image_repository"])
                    ->setImageFileName($sqlConversation["image_file_name"])
                    ->setcreatedAt(new \DateTime($sqlConversation["created_at"]))
                    ->setupdatedAt(new \DateTime($sqlConversation["updated_at"]))
                    ->setType($sqlConversation["type"]);

                return $conversation;
            }
            return null;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function SqlAdd(Conversation $conversation, int $userId, int $recipientId) // on est sur que la conv existe pas pas à ce moment la
    {
        try {
            $db = BDD::getInstance();

            // On vérifie si le destinataire existe
            $userCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :recipientId');
            $userCheck->bindValue(':recipientId', $recipientId);
            $userCheck->execute();
            if ($userCheck->fetchColumn() === 0) {
                throw new ApiException("UserId {$recipientId} doesn''t exist", 404);
            }

            // création de la conversation
            $query = $db->prepare("INSERT INTO conversations (name, image_repository, image_file_name ,created_at, updated_at, type) VALUES (:name, :image_repository, :image_file_name, :createdAt, :updatedAt, :type)");

            $query->bindValue(':name', $conversation->getName());
            $query->bindValue(':image_repository', $conversation->getImageRepository());
            $query->bindValue(':image_file_name', $conversation->getImageFileName());
            $query->bindValue(':createdAt', $conversation->getCreatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':type', $conversation->getType());

            $query->execute();
            $lastConversationId = BDD::getInstance()->lastInsertId();

            // ajout de l'utilisateur connecté à la conv
            $query = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $query->bindValue(':conversationId', $lastConversationId);
            $query->bindValue(':userId', $userId);
            $query->execute();

            // ajout du destinataire à la conv
            $query = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $query->bindValue(':conversationId', $lastConversationId);
            $query->bindValue(':userId', $recipientId);
            $query->execute();

            return $lastConversationId;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function SqlAddGroup(Conversation $conversation, array $recipentIds, int $userId)
    { // penser ajout de la logique de vérification pour ne pas créer de conv redondante

        try {
            $db = BDD::getInstance();

            foreach ($recipentIds as $recipentId) {
                // Vérifie si les destinataires existe
                $userCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :userId');
                $userCheck->bindValue(':userId', $recipentId);
                $userCheck->execute();
                if ($userCheck->fetchColumn() === 0) {
                    throw new ApiException("UserId {$recipentId} doesn''t exist", 404);
                }
            }

            if (Conversation::Groupexists($recipentIds)) // On vérifie si ce groupe de conversation que l'on veut créé existe deja
            {
                throw new ApiException("A Conversation with this users already exists", 404);
            }

            // création de la conversation
            $query = $db->prepare("INSERT INTO conversations (name, image_repository, image_file_name ,created_at, updated_at, type) VALUES (:name, :image_repository, :image_file_name, :createdAt, :updatedAt, :type)");

            $query->bindValue(':name', $conversation->getName());
            $query->bindValue(':image_repository', $conversation->getImageRepository());
            $query->bindValue(':image_file_name', $conversation->getImageFileName());
            $query->bindValue(':createdAt', $conversation->getCreatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':type', $conversation->getType());

            $query->execute();
            $lastConversationId = BDD::getInstance()->lastInsertId();

            // ajout de l'utilisateur connecté à la conv
            $query = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $query->bindValue(':conversationId', $lastConversationId);
            $query->bindValue(':userId', $userId);
            $query->execute();

            // ajout des autres destinataires de la conv
            foreach ($recipentIds as $recipentId) {
                $query = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
                $query->bindValue(':conversationId', $lastConversationId);
                $query->bindValue(':userId', $recipentId);
                $query->execute();
            }

            return $lastConversationId;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function SqlAddUser(int $userId, int $conversationId)
    {

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

            $query = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $query->bindValue(':conversationId', $conversationId);
            $query->bindValue(':userId', $userId);
            $query->execute();
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }

    }

    public static function SqlUpdate(Conversation $conversation)
    {
        try {
            $query = BDD::getInstance()->prepare("UPDATE conversations SET name =:name, image_repository = :image_repository,image_file_name = :image_file_name, updated_at = :updatedAt WHERE id = :id");

            $query->bindValue(':name', $conversation->getName());
            $query->bindValue(':image_repository', $conversation->getImageRepository());
            $query->bindValue(':image_file_name', $conversation->getImageFileName());
            $query->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $query->bindValue(':id', $conversation->getId());

            $query->execute();
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function exists($userId1, $userId2) : bool // on vérifie si une conv à 2 existe déjà
    {
        try {
            // Vérifie si une association existe déjà
            $associationCheck = BDD::getInstance()->prepare('
                SELECT COUNT(*) 
                FROM conversations_users cu
                JOIN conversations c
                    on cu.conversation_id = c.id
                WHERE 
                    user_id = :user1
                    AND conversation_id IN (SELECT conversation_id FROM conversations_users WHERE user_id = :user2)
                    AND c.type = 2
            ');
            $associationCheck->bindValue(':user1', $userId1);
            $associationCheck->bindValue(':user2', $userId2);
            $associationCheck->execute();
            if ($associationCheck->fetchColumn() > 0) {
                return true;
            }
            return false;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function Groupexists(array $userIds): bool  // on vérifie si une conv de groupe existe déjà
    {
        try {
            // Générer les placeholders pour chaque user_id
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            // Connexion à la base de données
            $bdd = BDD::getInstance();

            // Requête SQL avec les placeholders dynamiques
            $associationCheck = $bdd->prepare('
                SELECT conversation_id
                FROM conversations_users
                WHERE user_id IN (' . $placeholders . ') 
                GROUP BY conversation_id
                HAVING COUNT(DISTINCT user_id) = ?
        ');

            // Lier les valeurs des userIds aux placeholders
            foreach ($userIds as $index => $userId) {
                $associationCheck->bindValue($index + 1, $userId, \PDO::PARAM_INT);
            }

            // Lier le nombre d'utilisateurs (dernier paramètre, également positionnel)
            $associationCheck->bindValue(count($userIds) + 1, count($userIds), \PDO::PARAM_INT);

            // Exécuter la requête
            $associationCheck->execute();

            // Vérifie si la conversation existe
            return $associationCheck->fetchColumn() !== false;
        } catch (\PDOException $e) {
            throw new ApiException('Database Error test: ' . $e->getMessage(), 500);
        }
    }


    public static function SqlGetIdByUsersId(int $userId1, int $userId2) // récupềre une conv privée avec les ids des 2 users
    {
        try {
            $query = BDD::getInstance()->prepare('
                SELECT c.id
                FROM conversations_users cu
                JOIN conversations c
                    on cu.conversation_id = c.id
                WHERE 
                    user_id = :user1
                    AND conversation_id IN (SELECT conversation_id FROM conversations_users WHERE user_id = :user2)
                    AND c.type = 2
            ');
            $query->bindValue(':user1', $userId1);
            $query->bindValue(':user2', $userId2);
            $query->execute();

            $result = $query->fetch(\PDO::FETCH_ASSOC);
            return $result['id'];
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function getSqlImageRepository($conversationId)
    {
        try {
            $query = BDD::getInstance()->prepare("SELECT image_repository FROM conversations WHERE id = :conversationId");
            $query->bindValue(':conversationId', $conversationId);
            $query->execute();

            $result = $query->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['image_repository'] : null;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function getSqlImageName($conversationId)
    {
        try {
            $query = BDD::getInstance()->prepare("SELECT image_file_name FROM conversations WHERE id = :conversationId");
            $query->bindValue(':conversationId', $conversationId);
            $query->execute();

            $result = $query->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['image_file_name'] : null;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlGetFileredConversations(string $filter) // pb ! on renvoie juste les conversations et pas les noms des users
    {
        try {
            $query = BDD::getInstance()->prepare("SELECT * FROM users WHERE username LIKE :filter");
            $query->bindValue(':filter', "%{$filter}%");
            $query->execute();

            $sqlConversations = $query->fetchAll(\PDO::FETCH_ASSOC);
            if ($sqlConversations !== false) {
                $conversations = [];
                foreach ($sqlConversations as $sqlConversation) {
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
        } catch (\PDOException $e) {
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
            "updatedAt" => $this->getUpdatedAt()?->format("Y-m-d H:i:s"),
            "type" => $this->getType(),
            "last_message" => $this->getLastMessage()
        ];
    }

}


//            SELECT
//              DISTINCT
//                c.id,
//                CASE
//                    WHEN LENGTH(c.name)>1 THEN c.name
//                    ELSE u.username
//                END conversations_name,
//                CASE
//                    WHEN LENGTH(c.name)>1 THEN c.image_repository
//                    ELSE u.image_repository
//                END image_repository,
//                CASE
//                    WHEN LENGTH(c.name)>1 THEN c.image_file_name
//                    ELSE u.image_file_name
//                END image_file_name,
//                c.created_at,
//                c.updated_at,
//                c.type,
//                m_last.id AS last_message_id,
//                m_last.text AS last_message,
//                m_last.conversation_id AS last_message_conversation_id,
//                m_last.user_id AS last_message_user_id,
//                m_last.image_repository AS last_message_image_repository,
//                m_last.image_file_name AS last_message_image_file_name,
//                m_last.created_at AS last_message_date,
//                m_last.updated_at AS last_message_update
//
//              FROM conversations c
//              JOIN conversations_users cu on c.id = cu.conversation_id
//              JOIN users u on cu.user_id = u.id
//              LEFT JOIN messages m_last ON c.id = m_last.conversation_id
//                        AND m_last.created_at = (
//                            SELECT MAX(m.created_at)
//                            FROM messages m
//                            WHERE m.conversation_id = c.id
//                        )
//              WHERE u.username not like :username
//                        AND c.id IN (SELECT id FROM conversations c2 join conversations_users cu2 ON c2.id = cu2.conversation_id WHERE cu2.user_id = :userId)





//SELECT
//                DISTINCT
//                    c.id,
//                    CASE
//                        WHEN LENGTH(c.name) = 0 or c.name is null THEN u.username
//                        ELSE c.name
//                    END as conversation_name,
//                   CASE
//                        WHEN LENGTH(c.name)>1 THEN c.image_repository
//                        ELSE u.image_repository
//                    END image_repository,
//                    CASE
//                        WHEN LENGTH(c.name)>1 THEN c.image_file_name
//                        ELSE u.image_file_name
//                    END image_file_name,
//                    c.created_at,
//                    c.updated_at,
//                    c.type
//                FROM conversations c
//                JOIN conversations_users cu on c.id = cu.conversation_id
//                JOIN users u on cu.user_id = u.id
//                WHERE c.id = :id and u.username not like :username");