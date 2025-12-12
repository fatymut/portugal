<?php
// Fichier : db.php (Version finale et compatible avec tout)
require 'vendor/autoload.php'; 

use MongoDB\Client;

// On définit la fonction pour qu'elle soit accessible partout
function getDatabase() {
    try {
        // Connexion au serveur Docker (port 27017)
        // Si tu as changé le port dans docker-compose, change-le ici aussi !
        $client = new Client("mongodb://localhost:27017");
        
        // On retourne l'objet base de données 'genealogie'
        return $client->familytree;
        
    } catch (Exception $e) {
        die("Erreur de connexion Database : " . $e->getMessage());
    }
}

// Initialisation globale (pour les scripts simples qui n'utilisent pas la fonction)
try {
    $client = new Client("mongodb://localhost:27017");
    $db = $client->familytree;
    $collection = $db->individus;
} catch (Exception $e) {
    // On ne fait rien ici, la fonction getDatabase gérera les erreurs si appelée
}
?>