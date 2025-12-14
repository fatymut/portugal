<?php
require '../config/mongo.php';

$individuals = iterator_to_array($db->individuals->find());
$relations   = iterator_to_array($db->relations->find());

$people = [];
foreach ($individuals as $p) {
    $people[$p['_id']] = $p;
}

// Couples mariés
$couples = [];
foreach ($relations as $r) {
    if ($r['type'] === 'couple' && ($r['statut'] ?? 'marie') === 'marie') {
        $couples[$r['personne1']] = $r['personne2'];
        $couples[$r['personne2']] = $r['personne1'];
    }
}

// Parent -> enfants
$tree = [];
$hasParent = [];
foreach ($relations as $r) {
    if ($r['type'] === 'parent_enfant') {
        $tree[$r['parent']][] = $r['enfant'];
        $hasParent[$r['enfant']] = true;
    }
}

// Racines = personnes sans parents
$roots = [];
foreach ($people as $id => $p) {
    if (!isset($hasParent[$id])) {
        $roots[] = $id;
    }
}

$rendered = [];

// =========================
// AFFICHAGE RÉCURSIF
// =========================
function renderNode($id, $people, $tree, $couples, &$rendered) {
    if (isset($rendered[$id])) return;
    $rendered[$id] = true;

    $p = $people[$id];
    $spouse = $couples[$id] ?? null;

    echo "<div class='flex flex-col items-center'>";

    // Affichage couple marié
    if ($spouse && !isset($rendered[$spouse])) {
        $rendered[$spouse] = true;
        $s = $people[$spouse];

        echo "<div class='flex gap-6 items-center mb-4'>";
        foreach ([$p, $s] as $person) {
            echo "<div class='bg-yellow-50 border-2 border-yellow-400 rounded-xl px-6 py-3 shadow-md text-center min-w-[180px] hover:shadow-xl transition'>
                    <p class='font-bold text-lg'>{$person['prenom']} {$person['nom']}</p>
                    <p class='text-sm text-gray-600'>{$person['date_naissance']}</p>"
                    .(!empty($person['date_deces'])?"<p class='text-sm text-red-600'>⚰ {$person['date_deces']}</p>":"").
                  "</div>";
            if ($person === $p) {
                echo "<span class='text-yellow-600 text-xl font-bold flex items-center'>💍</span>";
            }
        }
        echo "</div>";
    }
    // Affichage personne seule
    elseif (!$spouse || isset($rendered[$spouse])) {
        echo "<div class='bg-white border-2 border-gray-300 rounded-xl px-6 py-3 shadow-md text-center min-w-[180px] hover:shadow-xl transition mb-4'>
                <p class='font-bold text-lg'>{$p['prenom']} {$p['nom']}</p>
                <p class='text-sm text-gray-600'>{$p['date_naissance']}</p>"
                .(!empty($p['date_deces'])?"<p class='text-sm text-red-600'>⚰ {$p['date_deces']}</p>":"").
              "</div>";
    }

    // Enfants
    $children = [];
    if ($spouse) {
        foreach ($tree[$id] ?? [] as $c) $children[] = $c;
        foreach ($tree[$spouse] ?? [] as $c) $children[] = $c;
    } else {
        $children = $tree[$id] ?? [];
    }

    if (!empty($children)) {
        echo "<div class='w-px h-8 bg-gray-400 my-2'></div>";
        echo "<div class='flex gap-10 bg-white/10 p-2 rounded-md'>"; // fond léger pour enfants/frères-sœurs
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
<h1 class="text-4xl font-bold text-center py-6 bg-white shadow">🌳 Arbre généalogique</h1>
<div class="mt-6">
    <a href="../index.php" class="text-blue-600 hover:underline">← Retour à l’accueil</a>
</div>
<div class="overflow-auto w-screen h-[calc(100vh-96px)] p-10">
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
