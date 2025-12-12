<?php
// Fichier : index.php (CORRIGÉ et ROBUSTE)
require 'db.php';
use MongoDB\BSON\Regex;

// Fonction pour afficher l'arbre proprement
function afficherArbre($id, $col, $niveau = 0) {
    $p = $col->findOne(['_id' => $id]);
    if (!$p) return;
    
    $prefix = str_repeat("    ", $niveau);
    echo $prefix . "|__ " . $p['identite']['prenom'] . " " . $p['identite']['nom'];
    // On gère le cas où la date n'existe pas
    $date = isset($p['naissance']['date']) ? substr($p['naissance']['date'], 0, 4) : '?';
    echo " (" . $date . ")\n";

    if (!empty($p['parents'])) {
        foreach ($p['parents'] as $lien) {
            // Cette ligne gère les deux formats possibles (ID simple ou ID dans un tableau)
            $parentId = is_array($lien) ? ($lien['id'] ?? null) : $lien;
            if ($parentId) {
                afficherArbre($parentId, $col, $niveau + 1);
            }
        }
    }
}

$collection = $db->individus; // On s'assure d'avoir la collection

while (true) {
    echo "\n============================================\n";
    echo "   GÉNÉALOGIE PORTUGAL (FAMILLE SILVA)    \n";
    echo "============================================\n";
    echo "1. Rechercher une personne\n";
    echo "2. Statistiques (Villes)\n";
    echo "3. Détecter les incohérences\n";
    echo "4. Quitter\n";
    echo "Votre choix : ";
    
    $choix = trim(fgets(STDIN));

    if ($choix === '1') {
        echo "Nom à chercher : ";
        $q = trim(fgets(STDIN));
        
        $cursor = $collection->find([
            'identite.nom' => new Regex($q, 'i')
        ]);
        $results = $cursor->toArray();

        echo "\n" . count($results) . " résultat(s) trouvé(s) :\n";
        
        foreach ($results as $i => $doc) {
            echo "[$i] " . $doc['identite']['prenom'] . " " . $doc['identite']['nom'];
            echo " (Né(e) le " . ($doc['naissance']['date'] ?? '?') . ")\n";
            
            // CORRECTION DU BUG ICI :
            // On vérifie d'abord si le tableau 'parents' n'est pas vide
            if (!empty($doc['parents'])) {
                foreach ($doc['parents'] as $parent) {
                    // On gère l'affichage de l'ID proprement
                    $pid = is_array($parent) ? $parent['id'] : $parent;
                    echo "    -> Enfant de (ID) : " . $pid . "\n";
                }
            } else {
                echo "    -> (Ancêtre : Pas de parents connus)\n";
            }
        }

        if (count($results) > 0) {
            echo "\nVoir l'arbre d'un individu ? (Tapez le numéro ou Entrée) : ";
            $sel = trim(fgets(STDIN));
            if (is_numeric($sel) && isset($results[$sel])) {
                echo "\n--- ARBRE ASCENDANT ---\n";
                afficherArbre($results[$sel]['_id'], $collection);
            }
        }

    } elseif ($choix === '2') {
        // Statistiques
        $pipeline = [
            ['$group' => ['_id' => '$naissance.lieu', 'total' => ['$sum' => 1]]],
            ['$sort' => ['total' => -1]],
            ['$limit' => 5]
        ];
        $villes = $collection->aggregate($pipeline);
        echo "\n--- Top Villes ---\n";
        foreach ($villes as $v) {
            echo $v['_id'] . " : " . $v['total'] . "\n";
        }

    } elseif ($choix === '3') {
        // Incohérences
        echo "\nVérification des dates...\n";
        $cursor = $collection->find();
        $ok = true;
        foreach ($cursor as $doc) {
            if (empty($doc['parents'])) continue;
            $dateEnfant = $doc['naissance']['date'] ?? null;
            if (!$dateEnfant) continue;

            foreach ($doc['parents'] as $pLink) {
                $pid = is_array($pLink) ? $pLink['id'] : $pLink;
                $parent = $collection->findOne(['_id' => $pid]);
                if ($parent && ($parent['naissance']['date'] ?? '') >= $dateEnfant) {
                    echo "[ERREUR] " . $doc['identite']['prenom'] . " est né avant son parent !\n";
                    $ok = false;
                }
            }
        }
        if ($ok) echo "Aucune incohérence trouvée.\n";

    } elseif ($choix === '4') {
        exit("Adeus !\n");
    }
}
?>