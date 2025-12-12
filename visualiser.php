<?php
// ==============================================================================
// ZONE 1 : CONFIGURATION ET CONNEXION BDD
// ==============================================================================
require 'vendor/autoload.php';
use MongoDB\BSON\ObjectId;

$treeData = null;

// Récupération de l'ID sélectionné (Sans ternaire)
$selectedId = '';
if (isset($_GET['root_id'])) {
    $selectedId = $_GET['root_id'];
}

try {
    // 1. Connexion MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017", [], [
        'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']
    ]);
    $collection = $client->familytree->individus; 
    
    // 2. Récupération de la liste pour le menu déroulant (Triée par Nom)
    $cursor = $collection->find([], ['sort' => ['identite.nom' => 1, 'identite.prenom' => 1]]);
    $individus = iterator_to_array($cursor);

    // ==============================================================================
    // ZONE 2 : LOGIQUE DE CONSTRUCTION DE L'ARBRE
    // ==============================================================================
    
    // Si un ID est sélectionné, on lance la construction
    if (!empty($selectedId)) {
        
        // A. Trouver la personne cible
        $targetObj = null;
        try {
            $targetObj = $collection->findOne(['_id' => new ObjectId($selectedId)]);
        } catch (Exception $e) { 
            $targetObj = null; 
        }

        if ($targetObj) {
            // B. Trouver le point de départ (Le parent le plus haut)
            $rootId = $targetObj['_id'];
            
            // Si la personne a des parents, on essaie de remonter
            if (isset($targetObj['parents']) && !empty($targetObj['parents'])) {
                if (isset($targetObj['parents'][0]['id'])) {
                    $rootId = $targetObj['parents'][0]['id'];
                }
            }

            // C. Fonction récursive qui construit l'arbre (Parents -> Enfants)
            function buildTreeData($currentId, $collection, $natureLienParenter = 'Biologique') {
                
                $doc = $collection->findOne(['_id' => $currentId]);
                if (!$doc) return null;

                // Extraction des données (Sans ternaires)
                $nom = '?';
                if (isset($doc['identite']['nom'])) $nom = $doc['identite']['nom'];

                $prenom = '?';
                if (isset($doc['identite']['prenom'])) $prenom = $doc['identite']['prenom'];

                $date = '';
                if (isset($doc['naissance']['date'])) $date = $doc['naissance']['date'];

                $data = [
                    'id' => (string)$doc['_id'],
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'date' => $date,
                    'nature_lien' => $natureLienParenter, // Pour le style pointillé
                    'spouses' => [], 
                    'children' => []
                ];

                // ---------------------------------------------------------
                // 1. GESTION DES RELATIONS (COUPLES)
                // ---------------------------------------------------------
                $relations = [];
                if (isset($doc['relations'])) {
                    $relations = $doc['relations'];
                } elseif (isset($doc['mariages'])) { // Compatibilité ancienne version
                    $relations = $doc['mariages'];
                }

                if (!empty($relations) && is_array($relations)) {
                    foreach ($relations as $rel) {
                        
                        // Récupération ID Partenaire
                        $partnerId = null;
                        if (isset($rel['partner_id'])) $partnerId = $rel['partner_id'];
                        elseif (isset($rel['id'])) $partnerId = $rel['id'];
                        
                        if ($partnerId) {
                            $partnerDoc = $collection->findOne(['_id' => $partnerId]);
                            
                            if ($partnerDoc) {
                                // Vérification si relation terminée
                                $isTermine = false;
                                if (!empty($rel['date_fin'])) $isTermine = true;
                                if (isset($rel['divorce']) && $rel['divorce'] == true) $isTermine = true;
                                
                                // Extraction données partenaire
                                $pNom = $partnerDoc['identite']['nom'];
                                $pPrenom = $partnerDoc['identite']['prenom'];
                                $pDate = '';
                                if (isset($partnerDoc['naissance']['date'])) $pDate = $partnerDoc['naissance']['date'];

                                $typeRelation = 'Couple';
                                if (isset($rel['type'])) $typeRelation = $rel['type'];

                                $data['spouses'][] = [
                                    'id' => (string)$partnerDoc['_id'],
                                    'nom' => $pNom,
                                    'prenom' => $pPrenom,
                                    'date' => $pDate,
                                    'type' => $typeRelation,
                                    'is_active' => !$isTermine // Inverse de terminé
                                ];
                            }
                        }
                    }
                    
                    // Tri : On met les conjoints ACTIFS en premier
                    usort($data['spouses'], function($a, $b) {
                        // Astuce pour trier sans ternaire (true=1, false=0)
                        $valA = $a['is_active'] ? 1 : 0;
                        $valB = $b['is_active'] ? 1 : 0;
                        return $valB - $valA; 
                    });
                }

                // ---------------------------------------------------------
                // 2. GESTION DES ENFANTS
                // ---------------------------------------------------------
                $enfants = $collection->find(['parents.id' => $currentId]);
                
                foreach ($enfants as $child) {
                    
                    // Déterminer la nature (Biologique ou Adoptif)
                    $nature = 'Biologique';
                    if (isset($child['parents'])) {
                        foreach ($child['parents'] as $p) {
                            if ((string)$p['id'] === (string)$currentId) {
                                if (isset($p['nature'])) {
                                    $nature = $p['nature'];
                                }
                                break;
                            }
                        }
                    }

                    // Appel Récursif
                    $childNode = buildTreeData($child['_id'], $collection, $nature);
                    if ($childNode) {
                        $data['children'][] = $childNode;
                    }
                }
                return $data;
            }

            // Lancement de la construction
            $treeData = buildTreeData($rootId, $collection);
        }
    }

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// ==============================================================================
// ZONE 3 : FONCTION D'AFFICHAGE HTML (RECURSIVE)
// ==============================================================================
function renderTreeHTML($node, $targetId) {
    if (!$node) return '';

    // Détermine si c'est la cible (pour surligner en jaune)
    $isTarget = false;
    if ($node['id'] === $targetId) {
        $isTarget = true;
    }
    
    // Classes CSS conditionnelles (Sans ternaires)
    $cardClass = 'bg-white border-slate-200';
    $headClass = 'bg-slate-700';
    
    if ($isTarget) {
        $cardClass = 'bg-amber-100 border-amber-400 ring-2 ring-amber-300';
        $headClass = 'bg-amber-500';
    }

    // Style de la LIGNE (Pointillé si adoptif)
    $liClass = '';
    if ($node['nature_lien'] === 'Adoptif' || $node['nature_lien'] === 'Tuteur') {
        $liClass = 'lien-adoptif';
    }

    // Début du LI
    $html = '<li class="'.$liClass.'">';
    
    // --- GROUPE (Personne + Conjoints) ---
    $html .= '<div class="inline-flex items-center gap-2 relative z-10 p-2">';

        // A. LA CARTE DE LA PERSONNE
        $html .= '<a href="modifier.php?id='.$node['id'].'" class="block border rounded-lg shadow-md overflow-hidden w-40 transition hover:scale-105 '.$cardClass.'">';
        $html .=    '<div class="'.$headClass.' text-white text-sm font-bold py-1 px-2 flex justify-between">';
        $html .=       '<span class="truncate">'.$node['prenom'].' '.$node['nom'].'</span>';
        $html .=       '<i class="fas fa-pen text-[10px] opacity-60 hover:opacity-100"></i>';
        $html .=    '</div>';
        $html .=    '<div class="p-2 text-xs text-slate-600 font-medium bg-white/50 flex justify-between">';
        $html .=       '<span>'.$node['date'].'</span>';
        
        // Petit badge si adoptif
        if($node['nature_lien'] == 'Adoptif') {
            $html .= '<i class="fas fa-child text-blue-400" title="Adoptif"></i>';
        }
        $html .=    '</div>';
        $html .= '</a>';

        // B. LES CONJOINTS
        if (!empty($node['spouses'])) {
            foreach ($node['spouses'] as $spouse) {
                
                // Différenciation Actif / Ex (Sans ternaires)
                $connector = '';
                $spouseStyle = '';
                $icon = '';

                if ($spouse['is_active']) {
                    // ACTUEL
                    $connector = '<div class="text-slate-300 font-bold text-lg">&mdash;</div>';
                    $spouseStyle = 'border-pink-300 bg-pink-50 ring-1 ring-pink-200';
                    $icon = '<i class="fas fa-heart text-pink-500 text-xs"></i>';
                } else {
                    // EX (Terminé)
                    $connector = '<div class="text-slate-200 text-lg px-1">/</div>'; 
                    $spouseStyle = 'border-slate-200 bg-slate-50 opacity-60 grayscale hover:grayscale-0';
                    $icon = '<i class="fas fa-history text-slate-400 text-xs"></i>';
                }

                $html .= $connector;

                $html .= '<a href="modifier.php?id='.$spouse['id'].'" class="block border rounded-lg shadow-sm overflow-hidden w-36 transition hover:scale-105 '.$spouseStyle.'">';
                $html .=    '<div class="bg-slate-500 text-white text-xs font-bold py-1 px-2 flex justify-between items-center">';
                $html .=       '<span class="truncate">'.$spouse['prenom'].'</span>';
                $html .=       $icon;
                $html .=    '</div>';
                $html .=    '<div class="p-1 text-[10px] text-slate-500 text-center">'.$spouse['date'].'</div>';
                $html .= '</a>';
            }
        }

    $html .= '</div>'; // Fin Flexbox

    // C. ENFANTS
    if (!empty($node['children'])) {
        $html .= '<ul>';
        foreach ($node['children'] as $child) {
            $html .= renderTreeHTML($child, $targetId);
        }
        $html .= '</ul>';
    }

    $html .= '</li>';
    return $html;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Visualisation Complète</title>
    <?php include 'header.php'; ?>
    
    <style>
        /* ==============================================================================
           ZONE 4 : CSS SPECIFIQUE A L'ARBRE
           ============================================================================== */
        
        /* Flexbox pour centrer l'arbre */
        .tree ul { padding-top: 20px; position: relative; display: flex; justify-content: center; }
        .tree li { float: left; text-align: center; list-style-type: none; position: relative; padding: 20px 5px 0 5px; }
        
        /* Les lignes connectrices (Pseudo-elements) */
        .tree li::before, .tree li::after {
            content: ''; position: absolute; top: 0; right: 50%;
            border-top: 2px solid #94a3b8; /* Couleur ligne standard (Slate-400) */
            width: 50%; height: 20px;
        }
        .tree li::after { right: auto; left: 50%; border-left: 2px solid #94a3b8; }
        
        /* Retouches pour premier/dernier élément afin d'arrondir les angles */
        .tree li:only-child::after, .tree li:only-child::before { display: none; }
        .tree li:only-child { padding-top: 0; }
        .tree li:first-child::before, .tree li:last-child::after { border: 0 none; }
        .tree li:last-child::before { border-right: 2px solid #94a3b8; border-radius: 0 5px 0 0; }
        .tree li:first-child::after { border-radius: 5px 0 0 0; }
        
        /* La ligne verticale descendant vers les enfants */
        .tree ul ul::before {
            content: ''; position: absolute; top: 0; left: 50%;
            border-left: 2px solid #94a3b8; width: 0; height: 20px;
        }

        /* STYLE POUR ENFANTS ADOPTIFS (Lignes en pointillés) */
        .tree li.lien-adoptif::before, 
        .tree li.lien-adoptif::after {
            border-top-style: dashed !important;
            border-left-style: dashed !important;
            border-color: #3b82f6; /* Bleu */
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800">

    <div class="min-h-screen py-10 px-4">
        
        <div class="bg-white rounded-xl shadow-lg p-6 max-w-4xl mx-auto border border-slate-200 mb-8 flex flex-col md:flex-row gap-4 items-center justify-between">
            <h2 class="text-xl font-bold text-slate-700 flex items-center gap-2">
                <i class="fas fa-sitemap text-blue-500"></i> Arbre Visuel
            </h2>
            <form action="visualiser.php" method="GET" class="flex gap-2 w-full md:w-auto">
                <select name="root_id" class="border p-2 rounded w-full md:w-64 bg-slate-50">
                    <option value="">-- Choisir une personne --</option>
                    <?php foreach ($individus as $ind): ?>
                        
                        <?php 
                        // Pré-calcul de la sélection (Sans ternaire)
                        $isSelected = '';
                        if ((string)$ind['_id'] == $selectedId) {
                            $isSelected = 'selected';
                        }
                        ?>

                        <option value="<?= (string)$ind['_id'] ?>" <?= $isSelected ?>>
                            <?= $ind['identite']['nom'] ?> <?= $ind['identite']['prenom'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded font-bold shadow hover:bg-blue-700">Voir</button>
            </form>
        </div>

        <div class="overflow-auto bg-white rounded-xl shadow-lg p-10 min-h-[600px] border border-slate-200 relative">
            
            <?php if ($treeData): ?>
                
                <div class="tree">
                    <ul>
                        <?= renderTreeHTML($treeData, $selectedId); ?>
                    </ul>
                </div>

                <div class="absolute top-4 right-4 bg-white/90 p-3 rounded border border-slate-200 text-xs shadow-sm space-y-2">
                    <div class="font-bold border-b pb-1 mb-1">Légende</div>
                    <div class="flex items-center gap-2"><span class="w-8 h-0 border-t-2 border-slate-400"></span> Lien Biologique</div>
                    <div class="flex items-center gap-2"><span class="w-8 h-0 border-t-2 border-dashed border-blue-500"></span> Lien Adoptif</div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 bg-pink-100 border border-pink-300"></div> Conjoint Actuel</div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 bg-slate-100 border border-slate-300 opacity-60"></div> Ex-Conjoint</div>
                </div>

            <?php else: ?>
                
                <div class="flex flex-col items-center justify-center h-full text-slate-300 mt-20">
                    <i class="fas fa-users text-8xl mb-4 opacity-20"></i>
                    <p class="text-xl font-medium">Sélectionnez un membre de la famille pour commencer.</p>
                </div>

            <?php endif; ?>

        </div>
    </div>

</body>
</html>