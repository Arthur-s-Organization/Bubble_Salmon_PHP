<?php


// Inclure la classe BDD si elle n'est pas incluse dans le fichier actuel
require_once __DIR__ . '/../src/Model/BDD.php';  // Ajustez le chemin si nécessaire

try {
    // Essayez d'obtenir l'instance de la connexion à la base de données
    $db = src\Model\BDD::getInstance();

    // Si la connexion est réussie, vous pouvez exécuter une requête simple pour tester
    $stmt = $db->query("SELECT 1");  // Requête simple pour tester la connexion
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si la requête réussit, vous affichez un message de succès
    if ($result) {
        echo "Connexion à la base de données réussie !";
    } else {
        echo "Erreur lors de la requête.";
    }
} catch (Exception $e) {
    // Si une exception est lancée, afficher l'erreur
    echo "Erreur : " . $e->getMessage();
}



