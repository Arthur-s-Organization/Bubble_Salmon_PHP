<?php


namespace src\Model;

use JsonSerializable;

class User  implements JsonSerializable {
    private ?int $id = null;
    private ?string $firstname;
    private ?string $lastname;
    private ?string $phone;
    private ?\DateTime $birth_date;
    private ?string $username;
    private ?string $password;
    private ?string $imageRepository = null;
    private ?string $imageFileName = null;
    private ?\DateTime $createdAt;
    private ?\DateTime $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): User
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): User
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): User
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): User
    {
        $this->phone = $phone;
        return $this;
    }

    public function getBirthDate(): \DateTime
    {
        return $this->birth_date;
    }

    public function setBirthDate(\DateTime $birth_date): User
    {
        $this->birth_date = $birth_date;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): User
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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): User
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): User
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public static function SqlAdd(User $user)
    {
        try {
            $requete = BDD::getInstance()->prepare("INSERT INTO users (firstname,lastname,phone,birth_date, username, password, created_at, updated_at) VALUES (:firstname,:lastname,:phone,:birth_date, :username, :password, :created_at, :updated_at)");

            $requete->bindValue(':firstname', $user->getFirstname());
            $requete->bindValue(':lastname', $user->getLastname());
            $requete->bindValue(':phone', $user->getPhone());
            $requete->bindValue(':birth_date', $user->getBirthDate()?->format('Y-m-d'));
            $requete->bindValue(':username', $user->getUsername());
            $requete->bindValue(':password', $user->getPassword());
            $requete->bindValue(':created_at', $user->getCreatedAt()->format('Y-m-d'));
            $requete->bindValue(':updated_at', $user->getUpdatedAt()->format('Y-m-d'));

            $requete->execute();
            return BDD::getInstance()?->lastInsertId();
        }
        catch (\PDOException $e) {
            return $e->getMessage();
        }
    }

    public static function SqlGetByUsername(string $username) {
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

    public function jsonSerialize(): array
    {
        return [
            "id" => $this->getId(),
            "firstname" => $this->getLastname(),
            "lastname" => $this->getLastname(),
            "phone" => $this->getPhone(),
            "birth_date" => $this->getBirthDate(),
            "username" => $this->getUsername(),
            "password" => $this->getPassword(),
            "image_repository" => $this->getImageRepository(),
            "image_file_name" => $this->getImageFileName(),
            "created_at" => $this->getCreatedAt(),
            "updated_at" => $this->getUpdatedAt(),
        ];
    }
}
