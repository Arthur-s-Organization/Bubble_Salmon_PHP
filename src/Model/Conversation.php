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


    public static function sqlGetAllbyUserId(int $userId)
    {
        try {
            $getConversationsQuery = BDD::getInstance()->prepare("  
                    SELECT 
                        c.id,
                        CASE 
                            WHEN c.type = 1 THEN u.username
                            WHEN c.type = 3 THEN c.name
                            ELSE (SELECT u.username 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS conversation_name,
                        CASE 
                            WHEN c.type = 1 THEN u.image_repository
                            WHEN c.type = 3 THEN c.image_repository
                            ELSE (SELECT u.image_repository 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS image_repository,
                        CASE 
                            WHEN c.type = 1 THEN u.image_file_name
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
                    JOIN users u ON u.id = cu.user_id
                    LEFT JOIN messages m_last ON c.id = m_last.conversation_id 
                        AND m_last.created_at = (
                            SELECT MAX(m.created_at) 
                            FROM messages m 
                            WHERE m.conversation_id = c.id
                        )
                    WHERE cu.user_id = :userId
                    ORDER BY  c.updated_at desc

            ");

            $getConversationsQuery->bindValue(':userId', $userId);
            $getConversationsQuery->execute();

            $conversationsSql = $getConversationsQuery->fetchAll(\PDO::FETCH_ASSOC);
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


    public static function sqlGetById(int $conversationId, int $userId)
    {
        try {
            // Vérifie si l'association existe bien : que l'utilisateur appartient bien à la conversation
            $userBelongToConversationQuery = BDD::getInstance()->prepare('SELECT COUNT(*) FROM conversations_users WHERE conversation_id = :conversationId AND user_id = :userId');
            $userBelongToConversationQuery->bindValue(':conversationId', $conversationId);
            $userBelongToConversationQuery->bindValue(':userId', $userId);
            $userBelongToConversationQuery->execute();
            if ($userBelongToConversationQuery->fetchColumn() < 1) {
                throw new ApiException("User {$userId} doesn't belongs to conversation {$conversationId}", 409);
            }

            $getConversationQuery = BDD::getInstance()->prepare("
                SELECT 
                    c.id,
                    CASE 
                        WHEN c.type = 1 THEN u.username
                        WHEN c.type = 3 THEN c.name
                        ELSE u_other.username
                    END AS conversation_name,
                    CASE 
                        WHEN c.type = 1 THEN u.image_repository
                        WHEN c.type = 3 THEN c.image_repository
                        ELSE u_other.image_repository
                    END AS image_repository,
                    CASE 
                        WHEN c.type = 1 THEN u.image_file_name
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
            $getConversationQuery->bindValue(':id', $conversationId);
            $getConversationQuery->bindValue(':userId', $userId);
            $getConversationQuery->execute();

            $sqlConversation = $getConversationQuery->fetch(\PDO::FETCH_ASSOC);
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


    public static function sqlAddSelf(Conversation $conversation, int $userId)
    {
        try {
            $db = BDD::getInstance();

            // création de la conversation
            $addConversationQuery = $db->prepare("INSERT INTO conversations (name, image_repository, image_file_name ,created_at, updated_at, type) VALUES (:name, :image_repository, :image_file_name, :createdAt, :updatedAt, :type)");

            $addConversationQuery->bindValue(':name', $conversation->getName());
            $addConversationQuery->bindValue(':image_repository', $conversation->getImageRepository());
            $addConversationQuery->bindValue(':image_file_name', $conversation->getImageFileName());
            $addConversationQuery->bindValue(':createdAt', $conversation->getCreatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':type', $conversation->getType());

            $addConversationQuery->execute();
            $lastConversationId = BDD::getInstance()->lastInsertId();

            // ajout de l'utilisateur à la conv
            $addUserToConversationQuery = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $addUserToConversationQuery->bindValue(':conversationId', $lastConversationId);
            $addUserToConversationQuery->bindValue(':userId', $userId);
            $addUserToConversationQuery->execute();

            return $lastConversationId;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlAdd(Conversation $conversation, int $userId, int $recipientId)
    {
        try {
            $db = BDD::getInstance();

            // On vérifie si le destinataire existe
            $recipientExistQuery = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :recipientId');
            $recipientExistQuery->bindValue(':recipientId', $recipientId);
            $recipientExistQuery->execute();
            if ($recipientExistQuery->fetchColumn() === 0) {
                throw new ApiException("UserId {$recipientId} doesn''t exist", 404);
            }

            // création de la conversation
            $addConversationQuery = $db->prepare("INSERT INTO conversations (name, image_repository, image_file_name ,created_at, updated_at, type) VALUES (:name, :image_repository, :image_file_name, :createdAt, :updatedAt, :type)");

            $addConversationQuery->bindValue(':name', $conversation->getName());
            $addConversationQuery->bindValue(':image_repository', $conversation->getImageRepository());
            $addConversationQuery->bindValue(':image_file_name', $conversation->getImageFileName());
            $addConversationQuery->bindValue(':createdAt', $conversation->getCreatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':type', $conversation->getType());

            $addConversationQuery->execute();
            $lastConversationId = BDD::getInstance()->lastInsertId();

            // ajout de l'utilisateur connecté à la conv
            $addUserToConversationQuery = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $addUserToConversationQuery->bindValue(':conversationId', $lastConversationId);
            $addUserToConversationQuery->bindValue(':userId', $userId);
            $addUserToConversationQuery->execute();

            // ajout du destinataire à la conv
            $addRecipientToConversationQuery = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $addRecipientToConversationQuery->bindValue(':conversationId', $lastConversationId);
            $addRecipientToConversationQuery->bindValue(':userId', $recipientId);
            $addRecipientToConversationQuery->execute();

            return $lastConversationId;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function sqlAddGroup(Conversation $conversation, array $recipentIds, int $userId)
    {
        try {
            $db = BDD::getInstance();

             $recipentIds[] = $userId;

            // Vérifie si les destinataires existent
            foreach ($recipentIds as $recipentId) {
                $recipientExistsQuery = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :userId');
                $recipientExistsQuery->bindValue(':userId', $recipentId);
                $recipientExistsQuery->execute();
                if ($recipientExistsQuery->fetchColumn() === 0) {
                    throw new ApiException("UserId {$recipentId} doesn''t exist", 404);
                }
            }

            // On vérifie si ce groupe de conversation que l'on veut créé existe deja
            if (Conversation::Groupexists($recipentIds))
            {
                throw new ApiException("A Conversation with this users already exists", 404);
            }

            // création de la conversation
            $addConversationQuery = $db->prepare("INSERT INTO conversations (name, image_repository, image_file_name ,created_at, updated_at, type) VALUES (:name, :image_repository, :image_file_name, :createdAt, :updatedAt, :type)");

            $addConversationQuery->bindValue(':name', $conversation->getName());
            $addConversationQuery->bindValue(':image_repository', $conversation->getImageRepository());
            $addConversationQuery->bindValue(':image_file_name', $conversation->getImageFileName());
            $addConversationQuery->bindValue(':createdAt', $conversation->getCreatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':updatedAt', $conversation->getUpdatedAt()?->format('Y-m-d H:i:s'));
            $addConversationQuery->bindValue(':type', $conversation->getType());

            $addConversationQuery->execute();
            $lastConversationId = BDD::getInstance()->lastInsertId();

            // ajout des users à la conv
            foreach ($recipentIds as $recipentId) {
                $addUserToConvQuery = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
                $addUserToConvQuery->bindValue(':conversationId', $lastConversationId);
                $addUserToConvQuery->bindValue(':userId', $recipentId);
                $addUserToConvQuery->execute();
            }

            return $lastConversationId;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function sqlAddUserToGroup(int $userId, int $conversationId)
    {
        try {
            $db = BDD::getInstance();

            // Vérifie si l'utilisateur existe
            $userCheck = $db->prepare('SELECT COUNT(*) FROM users WHERE id = :userId');
            $userCheck->bindValue(':userId', $userId);
            $userCheck->execute();
            if ($userCheck->fetchColumn() === 0) {
                throw new ApiException("UserId {$userId} doesn''t exist", 404);
            }

            // verifie que la conversation existe bien (et que c'est une conv de groupe !)
            $conversationCheck = $db->prepare('SELECT COUNT(*) FROM conversations WHERE id = :conversationId AND type = 3');
            $conversationCheck->bindValue(':conversationId', $conversationId);
            $conversationCheck->execute();
            if ($conversationCheck->fetchColumn() === 0) {
                throw new ApiException("Group conversation {$conversationId} doesn't exist", 404);
            }

            // Vérifie que l'utilisateur n'existe pas déjà dans cette conversation
            $associationCheck = $db->prepare('SELECT COUNT(*) FROM conversations_users WHERE conversation_id = :conversationId AND user_id = :userId');
            $associationCheck->bindValue(':conversationId', $conversationId);
            $associationCheck->bindValue(':userId', $userId);
            $associationCheck->execute();
            if ($associationCheck->fetchColumn() > 0) {
                throw new ApiException("User {$userId} already belongs to conversation {$conversationId}", 409);
            }

            $usersQuery = $db->prepare('
                SELECT user_id  
                FROM conversations_users
                WHERE conversation_id = :conversationId
             ');
            $usersQuery->bindValue(':conversationId', $conversationId);
            $usersQuery->execute();
            $userIds = $usersQuery->fetchAll(\PDO::FETCH_COLUMN);
            $userIds[] = $userId;

            // Vérifie si cette nouvelle conversation n'existe pas déjà
            $newConversationExists = Conversation::Groupexists($userIds);
            if ($newConversationExists) {
                throw new ApiException("This conversation already exists", 409);
            }

            $userAddQuery = $db->prepare('INSERT INTO conversations_users (conversation_id, user_id) VALUES (:conversationId, :userId)');
            $userAddQuery->bindValue(':conversationId', $conversationId);
            $userAddQuery->bindValue(':userId', $userId);
            $userAddQuery->execute();
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }

    }


    public static function sqlUpdate(Conversation $conversation)
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


    public static function sqlSelfExists(int $userId)
    {
        try {
            // Vérifie si une association existe déjà
            $selfConversationExistQuery = BDD::getInstance()->prepare('
                SELECT COUNT(*) 
                FROM conversations_users cu
                JOIN conversations c
                    on cu.conversation_id = c.id
                WHERE 
                    user_id = :userId
                    AND c.type = 1
            ');
            $selfConversationExistQuery->bindValue(':userId', $userId);
            $selfConversationExistQuery->execute();
            if ($selfConversationExistQuery->fetchColumn() > 0) {
                return true;
            }
            return false;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function sqlExists(int $userId1, int $userId2) : bool // on vérifie si une conv à 2 existe déjà
    {
        try {
            // Vérifie si une association existe déjà
            $conversationExistsQuery = BDD::getInstance()->prepare('
                SELECT COUNT(*) 
                FROM conversations_users cu
                JOIN conversations c
                    on cu.conversation_id = c.id
                WHERE 
                    user_id = :user1
                    AND conversation_id IN (SELECT conversation_id FROM conversations_users WHERE user_id = :user2)
                    AND c.type = 2
            ');
            $conversationExistsQuery->bindValue(':user1', $userId1);
            $conversationExistsQuery->bindValue(':user2', $userId2);
            $conversationExistsQuery->execute();
            if ($conversationExistsQuery->fetchColumn() > 0) {
                return true;
            }
            return false;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function Groupexists(array $userIds): bool
    {
        if (empty($userIds)) {
            return false;
        }

        try {
            $bdd = BDD::getInstance();

            // Générer une liste de placeholders dynamiquement pour les paramètres
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            $sql = "
            WITH given_users AS (
                SELECT ? AS user_id UNION ALL " . implode(" UNION ALL ", array_fill(0, count($userIds) - 1, "SELECT ?")) . "
            ),
            conversation_matches AS (
                SELECT cu.conversation_id
                FROM conversations_users cu
                JOIN given_users gu ON cu.user_id = gu.user_id
                GROUP BY cu.conversation_id
                HAVING COUNT(*) = (SELECT COUNT(*) FROM given_users)
            )
            SELECT c.id
            FROM conversations c
            JOIN conversation_matches cm ON c.id = cm.conversation_id
            WHERE NOT EXISTS (
                SELECT 1 FROM conversations_users cu
                WHERE cu.conversation_id = c.id
                AND cu.user_id NOT IN (SELECT user_id FROM given_users)
            )
        ";

            $groupConversationExistsQuery = $bdd->prepare($sql);

            // Assigner les valeurs des utilisateurs dynamiquement
            foreach ($userIds as $index => $userId) {
                $groupConversationExistsQuery->bindValue($index + 1, $userId, \PDO::PARAM_INT);
            }

            $groupConversationExistsQuery->execute();
            return $groupConversationExistsQuery->fetchColumn() !== false;
        } catch (\PDOException $e) {
            throw new ApiException('Database Error: ' . $e->getMessage(), 500);
        }
    }


    public static function sqlGetSelfIdByUserId(int $userId)
    {
        try {
            $getSelfConversationQuery = BDD::getInstance()->prepare('
                SELECT c.id
                FROM conversations_users cu
                JOIN conversations c
                    on cu.conversation_id = c.id
                WHERE 
                    user_id = :userId
                    AND c.type = 1
            ');
            $getSelfConversationQuery->bindValue(':userId', $userId);
            $getSelfConversationQuery->execute();

            $result = $getSelfConversationQuery->fetch(\PDO::FETCH_ASSOC);
            return $result['id'];
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function sqlGetIdByUsersId(int $userId1, int $userId2) // récupềre une conv à 2 avec les ids des 2 users
    {
        try {
            $getConversationIdQuery = BDD::getInstance()->prepare('
                SELECT c.id
                FROM conversations_users cu
                JOIN conversations c
                    on cu.conversation_id = c.id
                WHERE 
                    user_id = :user1
                    AND conversation_id IN (SELECT conversation_id FROM conversations_users WHERE user_id = :user2)
                    AND c.type = 2
            ');
            $getConversationIdQuery->bindValue(':user1', $userId1);
            $getConversationIdQuery->bindValue(':user2', $userId2);
            $getConversationIdQuery->execute();

            $result = $getConversationIdQuery->fetch(\PDO::FETCH_ASSOC);
            return $result['id'];
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function getSqlImageRepositoryById(int $conversationId)
    {
        try {
            $getConvImageRepoQuery = BDD::getInstance()->prepare("SELECT image_repository FROM conversations WHERE id = :conversationId");
            $getConvImageRepoQuery->bindValue(':conversationId', $conversationId);
            $getConvImageRepoQuery->execute();

            $result = $getConvImageRepoQuery->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['image_repository'] : null;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function getSqlImageNameById(int $conversationId)
    {
        try {
            $getConvImageNameQuery = BDD::getInstance()->prepare("SELECT image_file_name FROM conversations WHERE id = :conversationId");
            $getConvImageNameQuery->bindValue(':conversationId', $conversationId);
            $getConvImageNameQuery->execute();

            $result = $getConvImageNameQuery->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['image_file_name'] : null;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function sqlGetNamebyId(int $conversationId)
    {
        try {
            $getConvNameQuery = BDD::getInstance()->prepare("SELECT name FROM conversations WHERE id = :conversationId");
            $getConvNameQuery->bindValue(':conversationId', $conversationId);
            $getConvNameQuery->execute();

            $result = $getConvNameQuery->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['name'] : null;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }


    public static function sqlGetFileredConversations(string $filter, int $userId)
    {
        try {
            $getFiltredConvQuery = BDD::getInstance()->prepare("  
                    SELECT 
                        c.id,
                        CASE 
                            WHEN c.type = 1 THEN u.username
                            WHEN c.type = 3 THEN c.name
                            ELSE (SELECT u.username 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS conversation_name,
                        CASE 
                            WHEN c.type = 1 THEN u.image_repository
                            WHEN c.type = 3 THEN c.image_repository
                            ELSE (SELECT u.image_repository 
                                  FROM users u 
                                  JOIN conversations_users cu ON u.id = cu.user_id 
                                  WHERE cu.conversation_id = c.id AND u.id <> :userId
                                  LIMIT 1) 
                        END AS image_repository,
                        CASE 
                            WHEN c.type = 1 THEN u.image_file_name
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
                    JOIN users u ON u.id = cu.user_id
                    LEFT JOIN messages m_last ON c.id = m_last.conversation_id 
                        AND m_last.created_at = (
                            SELECT MAX(m.created_at) 
                            FROM messages m 
                            WHERE m.conversation_id = c.id
                        )
                    WHERE cu.user_id = :userId
                        AND (
                            CASE 
                                WHEN c.type = 1 THEN u.username
                                WHEN c.type = 3 THEN c.name
                                ELSE (SELECT u.username 
                                      FROM users u 
                                      JOIN conversations_users cu ON u.id = cu.user_id 
                                      WHERE cu.conversation_id = c.id AND u.id <> :userId
                                      LIMIT 1) 
                            END
                        ) LIKE :filter
                    ORDER BY  c.updated_at desc
            ");

            $getFiltredConvQuery->bindValue(':userId', $userId);
            $getFiltredConvQuery->bindValue(':filter', "%{$filter}%");
            $getFiltredConvQuery->execute();

            $conversationsSql = $getFiltredConvQuery->fetchAll(\PDO::FETCH_ASSOC);
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
