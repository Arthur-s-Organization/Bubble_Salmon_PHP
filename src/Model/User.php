<?php


namespace src\Model;

use JsonSerializable;
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
            $requete = BDD::getInstance()->prepare("INSERT INTO users (firstname,lastname,phone,birth_date, username, password, created_at, updated_at) VALUES (:firstname,:lastname,:phone,:birth_date, :username, :password, :created_at, :updated_at)");

            $requete->bindValue(':firstname', $user->getFirstname());
            $requete->bindValue(':lastname', $user->getLastname());
            $requete->bindValue(':phone', $user->getPhone());
            $requete->bindValue(':birth_date', $user->getBirthDate()?->format('Y-m-d'));
            $requete->bindValue(':username', $user->getUsername());
            $requete->bindValue(':password', $user->getPassword());
            $requete->bindValue(':created_at', $user->getCreatedAt()?->format('Y-m-d H:i:s'));
            $requete->bindValue(':updated_at', $user->getUpdatedAt()?->format('Y-m-d H:i:s'));

            $requete->execute();
            return BDD::getInstance()?->lastInsertId();
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlGetByUsername(string $username) {

        try
        {
            $requete = BDD::getInstance()->prepare("SELECT * FROM users WHERE username = :username");
            $requete->bindValue(':username', $username);
            $requete->execute();

            $sqlUser = $requete->fetch(\PDO::FETCH_ASSOC);
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

    public static function SqlGetAll() {
        try {
            $requete = BDD::getInstance()->prepare("SELECT * FROM users");
            $requete->execute();

            $sqlUsers = $requete->fetchAll(\PDO::FETCH_ASSOC);
            if ($sqlUsers !== false) {
                $users = [];
                foreach ($sqlUsers as $sqlUser) {
                    $user = new User();
                    $user->setId($sqlUser["id"])
                        ->setFirstname($sqlUser["firstname"])
                        ->setLastname($sqlUser["lastname"])
                        ->setphone($sqlUser["phone"])
                        ->setBirthDate(new \DateTime($sqlUser["birth_date"]))
                        ->setUsername($sqlUser["username"]);
                    $users[] = $user;
                }
                return $users;
            }
        }
        catch (\PDOException $e) {
            throw new ApiException('DataBase Error : ' . $e->getMessage(), 500);
        }
    }

    public static function SqlGetById(int $userId) {
        try {
            $requete = BDD::getInstance()->prepare("SELECT * FROM users WHERE id = :id");
            $requete->bindValue(':id', $userId);
            $requete->execute();

            $sqlUser = $requete->fetch(\PDO::FETCH_ASSOC);
            if ($sqlUser !== false) {
                $user = new User();
                $user->setId($sqlUser["id"])
                    ->setFirstname($sqlUser["firstname"])
                    ->setLastname($sqlUser["lastname"])
                    ->setphone($sqlUser["phone"])
                    ->setBirthDate(new \DateTime($sqlUser["birth_date"]))
                    ->setUsername($sqlUser["username"])
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
