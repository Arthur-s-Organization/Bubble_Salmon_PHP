<?php

namespace src\Controller;

use DateTime;
use src\Exception\ApiException;
use src\Model\User;
use src\Service\JwtService;

class UserController {

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }


    public function register() {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if (empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if (!isset($jsonDatasObj->Firstname) || !isset($jsonDatasObj->Lastname) || !isset($jsonDatasObj->Phone) || !isset($jsonDatasObj->BirthDate) || !isset($jsonDatasObj->Username) || !isset($jsonDatasObj->Password))
        {
            throw new ApiException("Missing required fields", 400); // peut etre ajouter les autres champs
        }

//        if($jsonDatasObj->Firstname)

        $sqlRepository = null;
        $imageName = null;
        $now = new DateTime();

        if (isset($jsonDatasObj->Image)) {
            $imageName = uniqid() . ".jpg";
            //Fabriquer le répertoire d'accueil
            $dateNow = new \DateTime();
            $sqlRepository = $now->format('Y/m');
            $repository = './uploads/images/' . $now->format('Y/m');
            if (!is_dir($repository)) {
                mkdir($repository, 0777, true);
            }
            //Fabriquer l'image
            $ifp = fopen($repository . "/" . $imageName, "wb");
            fwrite($ifp, base64_decode($jsonDatasObj->Image));
            fclose($ifp);
        }

        $hashPassword = password_hash($jsonDatasObj->Password, PASSWORD_BCRYPT, ['cost' => 12]);

        $user = new User();
        $user->setFirstname($jsonDatasObj->Firstname)
            ->setLastname($jsonDatasObj->Lastname)
            ->setphone($jsonDatasObj->Phone)
            ->setBirthDate(new \DateTime($jsonDatasObj->BirthDate))
            ->setUsername($jsonDatasObj->Username)
            ->setPassword($hashPassword)
            ->setImageRepository($sqlRepository)
            ->setImageFileName($imageName)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $userId = User::SqlAdd($user);
        return json_encode(["status" => 'success', "message" => "User successfully added", "UserId" => $userId], JSON_THROW_ON_ERROR); // a voir gestio ndes erreus plsu tard peut etre
    }


    public function login()
    {
        header("Content-type: application/json; charset=utf-8");

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            throw new ApiException("Method POST expected", 405);
        }

        $jsonDatasStr = file_get_contents("php://input");
        $jsonDatasObj = json_decode($jsonDatasStr);

        if(empty($jsonDatasObj)) {
            throw new ApiException("No data provided in the request body", 400);
        }

        if(!isset($jsonDatasObj->Username) || !isset($jsonDatasObj->Password) ) {
            throw new ApiException("Missing required fields : Username and Password are required", 400);
        }

        $user = User::sqlGetByUsername($jsonDatasObj->Username);
        if($user === null) {
            throw new ApiException("The provided username does not exist in our system", 403);
        }

        if(!password_verify($jsonDatasObj->Password, $user->getPassword())){
            throw new ApiException("Invalid credentials: The password provided does not match our records", 403);
        }

        // Retourne le JWT
        return JwtService::createToken([
            "id" => $user->getId(),
            "username" => $user->getUsername()
        ]);
    }


    public function getAll() // récupère la liste de tous les utilisateurs
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        JwtService::checkToken();

        $users = User::sqlGetAll();
        return json_encode($users);
    }


    public function show() // récupère la "fiche" de l'utilisateur connecté
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        $tokensDatas = JwtService::checkToken();
        $userId = (int)$tokensDatas->id;

        $user = User::sqlGetById($userId);
        if($user === null) {
            throw new ApiException("User not found in our system. Please check your credentials and try again.", 403);
        }

        return json_encode($user);
    }

    public function getById(int $userId)
    {
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }
        JwtService::checkToken();

        $user = User::sqlGetById($userId);
        if($user === null) {
            throw new ApiException("User not found in our system. Please check your credentials and try again.", 403);
        }

        return json_encode($user);
    }

    public function search($filter) { // récupère la liste de tous les users correpondant à un critère de recherche
        if ($_SERVER["REQUEST_METHOD"] !== "GET") {
            throw new ApiException("Method GET expected", 405);
        }

        JwtService::checkToken();

        $users = User::sqlGetFileredUsers($filter);

        return json_encode($users);
    }
}