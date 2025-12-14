<?php
require '../config/mongo.php';

/* =========================
   RÉCUPÉRATION DES DONNÉES
   ========================= */

$individuals = iterator_to_array($db->individuals->find());
$relations   = iterator_to_array($db->relations->find());

/* Index des personnes */
$people = [];
foreach ($individuals as $p) {
    $people[$p['_id']] = $p;
}

/* Couples (mariés ou divorcés) */
$couples = [];
foreach ($relations as $r) {
    if ($r['type'] === 'couple') {
        $couples[$r['personne1']] = [
            'spouse' => $r['personne2'],
            'statut' => $r['statut'] ?? 'marie'
        ];
        $couples[$r['personne2']] = [
            'spouse' => $r['personne1'],
            'statut' => $r['statut'] ?? 'marie'
        ];
    }
}

/* Parent -> enfants */
$tree = [];
$hasParent = [];
foreach ($relations as $r) {
    if ($r['type'] === 'parent_enfant') {
        $tree[$r['parent']][] = $r['enfant'];
        $hasParent[$r['enfant']] = true;
    }
}

/* Racines */
$roots = [];
foreach ($people as $id => $p) {
    if (!isset($hasParent[$id])) {
        $roots[] = $id;
    }
}

$rendered = [];

/* =========================
   AFFICHAGE RÉCURSIF
   ========================= */
function renderNode($id, $people, $tree, $couples, &$rendered) {
    if (isset($rendered[$id])) {
        return;
    }
    $rendered[$id] = true;

    $p = $people[$id];
    $spouse = null;
    $statut = null;

    if (isset($couples[$id])) {
        $spouse = $couples[$id]['spouse'];
        $statut = $couples[$id]['statut'];
    }

    echo "<div class='flex flex-col items-center'>";

    /* =========================
       AFFICHAGE COUPLE
       ========================= */
    if ($spouse !== null && !isset($rendered[$spouse])) {
        $rendered[$spouse] = true;
        $s = $people[$spouse];

        if ($statut === 'marie') {
            $style = "bg-yellow-50 border-yellow-400";
            $icon = "💍";
            $label = "Mariés";
        } else {
            $style = "bg-gray-100 border-gray-400";
            $icon = "💔";
            $label = "Divorcés";
        }

        echo "<div class='flex gap-6 items-center mb-4'>";
        foreach ([$p, $s] as $person) {
            echo "<div class='$style border-2 rounded-xl px-6 py-3 shadow-md text-center min-w-[180px]'>
                    <p class='font-bold text-lg'>{$person['prenom']} {$person['nom']}</p>
                    <p class='text-sm text-gray-600'>{$person['date_naissance']}</p>";

            if (!empty($person['date_deces'])) {
                echo "<p class='text-sm text-red-600'>⚰ {$person['date_deces']}</p>";
            }

            echo "</div>";
        }

        echo "<span class='font-bold text-lg mx-2'>$icon</span>";
        echo "</div>";
        echo "<p class='text-sm text-gray-600 mb-2'>$label</p>";
    }

    /* =========================
       PERSONNE SEULE
       ========================= */
    if ($spouse === null) {
        echo "<div class='bg-white border-2 border-gray-300 rounded-xl px-6 py-3 shadow-md text-center min-w-[180px] mb-4'>
                <p class='font-bold text-lg'>{$p['prenom']} {$p['nom']}</p>
                <p class='text-sm text-gray-600'>{$p['date_naissance']}</p>";

        if (!empty($p['date_deces'])) {
            echo "<p class='text-sm text-red-600'>⚰ {$p['date_deces']}</p>";
        }

        echo "</div>";
    }

    /* =========================
       ENFANTS (FRÈRES / SŒURS)
       ========================= */
    $children = [];

    if ($spouse !== null) {
        if (isset($tree[$id])) {
            foreach ($tree[$id] as $c) {
                $children[] = $c;
            }
        }
        if (isset($tree[$spouse])) {
            foreach ($tree[$spouse] as $c) {
                $children[] = $c;
            }
        }
    } else {
        if (isset($tree[$id])) {
            $children = $tree[$id];
        }
    }

    if (!empty($children)) {
        echo "<div class='w-px h-8 bg-gray-400 my-2'></div>";
        echo "<div class='flex gap-10 bg-blue-50 p-4 rounded-lg'>";
        foreach ($children as $child) {
            renderNode($child, $people, $tree, $couples, $rendered);
        }
        echo "</div>";
    }

    echo "</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Arbre généalogique</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<h1 class="text-4xl font-bold text-center py-6 bg-white shadow">
    🌳 Arbre généalogique
</h1>

<div class="mt-6 ">
    <a href="../index.php" class="text-blue-600 hover:underline">← Retour à l’accueil</a>
</div>

<div class="overflow-auto w-screen h-[calc(100vh-120px)] p-10">
    <div class="flex justify-center gap-20 min-w-max">
        <?php
        $rendered = [];
        foreach ($roots as $root) {
            renderNode($root, $people, $tree, $couples, $rendered);
        }
        ?>
    </div>
</div>

</body>
</html>
