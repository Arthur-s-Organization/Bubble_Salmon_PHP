<?php

namespace src\Controller;

class ErrorController
{
    public function show(\Exception $e)
    {
        http_response_code($e->getCode() ?: 500); // Utilise le code de l'exception ou 500 par défaut

        return json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
