<?php
// ==============================================================================
// ZONE 1 : LOGIQUE PHP (RECUPERATION DONNEES)
// ==============================================================================
require 'vendor/autoload.php';
use MongoDB\BSON\ObjectId;

// 1. Vérification de l'ID
$id = '';
if (isset($_GET['id'])) {
    $id = $_GET['id'];
}
if (empty($id)) {
    header("Location: visualiser.php");
    exit;
}

try {
    // 2. Connexion
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->familytree->individus; 
    
    // 3. Récupération de l'individu à modifier
    $ind = $collection->findOne(['_id' => new ObjectId($id)]);
    
    if (!$ind) {
        die("Erreur : Individu introuvable.");
    }

    // 4. Récupération de tous les individus (pour les listes déroulantes)
    $cursor = $collection->find([], ['sort' => ['identite.nom' => 1]]);
    $all = iterator_to_array($cursor);

    // 5. Préparation des variables pour les parents (éviter les ternaires dans le HTML)
    $parent1_id = '';
    $parent1_role = '';
    $parent1_nature = '';

    if (isset($ind['parents'][0])) {
        $parent1_id = (string)$ind['parents'][0]['id'];
        if (isset($ind['parents'][0]['role'])) $parent1_role = $ind['parents'][0]['role'];
        if (isset($ind['parents'][0]['nature'])) $parent1_nature = $ind['parents'][0]['nature'];
    }

    $parent2_id = '';
    $parent2_role = '';
    $parent2_nature = '';

    if (isset($ind['parents'][1])) {
        $parent2_id = (string)$ind['parents'][1]['id'];
        if (isset($ind['parents'][1]['role'])) $parent2_role = $ind['parents'][1]['role'];
        if (isset($ind['parents'][1]['nature'])) $parent2_nature = $ind['parents'][1]['nature'];
    }

} catch (Exception $e) { 
    die($e->getMessage()); 
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Avancé</title>
    <?php include 'header.php'; ?>
</head>
<body class="bg-slate-100 p-6 md:p-10 font-sans">

<div class="max-w-4xl mx-auto bg-white p-8 rounded shadow-lg">
    
    <div class="flex justify-between items-center mb-8 border-b pb-4">
        <h1 class="text-3xl font-bold text-slate-800">
            Modifier : <?= $ind['identite']['prenom'] ?> <?= $ind['identite']['nom'] ?>
        </h1>
        <a href="action.php?action=delete&id=<?= $id ?>" onclick="return confirm('Irréversible. Continuer ?')" 
           class="text-red-600 border border-red-200 bg-red-50 hover:bg-red-600 hover:text-white px-4 py-2 rounded transition">
           <i class="fas fa-trash"></i> Supprimer
        </a>
    </div>

    <form action="action.php" method="POST" class="space-y-8">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="id" value="<?= $id ?>">

        <section>
            <h3 class="font-bold text-xl text-slate-700 mb-4 flex items-center gap-2">
                <i class="fas fa-id-card text-blue-500"></i> Identité & Genre
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <div>
                    <label class="block text-sm font-bold text-gray-600">Nom</label>
                    <input type="text" name="nom" value="<?= $ind['identite']['nom'] ?>" class="w-full border p-2 rounded">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-600">Prénom</label>
                    <input type="text" name="prenom" value="<?= $ind['identite']['prenom'] ?>" class="w-full border p-2 rounded">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-600">Date Naissance</label>
                    <input type="date" name="date_naissance" value="<?= $ind['naissance']['date'] ?>" class="w-full border p-2 rounded">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-gray-600">Genre / Sexe</label>
                    <select name="sexe" class="w-full border p-2 rounded">
                        <?php $sexe = $ind['identite']['sexe']; ?>
                        <option value="M" <?php if($sexe == 'M') echo 'selected'; ?>>Homme</option>
                        <option value="F" <?php if($sexe == 'F') echo 'selected'; ?>>Femme</option>
                        <option value="NB" <?php if($sexe == 'NB') echo 'selected'; ?>>Non-binaire</option>
                        <option value="Autre" <?php if($sexe == 'Autre') echo 'selected'; ?>>Autre</option>
                    </select>
                </div>

            </div>
        </section>

        <hr>

        <section>
            <h3 class="font-bold text-xl text-slate-700 mb-4 flex items-center gap-2">
                <i class="fas fa-users text-green-500"></i> Parents & Filiation
            </h3>
            
            <div class="bg-green-50 p-4 rounded border border-green-100 grid gap-6">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-green-800">Parent 1 (Nom)</label>
                        <select name="parent1_id" class="w-full border p-2 rounded">
                            <option value="">-- Aucun --</option>
                            <?php foreach($all as $p): ?>
                                <?php if((string)$p['_id'] != $id): ?>
                                    <option value="<?= $p['_id'] ?>" <?php if((string)$p['_id'] == $parent1_id) echo 'selected'; ?>>
                                        <?= $p['identite']['nom'] ?> <?= $p['identite']['prenom'] ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-800">Rôle social</label>
                        <select name="parent1_role" class="w-full border p-2 rounded">
                            <option value="Pere" <?php if($parent1_role == 'Pere') echo 'selected'; ?>>Père</option>
                            <option value="Mere" <?php if($parent1_role == 'Mere') echo 'selected'; ?>>Mère</option>
                            <option value="Parent" <?php if($parent1_role == 'Parent') echo 'selected'; ?>>Parent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-800">Nature du lien</label>
                        <select name="parent1_nature" class="w-full border p-2 rounded">
                            <option value="Biologique" <?php if($parent1_nature == 'Biologique') echo 'selected'; ?>>Biologique</option>
                            <option value="Adoptif" <?php if($parent1_nature == 'Adoptif') echo 'selected'; ?>>Adoptif</option>
                            <option value="Tuteur" <?php if($parent1_nature == 'Tuteur') echo 'selected'; ?>>Tuteur</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-green-800">Parent 2 (Nom)</label>
                        <select name="parent2_id" class="w-full border p-2 rounded">
                            <option value="">-- Aucun --</option>
                            <?php foreach($all as $p): ?>
                                <?php if((string)$p['_id'] != $id): ?>
                                    <option value="<?= $p['_id'] ?>" <?php if((string)$p['_id'] == $parent2_id) echo 'selected'; ?>>
                                        <?= $p['identite']['nom'] ?> <?= $p['identite']['prenom'] ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-800">Rôle social</label>
                        <select name="parent2_role" class="w-full border p-2 rounded">
                            <option value="Pere" <?php if($parent2_role == 'Pere') echo 'selected'; ?>>Père</option>
                            <option value="Mere" <?php if($parent2_role == 'Mere') echo 'selected'; ?>>Mère</option>
                            <option value="Parent" <?php if($parent2_role == 'Parent') echo 'selected'; ?>>Parent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-green-800">Nature du lien</label>
                        <select name="parent2_nature" class="w-full border p-2 rounded">
                            <option value="Biologique" <?php if($parent2_nature == 'Biologique') echo 'selected'; ?>>Biologique</option>
                            <option value="Adoptif" <?php if($parent2_nature == 'Adoptif') echo 'selected'; ?>>Adoptif</option>
                            <option value="Tuteur" <?php if($parent2_nature == 'Tuteur') echo 'selected'; ?>>Tuteur</option>
                        </select>
                    </div>
                </div>

            </div>
        </section>

        <button class="w-full bg-emerald-600 text-white font-bold py-3 rounded hover:bg-emerald-700">
            Enregistrer Identité & Parents
        </button>
    </form>

    <hr class="my-8 border-slate-300">

    <section>
        <h3 class="font-bold text-xl text-slate-700 mb-4 flex items-center gap-2">
            <i class="fas fa-heart text-pink-500"></i> Relations & Historique
        </h3>

        <div class="space-y-3 mb-6">
            <?php 
            // On gère la compatibilité ancien/nouveau format
            $relations = [];
            if (isset($ind['relations'])) {
                $relations = $ind['relations'];
            } elseif (isset($ind['mariages'])) {
                $relations = $ind['mariages'];
            }

            if (empty($relations)) {
                echo "<p class='text-gray-400 italic'>Aucune relation enregistrée.</p>";
            } else {
                foreach($relations as $rel) {
                    
                    // On récupère l'ID du partenaire
                    $pid = null;
                    if(isset($rel['partner_id'])) $pid = $rel['partner_id'];
                    elseif(isset($rel['id'])) $pid = $rel['id'];

                    if ($pid) {
                        $part = $collection->findOne(['_id' => $pid]);
                        
                        // Est-ce terminé ?
                        $isTermine = false;
                        if (!empty($rel['date_fin'])) $isTermine = true;
                        if (isset($rel['divorce']) && $rel['divorce'] == true) $isTermine = true;

                        // Style CSS conditionnel
                        $cssBorder = $isTermine ? 'border-gray-200 bg-gray-50 opacity-75' : 'border-pink-200 shadow-sm';
                        $cssIconBg = $isTermine ? 'bg-gray-400' : 'bg-pink-500';
                        ?>

                        <div class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded border <?= $cssBorder ?>">
                            
                            <div class="flex items-center gap-3">
                                <div class="<?= $cssIconBg ?> text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-lg"><?= $part['identite']['prenom'] ?> <?= $part['identite']['nom'] ?></p>
                                    <p class="text-sm text-gray-600">
                                        Type : <strong><?= $rel['type'] ?? 'Mariage' ?></strong> | 
                                        Du : <?= $rel['date_debut'] ?? '?' ?>
                                        
                                        <?php if($isTermine): ?>
                                            au <strong><?= $rel['date_fin'] ?? '?' ?></strong>
                                        <?php else: ?>
                                            <span class="text-green-600 font-bold">(En cours)</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>

                            <?php if(!$isTermine): ?>
                            <form action="action.php" method="POST" class="flex items-center gap-2 mt-2 md:mt-0">
                                <input type="hidden" name="action" value="end_relation">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="partner_id" value="<?= $pid ?>">
                                
                                <input type="date" name="date_fin" required class="border p-1 rounded text-sm">
                                <button class="bg-slate-600 hover:bg-slate-700 text-white px-3 py-1 rounded text-sm">Terminer</button>
                            </form>
                            <?php else: ?>
                                <span class="text-xs font-bold uppercase border border-gray-400 text-gray-500 px-2 py-1 rounded">Terminé</span>
                            <?php endif; ?>

                        </div>

                        <?php
                    }
                }
            }
            ?>
        </div>

        <div class="bg-pink-50 p-4 rounded border border-pink-100">
            <h4 class="font-bold text-pink-800 mb-2">Ajouter une nouvelle relation</h4>
            <form action="action.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-2">
                <input type="hidden" name="action" value="add_relation">
                <input type="hidden" name="id" value="<?= $id ?>">
                
                <select name="new_partner_id" required class="border p-2 rounded">
                    <option value="">-- Choisir partenaire --</option>
                    <?php foreach($all as $p): ?>
                        <?php if((string)$p['_id'] != $id): ?>
                            <option value="<?= $p['_id'] ?>">
                                <?= $p['identite']['nom'] ?> <?= $p['identite']['prenom'] ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                
                <select name="type_relation" class="border p-2 rounded">
                    <option value="Mariage">Mariage</option>
                    <option value="PACS">PACS</option>
                    <option value="Concubinage">Union Libre</option>
                </select>
                
                <input type="date" name="date_debut" required class="border p-2 rounded">
                
                <button class="bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 rounded">Ajouter</button>
            </form>
        </div>
    </section>

</div>
</body>
</html>