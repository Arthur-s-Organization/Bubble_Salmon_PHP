<?php


//// Inclure la classe BDD si elle n'est pas incluse dans le fichier actuel
//require_once __DIR__ . '/../src/Model/BDD.php';  // Ajustez le chemin si nécessaire
//
//try {
//    // Essayez d'obtenir l'instance de la connexion à la base de données
//    $db = src\Model\BDD::getInstance();
//
//    // Si la connexion est réussie, vous pouvez exécuter une requête simple pour tester
//    $stmt = $db->query("SELECT 1");  // Requête simple pour tester la connexion
//    $result = $stmt->fetch(PDO::FETCH_ASSOC);
//
//    // Si la requête réussit, vous affichez un message de succès
//    if ($result) {
//        echo "Connexion à la base de données réussie !";
//    } else {
//        echo "Erreur lors de la requête.";
//    }
//} catch (Exception $e) {
//    // Si une exception est lancée, afficher l'erreur
//    echo "Erreur : " . $e->getMessage();
//}



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


if($controller !== ''){
    try{
        $class = "src\Controller\\{$controller}Controller";
        if(class_exists($class)) {
            $controller = new $class();
            if (method_exists($controller, $action)) {
                echo $controller->$action($param);
            }
            else
            {
                throw new Exception("Action {$action} does not exist in {$class}");
            }
        }
        else
        {
            throw new Exception("Controller {$controller} does not exist");
        }
    }catch (Exception $e){
//        $controller = new ErrorController();
//        echo $controller->show($e);
    }
}else{
    //Page par défaut
//    $controller = new \src\Controller\StreetArtController();
//    echo $controller->index();
}

