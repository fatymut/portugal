<?php
// ==============================================================================
// ZONE 1 : CHARGEMENT DES BIBLIOTHEQUES ET CONNEXION
// ==============================================================================
require 'vendor/autoload.php';
use MongoDB\BSON\ObjectId;

try {
    // Connexion à la base de données MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->familytree->individus; 

    // ==============================================================================
    // ZONE 2 : RECUPERATION DES PARAMETRES (SANS TERNAIRES)
    // ==============================================================================
    
    // On initialise les variables à vide par défaut
    $action = '';
    $id = '';

    // On vérifie si 'action' existe dans l'URL ou le formulaire
    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
    }

    // On vérifie si 'id' existe
    if (isset($_REQUEST['id'])) {
        $id = $_REQUEST['id'];
    }

    // Sécurité : Si pas d'ID, on arrête tout
    if (empty($id)) {
        die("Erreur : ID manquant");
    }

    // ==============================================================================
    // ZONE 3 : ACTION DE SUPPRESSION (DELETE)
    // ==============================================================================
    if ($action === 'delete') {
        
        // On supprime le document qui correspond à l'ID
        $collection->deleteOne(['_id' => new ObjectId($id)]);
        
        // On redirige vers l'accueil
        header("Location: visualiser.php");
        exit;
    }

    // ==============================================================================
    // ZONE 4 : GESTION DES RELATIONS (COUPLES / DIVORCES)
    // ==============================================================================

    // --- CAS A : TERMINER UNE RELATION (Divorce / Séparation) ---
    if ($action === 'end_relation') {
        
        // 1. Récupération des données du formulaire
        $partnerId = '';
        if (isset($_POST['partner_id'])) {
            $partnerId = $_POST['partner_id'];
        }

        $dateFin = date('Y-m-d'); // Date d'aujourd'hui par défaut
        if (!empty($_POST['date_fin'])) {
            $dateFin = $_POST['date_fin'];
        }

        // 2. Mise à jour dans la base (si on a bien un partenaire)
        if (!empty($partnerId)) {
            
            // Mise à jour de la fiche de la personne principale
            $collection->updateOne(
                [ '_id' => new ObjectId($id), 'relations.partner_id' => new ObjectId($partnerId) ],
                [ '$set' => [ 'relations.$.date_fin' => $dateFin, 'relations.$.statut' => 'Terminé' ] ]
            );
            
            // Mise à jour de la fiche du partenaire (Réciproque)
            $collection->updateOne(
                [ '_id' => new ObjectId($partnerId), 'relations.partner_id' => new ObjectId($id) ],
                [ '$set' => [ 'relations.$.date_fin' => $dateFin, 'relations.$.statut' => 'Terminé' ] ]
            );
        }

        // 3. Redirection
        header("Location: modifier.php?id=$id&msg=relation_ended");
        exit;
    }

    // --- CAS B : AJOUTER UNE NOUVELLE RELATION ---
    if ($action === 'add_relation') {
        
        // 1. Récupération simple des données
        $partnerId = $_POST['new_partner_id'];
        $type = $_POST['type_relation']; 
        $dateDebut = $_POST['date_debut'];

        // 2. Création de l'objet "Relation"
        $relationData = [
            'partner_id' => new ObjectId($partnerId),
            'type' => $type,
            'date_debut' => $dateDebut,
            'date_fin' => null,
            'statut' => 'Actif'
        ];

        // 3. Ajout chez la personne principale
        $collection->updateOne(
            ['_id' => new ObjectId($id)], 
            ['$push' => ['relations' => $relationData]]
        );
        
        // 4. Ajout chez le partenaire (en inversant l'ID)
        $relationData['partner_id'] = new ObjectId($id); // On remplace l'ID par celui de la personne principale
        
        $collection->updateOne(
            ['_id' => new ObjectId($partnerId)], 
            ['$push' => ['relations' => $relationData]]
        );

        header("Location: modifier.php?id=$id&msg=relation_added");
        exit;
    }

    // ==============================================================================
    // ZONE 5 : MISE A JOUR DES INFOS (UPDATE GLOBAL)
    // ==============================================================================
    if ($action === 'update') {
        
        // 1. Construction du tableau des Parents
        $parentsArray = [];
        
        // Si le Parent 1 est rempli, on l'ajoute
        if (!empty($_POST['parent1_id'])) {
            $parentsArray[] = [
                'id' => new ObjectId($_POST['parent1_id']),
                'role' => $_POST['parent1_role'],
                'nature' => $_POST['parent1_nature']
            ];
        }

        // Si le Parent 2 est rempli, on l'ajoute
        if (!empty($_POST['parent2_id'])) {
            $parentsArray[] = [
                'id' => new ObjectId($_POST['parent2_id']),
                'role' => $_POST['parent2_role'],
                'nature' => $_POST['parent2_nature']
            ];
        }

        // 2. Envoi des modifications à MongoDB
        $collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => [
                'identite.nom' => $_POST['nom'],
                'identite.prenom' => $_POST['prenom'],
                'identite.sexe' => $_POST['sexe'],
                'identite.nationalite' => $_POST['nationalite'],
                'naissance.date' => $_POST['date_naissance'],
                'parents' => $parentsArray
            ]]
        );

        header("Location: modifier.php?id=$id&msg=update_ok");
        exit;
    }

} catch (Exception $e) { 
    die("Erreur technique : " . $e->getMessage()); 
}
?>