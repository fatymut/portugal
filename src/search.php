<?php
require '../config/mongo.php';
require __DIR__ . '/../vendor/autoload.php';

$q = $_GET['q'] ?? '';

$resultats = $db->individuals->find([
    '$or' => [
        ['nom' => ['$regex' => $q, '$options' => 'i']],
        ['prenom' => ['$regex' => $q, '$options' => 'i']]
    ]
]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Résultats de recherche</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6 font-sans">

<div class="max-w-3xl mx-auto bg-white rounded-xl shadow-lg p-6">

    <h1 class="text-2xl font-bold mb-4">Résultats pour : <span class="text-blue-600"><?= htmlspecialchars($q) ?></span></h1>

    <?php if ($resultats->isDead()): ?>
        <p class="text-gray-600">Aucun résultat trouvé.</p>
    <?php else: ?>
        <ul class="space-y-2">
        <?php foreach ($resultats as $individu): ?>
            <li>
                <a href="individual.php?id=<?= $individu['_id'] ?>"
                   class="block p-3 bg-gray-50 rounded-lg shadow hover:bg-blue-50 transition">
                   <?= htmlspecialchars($individu['prenom']) ?> <?= htmlspecialchars($individu['nom']) ?>
                </a>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="mt-6">
        <a href="../index.php" class="text-blue-600 hover:underline">← Retour à l’accueil</a>
    </div>

</div>

</body>
</html>
