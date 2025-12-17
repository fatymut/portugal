<?php
require '../config/mongo.php';


//traitement form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = [
        '_id' => 'ind_' . uniqid(),
        'prenom' => $_POST['prenom'],
        'nom' => $_POST['nom'],
        'date_naissance' => $_POST['date_naissance'],
        'sexe' => $_POST['sexe'],
        'lieu_naissance' => $_POST['lieu_naissance'] ?: null,
        'nationalite' => $_POST['nationalite'] ?: null,
        'profession' => $_POST['profession'] ?: null,
        'date_deces' => $_POST['date_deces'] ?: null
    ];

    $db->individuals->insertOne($data);

    // Redirection vers la fiche de la personne créée
    header("Location: individual.php?id=" . $data['_id']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouvelle personne</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
       
<body class="bg-gray-100 min-h-screen p-6">
<div class="max-w-xl mx-auto bg-white shadow-lg rounded-xl p-8">

<h1 class="text-2xl font-bold mb-6"> Ajouter une nouvelle personne</h1>

<form method="post" class="space-y-4">

    <div>
        <label class="block text-sm font-medium">Prénom</label>
        <input class="w-full border rounded-lg p-2" type="text" name="prenom" required>
    </div>

    <div>
        <label class="block text-sm font-medium">Nom</label>
        <input class="w-full border rounded-lg p-2" type="text" name="nom" required>
    </div>

    <div>
        <label class="block text-sm font-medium">Date de naissance</label>
        <input class="w-full border rounded-lg p-2" type="date" name="date_naissance" >
    </div>

    <div>
        <label class="block text-sm font-medium">Date de décès</label>
        <input class="w-full border rounded-lg p-2" type="date" name="date_deces">
    </div>

        <div>
        <label class="block text-sm font-medium">Profession</label>
        <input class="w-full border rounded-lg p-2" type="text" name="profession" required>
    </div>
            <div>
        <label class="block text-sm font-medium">Lieu de naissance</label>
        <input class="w-full border rounded-lg p-2" type="text" name="lieu_naissance" required>
    </div>
                <div>
        <label class="block text-sm font-medium">Nationalité</label>
        <input class="w-full border rounded-lg p-2" type="text" name="nationalite" required>
    </div>

    <div>
        <label class="block text-sm font-medium">Sexe</label>
        <select class="w-full border rounded-lg p-2" name="sexe" required>
            <option value="">-- Choisir --</option>
            <option value="M">Homme</option>
            <option value="F">Femme</option>
        </select>
    </div>

    <div class="flex justify-between">
        <a href="../index.php" class="text-gray-600 hover:underline">
            ← Retour
        </a>

        <button class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
            Créer la personne
        </button>
    </div>
</form>

</div>
</body>
</html>
