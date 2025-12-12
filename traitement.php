<?php
// ==============================================================================
// ZONE 1 : CHARGEMENT ET SECURITE
// ==============================================================================
require 'vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// On vérifie que le formulaire a bien été soumis en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ajouter.php");
    exit;
}

try {
    // ==============================================================================
    // ZONE 2 : CONNEXION A LA BASE DE DONNEES
    // ==============================================================================
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->familytree->individus; 

    // ==============================================================================
    // ZONE 3 : RECUPERATION DES DONNEES (SANS TERNAIRES)
    // ==============================================================================
    
    // --- 1. Identité ---
    $nom = '';
    if (isset($_POST['nom'])) {
        $nom = htmlspecialchars($_POST['nom']);
    }

    $prenom = '';
    if (isset($_POST['prenom'])) {
        $prenom = htmlspecialchars($_POST['prenom']);
    }

    $sexe = 'M'; // Valeur par défaut
    if (isset($_POST['sexe'])) {
        $sexe = $_POST['sexe'];
    }

    $nationalite = 'Portugaise'; // Valeur par défaut
    if (isset($_POST['nationalite'])) {
        // On vérifie que le champ n'est pas vide
        if (!empty($_POST['nationalite'])) {
            $nationalite = $_POST['nationalite'];
        }
    }

    // --- 2. Naissance ---
    $dateNaissance = '';
    if (isset($_POST['date_naissance'])) {
        $dateNaissance = $_POST['date_naissance'];
    }

    $lieuNaissance = '';
    if (isset($_POST['lieu_naissance'])) {
        $lieuNaissance = $_POST['lieu_naissance'];
    }

    // ==============================================================================
    // ZONE 4 : GESTION DES PARENTS (Compatible Schema V2)
    // ==============================================================================
    $parentsArray = [];

    // --- Traitement du PÈRE ---
    if (isset($_POST['pere_id'])) {
        $pereId = $_POST['pere_id'];
        
        // Si un père a été sélectionné (l'ID n'est pas vide)
        if (!empty($pereId)) {
            $parentsArray[] = [
                'id' => new ObjectId($pereId),
                'role' => 'Pere',
                'nature' => 'Biologique' // Valeur par défaut pour l'ajout simple
            ];
        }
    }

    // --- Traitement de la MÈRE ---
    if (isset($_POST['mere_id'])) {
        $mereId = $_POST['mere_id'];

        // Si une mère a été sélectionnée
        if (!empty($mereId)) {
            $parentsArray[] = [
                'id' => new ObjectId($mereId),
                'role' => 'Mere',
                'nature' => 'Biologique' // Valeur par défaut pour l'ajout simple
            ];
        }
    }

    // ==============================================================================
    // ZONE 5 : CREATION DU DOCUMENT COMPLET
    // ==============================================================================
    $document = [
        'identite' => [
            'nom'           => $nom,
            'prenom'        => $prenom,
            'sexe'          => $sexe,
            'nationalite'   => $nationalite
        ],
        'naissance' => [
            'date' => $dateNaissance,
            'lieu' => $lieuNaissance
        ],
        'parents'   => $parentsArray, // Tableau généré en Zone 4
        'relations' => []             // Tableau vide à la création (Schema V2)
    ];

    // ==============================================================================
    // ZONE 6 : INSERTION ET REDIRECTION
    // ==============================================================================
    
    // Insertion dans MongoDB
    $result = $collection->insertOne($document);

    // Vérification du succès
    if ($result->getInsertedCount() == 1) {
        // Succès : retour au formulaire
        header("Location: ajouter.php?msg=ok");
        exit;
    } else {
        die("Erreur : L'insertion dans la base a échoué.");
    }

} catch (Exception $e) {
    die("<h1>Erreur Technique</h1><p>" . $e->getMessage() . "</p>");
}
?>