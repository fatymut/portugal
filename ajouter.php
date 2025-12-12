<?php
// ==============================================================================
// ZONE 1 : LOGIQUE PHP (CONNEXION & RECUPERATION DONNEES)
// ==============================================================================
require 'vendor/autoload.php';

try {
    // 1. Connexion à MongoDB
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->familytree->individus;

    // 2. Récupération de TOUS les individus existants (pour les listes déroulantes Parents)
    // On trie par Nom puis Prénom pour que ce soit facile à trouver
    $cursor = $collection->find([], ['sort' => ['identite.nom' => 1, 'identite.prenom' => 1]]);
    
    // On convertit le curseur en un tableau PHP facile à utiliser
    $all = iterator_to_array($cursor);

} catch (Exception $e) {
    // En cas d'erreur (ex: pas de base de données), on met une liste vide pour ne pas faire planter la page
    $all = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une personne</title>
    </head>
<body class="bg-slate-100 font-sans text-slate-800">

    <?php 
    // ==============================================================================
    // ZONE 2 : INCLUSION DU HEADER (MENU NAVIGATION)
    // ==============================================================================
    include 'header.php'; 
    ?>

    <div class="max-w-3xl mx-auto py-10 px-4">
        
        <div class="bg-white rounded-xl shadow-lg p-8">
            
            <h1 class="text-2xl font-bold text-slate-700 mb-2 flex items-center gap-2">
                <i class="fas fa-user-plus text-emerald-500"></i> Ajouter une personne
            </h1>
            <p class="text-slate-500 text-sm mb-6">Créez une nouvelle fiche pour agrandir l'arbre.</p>

            <form action="traitement.php" method="POST" class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block font-bold text-sm mb-1 text-slate-600">Nom</label>
                        <input type="text" name="nom" required placeholder="Ex: Silva" 
                               class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-sm mb-1 text-slate-600">Prénom</label>
                        <input type="text" name="prenom" required placeholder="Ex: Maria" 
                               class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block font-bold text-sm mb-1 text-slate-600">Date de naissance</label>
                        <input type="date" name="date_naissance" required 
                               class="w-full border border-slate-300 p-2.5 rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-sm mb-1 text-slate-600">Sexe</label>
                        <select name="sexe" class="w-full border border-slate-300 p-2.5 rounded-lg outline-none bg-white">
                            <option value="M">Homme</option>
                            <option value="F">Femme</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-sm mb-1 text-slate-600">Nationalité</label>
                        <input type="text" name="nationalite" value="Portugaise" 
                               class="w-full border border-slate-300 p-2.5 rounded-lg outline-none">
                    </div>
                </div>

                <hr class="border-slate-200">

                <div>
                    <h3 class="font-bold text-blue-600 mb-4 flex items-center gap-2">
                        <i class="fas fa-users"></i> Qui sont ses parents ?
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div>
                            <label class="block font-bold text-sm mb-1 text-slate-600">Père</label>
                            <select name="pere_id" class="w-full border border-slate-300 p-2.5 rounded-lg outline-none cursor-pointer bg-white">
                                <option value="">-- Père inconnu --</option>
                                <?php foreach($all as $p): ?>
                                    <option value="<?= (string)$p['_id'] ?>">
                                        <?= $p['identite']['nom'] . " " . $p['identite']['prenom'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-sm mb-1 text-slate-600">Mère</label>
                            <select name="mere_id" class="w-full border border-slate-300 p-2.5 rounded-lg outline-none cursor-pointer bg-white">
                                <option value="">-- Mère inconnue --</option>
                                <?php foreach($all as $p): ?>
                                    <option value="<?= (string)$p['_id'] ?>">
                                        <?= $p['identite']['nom'] . " " . $p['identite']['prenom'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-lg py-3 rounded-lg shadow-md transition transform active:scale-[0.99]">
                        <i class="fas fa-check-circle mr-2"></i> Enregistrer la personne
                    </button>
                </div>

            </form>
        </div>
    </div>

</body>
</html>