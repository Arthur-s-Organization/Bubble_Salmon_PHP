<?php


namespace src\Model;

use JsonSerializable;
use Random\Engine\Secure;
use src\Exception\ApiException;

class User  implements JsonSerializable {
    private ?int $id = null;
    private ?string $firstname = null;
    private ?string $lastname = null;
    private ?string $phone  = null;
    private ?\DateTime $birth_date = null;
    private ?string $username = null;
    private ?string $password  = null;
    private ?string $imageRepository = null;
    private ?string $imageFileName = null;
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): User
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): User
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): User
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): User
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birth_date;
    }

    public function setBirthDate(?\DateTime $birth_date): User
    {
        $this->birth_date = $birth_date;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): User
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): User
    {
        $this->password = $password;
        return $this;
    }

    public function getImageRepository(): ?string
    {
        return $this->imageRepository;
    }

    public function setImageRepository(?string $imageRepository): User
    {
        $this->imageRepository = $imageRepository;
        return $this;
    }

    public function getImageFileName(): ?string
    {
        return $this->imageFileName;
    }

    public function setImageFileName(?string $imageFileName): User
    {
        $this->imageFileName = $imageFileName;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): User
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public static function SqlAdd(User $user)
    {
        try
        {
            $addUserQuery = BDD::getInstance()->prepare("INSERT INTO users (firstname,lastname,phone,birth_date, username, password, image_repository, image_file_name, created_at, updated_at) VALUES (:firstname,:lastname,:phone,:birth_date, :username, :password, :image_repository, :image_file_name, :created_at, :updated_at)");

            $addUserQuery->bindValue(':firstname', $user->getFirstname());
            $addUserQuery->bindValue(':lastname', $user->getLastname());
            $addUserQuery->bindValue(':phone', $user->getPhone());
            $addUserQuery->bindValue(':birth_date', $user->getBirthDate()?->format('Y-m-d'));
            $addUserQuery->bindValue(':username', $user->getUsername());
            $addUserQuery->bindValue(':password', $user->getPassword());
            $addUserQuery->bindValue(':image_repository', $user->getImageRepository());
            $addUserQuery->bindValue(':image_file_name', $user->getImageFileName());
            $addUserQuery->bindValue(':created_at', $user->getCreatedAt()?->format('Y-m-d H:i:s'));
            $addUserQuery->bindValue(':updated_at', $user->getUpdatedAt()?->format('Y-m-d H:i:s'));

            $addUserQuery->execute();
            return BDD::getInstance()?->lastInsertId();
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlGetByUsername(string $username) {

        try
        {
            $getUserQuery = BDD::getInstance()->prepare("SELECT * FROM users WHERE username = :username");
            $getUserQuery->bindValue(':username', $username);
            $getUserQuery->execute();

            $sqlUser = $getUserQuery->fetch(\PDO::FETCH_ASSOC);
            if ($sqlUser !== false) {
                $user = new User();
                $user->setId($sqlUser["id"])
                    ->setUsername($sqlUser["username"])
                    ->setPassword($sqlUser["password"]);
                return $user;
            }
            return null;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlGetAll() {
        try {
            $getUsersQuery = BDD::getInstance()->prepare("SELECT * FROM users ORDER BY username asc");
            $getUsersQuery->execute();

            $sqlUsers = $getUsersQuery->fetchAll(\PDO::FETCH_ASSOC);
            if ($sqlUsers !== false) {
                $users = [];
                foreach ($sqlUsers as $sqlUser) {
                    $user = new User();
                    $user->setId($sqlUser["id"])
                        ->setFirstname($sqlUser["firstname"])
                        ->setLastname($sqlUser["lastname"])
                        ->setphone($sqlUser["phone"])
                        ->setBirthDate(new \DateTime($sqlUser["birth_date"]))
                        ->setUsername($sqlUser["username"])
                        ->setImageRepository($sqlUser["image_repository"])
                        ->setImageFileName($sqlUser["image_file_name"])
                        ->setCreatedAt(new \DateTime($sqlUser["created_at"]))
                        ->setupdatedAt(new \DateTime($sqlUser["updated_at"]));
                    $users[] = $user;
                }
                return $users;
            }
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlGetById(int $userId) {
        try {
            $getUserQuery = BDD::getInstance()->prepare("SELECT * FROM users WHERE id = :id");
            $getUserQuery->bindValue(':id', $userId);
            $getUserQuery->execute();

            $sqlUser = $getUserQuery->fetch(\PDO::FETCH_ASSOC);
            if ($sqlUser !== false) {
                $user = new User();
                $user->setId($sqlUser["id"])
                    ->setFirstname($sqlUser["firstname"])
                    ->setLastname($sqlUser["lastname"])
                    ->setphone($sqlUser["phone"])
                    ->setBirthDate(new \DateTime($sqlUser["birth_date"]))
                    ->setUsername($sqlUser["username"])
                    ->setImageRepository($sqlUser["image_repository"])
                    ->setImageFileName($sqlUser["image_file_name"])
                    ->setCreatedAt(new \DateTime($sqlUser["created_at"]))
                    ->setupdatedAt(new \DateTime($sqlUser["updated_at"]));
                return $user;
            }
            return null;
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlGetFileredUsers(string $filter)
    {
        try
        {
            $getUsersQuery = BDD::getInstance()->prepare("SELECT * FROM users WHERE username LIKE :filter");
            $getUsersQuery->bindValue(':filter', "%{$filter}%");
            $getUsersQuery->execute();

            $sqlUsers = $getUsersQuery->fetchAll(\PDO::FETCH_ASSOC);
            if ($sqlUsers !== false)
            {
                $users = [];
                foreach ($sqlUsers as $sqlUser)
                {
                    $user = new User();
                    $user->setId($sqlUser["id"])
                        ->setFirstname($sqlUser["firstname"])
                        ->setLastname($sqlUser["lastname"])
                        ->setphone($sqlUser["phone"])
                        ->setBirthDate(new \DateTime($sqlUser["birth_date"]))
                        ->setUsername($sqlUser["username"])
                        ->setImageRepository($sqlUser["image_repository"])
                        ->setImageFileName($sqlUser["image_file_name"])
                        ->setCreatedAt(new \DateTime($sqlUser["created_at"]))
                        ->setupdatedAt(new \DateTime($sqlUser["updated_at"]));
                    $users[] = $user;
                }
                return $users;
            }
        }
        catch (\PDOException $e)
        {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlPhoneExists(string $phone): bool {
        try {
            $getPhoneQuery = BDD::getInstance()->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone");
            $getPhoneQuery->bindValue(':phone', $phone);
            $getPhoneQuery->execute();

            $count = $getPhoneQuery->fetchColumn();
            return $count > 0;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function sqlUsernameExists(string $username): bool {
        try {
            $getUsernameQuery = BDD::getInstance()->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $getUsernameQuery->bindValue(':username', $username);
            $getUsernameQuery->execute();

            $count = $getUsernameQuery->fetchColumn();
            return $count > 0;
        } catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "phone" => $this->getPhone(),
            "birth_date" => $this->getBirthDate()?->format("Y-m-d"),
            "username" => $this->getUsername(),
            "password" => $this->getPassword(),
            "image_repository" => $this->getImageRepository(),
            "image_file_name" => $this->getImageFileName(),
            "created_at" => $this->getCreatedAt()?->format("Y-m-d H:i:s"),
            "updated_at" => $this->getUpdatedAt()?->format("Y-m-d H:i:s"),
        ];
    }
}
