<?php

require '../vendor/autoload.php';

spl_autoload_register(function ($class) {
    // J'obient : src\Model\Article
    // Faire un require du $class
    // unix séparé par les /, windows par des \
    $ds = DIRECTORY_SEPARATOR;
    $dir = $_SERVER['DOCUMENT_ROOT'] . $ds."..";
    $className = str_replace("\\", $ds, $class);
    $file = "{$dir}{$ds}{$className}.php";
    if(is_readable($file)) {
        require_once $file;
    }
});

$URLS = explode("/",$_GET["url"]);
$controller = (isset($URLS[0])) ? $URLS[0] : '';
$action = (isset($URLS[1])) ? $URLS[1] : '';
$param = $URLS[2] ?? '';


if($controller !== '')
{
    try
    {
        $class = "src\Controller\\{$controller}Controller";
        if(class_exists($class))
        {
            $controller = new $class();
            if (method_exists($controller, $action))
            {
                echo $controller->$action($param);
            }
            else
            {
                throw new \src\Exception\ApiException("Action {$action} does not exist in {$class}", 404);
            }
        }
        else
        {
            throw new \src\Exception\ApiException("Controller {$controller} does not exist", 404);
        }
    }
    catch (\src\Exception\ApiException $e)
    {
        $controller = new \src\Controller\ErrorController();
        echo $controller->show($e);
    }
}
else
{
//    $controller = new \src\Controller\UserController();
//    echo $controller->index();
    echo json_encode(["message" => "Welcome to our API!"]);
}

