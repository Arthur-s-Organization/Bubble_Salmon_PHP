<?php

namespace src\Controller;

use src\Model\Conversation;
use src\Service\JwtService;

class ConversationController {

    public function __construct()
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    public function getAll() {
        if ($_SERVER["REQUEST_METHOD"] != "GET") {
            header("HTTP/1.1 405 Method Not Allowed");
            return json_encode(["code" => 1, "Message" => "Get Attendu"]);
        }
        $result = JwtService::checkToken();

        if($result["code"] == "1")
        {
            return json_encode($result);
        }

        $conversations = Conversation::SqlGetAllbyUserId($result["data"]["id"]);
        return json_encode($conversations);
    }

}