<?php
// ==============================================================================
// ZONE 1 : CONFIGURATION ET CONNEXION
// ==============================================================================
// On désactive l'affichage des erreurs HTML pour ne pas casser le format JSON
ini_set('display_errors', 0);
header('Content-Type: application/json');

require 'vendor/autoload.php';
use MongoDB\BSON\ObjectId;

try {
    // 1. Connexion à MongoDB (Mode tableau forcé pour la simplicité)
    $client = new MongoDB\Client("mongodb://localhost:27017", [], [
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']
    ]);
    $collection = $client->familytree->individus; 

    // 2. Vérification du paramètre d'entrée
    if (!isset($_GET['root_id'])) {
        echo json_encode(['error' => 'Aucun ID fourni']);
        exit;
    }

    $targetIdStr = (string)$_GET['root_id'];

    // ==============================================================================
    // ZONE 2 : RECUPERATION DE LA CIBLE ET DU POINT DE DEPART
    // ==============================================================================
    
    // On essaie de trouver la personne demandée
    try {
        $targetIndividu = $collection->findOne(['_id' => new ObjectId($targetIdStr)]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Format ID invalide']);
        exit;
    }

    if (!$targetIndividu) {
        echo json_encode(['error' => 'Personne introuvable']);
        exit;
    }

    // STRATEGIE : On remonte d'un cran pour afficher les frères et sœurs.
    // Par défaut, la racine est la personne elle-même.
    $rootId = $targetIndividu['_id'];
    
    // Si la personne a des parents, on prend le premier parent trouvé comme racine.
    if (isset($targetIndividu['parents'])) {
        if (count($targetIndividu['parents']) > 0) {
            // On boucle pour trouver un ID valide
            foreach ($targetIndividu['parents'] as $p) {
                if (isset($p['id'])) {
                    $rootId = $p['id']; 
                    break; // On a trouvé un parent, on s'arrête là
                }
            }
        }
    }

    // ==============================================================================
    // ZONE 3 : FONCTION RECURSIVE (CONSTRUCTION DE L'ARBRE)
    // ==============================================================================
    function getFamilleTree($currentId, $collection, $originalTargetIdStr) {
        
        // 1. Récupération du document
        $doc = $collection->findOne(['_id' => $currentId]);
        if (!$doc) {
            return null;
        }

        // 2. Extraction des données (Sans ternaires)
        $nom = '?';
        if (isset($doc['identite']['nom'])) {
            $nom = $doc['identite']['nom'];
        }

        $prenom = '?';
        if (isset($doc['identite']['prenom'])) {
            $prenom = $doc['identite']['prenom'];
        }

        $date = '';
        if (isset($doc['naissance']['date'])) {
            $date = $doc['naissance']['date'];
        }

        // Classe CSS spéciale si c'est la personne qu'on a cliquée au début
        $cssClass = '';
        if ((string)$currentId === $originalTargetIdStr) {
            $cssClass = 'cible-actuelle';
        }

        // 3. Structure de base du noeud JSON
        $data = [
            'name' => $prenom . " " . $nom,
            'title' => $date,
            'className' => $cssClass,
            'spouses' => [], // On prépare le tableau des conjoints
            'children' => []
        ];

        // 4. RECUPERATION DES CONJOINTS (Schema V2)
        // On vérifie si le champ 'relations' existe (nouveau) ou 'mariages' (ancien)
        $relations = [];
        if (isset($doc['relations'])) {
            $relations = $doc['relations'];
        } elseif (isset($doc['mariages'])) {
            $relations = $doc['mariages'];
        }

        if (!empty($relations)) {
            foreach ($relations as $rel) {
                // On récupère l'ID du partenaire
                $partnerId = null;
                if (isset($rel['partner_id'])) {
                    $partnerId = $rel['partner_id'];
                } elseif (isset($rel['id'])) {
                    $partnerId = $rel['id'];
                }

                if ($partnerId) {
                    $partnerDoc = $collection->findOne(['_id' => $partnerId]);
                    if ($partnerDoc) {
                        // On ajoute le conjoint aux données
                        $pNom = $partnerDoc['identite']['nom'];
                        $pPrenom = $partnerDoc['identite']['prenom'];
                        $pDate = '';
                        if (isset($partnerDoc['naissance']['date'])) {
                            $pDate = $partnerDoc['naissance']['date'];
                        }

                        $data['spouses'][] = [
                            'name' => $pPrenom . " " . $pNom,
                            'title' => $pDate
                        ];
                    }
                }
            }
        }

        // 5. RECUPERATION DES ENFANTS (RECURSION)
        // On cherche tous les individus dont un des parents a l'ID actuel
        $enfantsCursor = $collection->find(['parents.id' => $currentId]);

        foreach ($enfantsCursor as $enfant) {
            // On rappelle la fonction pour l'enfant (ça descend dans l'arbre)
            $childNode = getFamilleTree($enfant['_id'], $collection, $originalTargetIdStr);
            
            if ($childNode) {
                $data['children'][] = $childNode;
            }
        }

        return $data;
    }

    // ==============================================================================
    // ZONE 4 : EXECUTION ET ENVOI DU JSON
    // ==============================================================================
    
    // On lance la construction depuis la racine (Le parent trouvé ou la cible)
    $treeData = getFamilleTree($rootId, $collection, $targetIdStr);
    
    // On renvoie le résultat au navigateur
    echo json_encode($treeData);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>