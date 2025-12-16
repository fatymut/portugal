<?php
require '../config/mongo.php';

/* =========================
   RÉCUPÉRATION DE L’INDIVIDU
   ========================= */
$id = $_GET['id'] ?? null;
$individu = null;

if ($id) {
    $individu = $db->individuals->findOne(['_id' => $id]);
}

/* =========================
   SUPPRESSION D’UNE RELATION
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_relation'])) {
    $relationId = $_POST['relation_id'];
    $db->relations->deleteOne(['_id' => $relationId]);
    header("Location: individual.php?id=$id");
    exit;
}

/* =========================
   GESTION DU DIVORCE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['divorce'])) {
    $relationId = $_POST['relation_id'];
    $db->relations->updateOne(
        ['_id' => $relationId],
        [
            '$set' => [
                'statut' => 'divorce',
                'date_divorce' => date('Y-m-d')
            ]
        ]
    );
    header("Location: individual.php?id=$id");
    exit;
}

/* =========================
   SUPPRESSION D’UN INDIVIDU
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_individu'])) {
    if ($id) {
        // Supprimer toutes les relations liées
        $db->relations->deleteMany([
            '$or' => [
                ['parent' => $id],
                ['enfant' => $id],
                ['personne1' => $id],
                ['personne2' => $id]
            ]
        ]);
        // Supprimer l’individu
        $db->individuals->deleteOne(['_id' => $id]);
    }
    header("Location: ../index.php");
    exit;
}

/* =========================
   AJOUT / MODIFICATION PERSONNE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_individu'])) {
    $data = [
        'prenom' => $_POST['prenom'],
        'nom' => $_POST['nom'],
        'date_naissance' => $_POST['date_naissance'],
        'sexe' => $_POST['sexe'],
        'date_deces' => $_POST['date_deces'] ?: null
    ];

    if ($id) {
        $db->individuals->updateOne(['_id' => $id], ['$set' => $data]);
    } else {
        $data['_id'] = 'ind_' . uniqid();
        $db->individuals->insertOne($data);
        $id = $data['_id'];
    }

    header("Location: individual.php?id=$id");
    exit;
}

/* =========================
   AJOUT D’UNE RELATION
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_relation'])) {
    $type = $_POST['type'];
    $autre_id = $_POST['autre_id'];

    if ($type === 'parent_enfant') {
        $role = $_POST['role_parent_enfant'];
        $commentaire = $_POST['commentaire'];

        if ($role === 'parent') {
            $relation = [
                '_id' => 'rel_' . uniqid(),
                'type' => 'parent_enfant',
                'parent' => $id,
                'enfant' => $autre_id,
                'commentaire' => $commentaire
            ];
        } else {
            $relation = [
                '_id' => 'rel_' . uniqid(),
                'type' => 'parent_enfant',
                'parent' => $autre_id,
                'enfant' => $id,
                'commentaire' => $commentaire
            ];
        }
    }

    if ($type === 'couple') {
        $relation = [
            '_id' => 'rel_' . uniqid(),
            'type' => 'couple',
            'personne1' => $id,
            'personne2' => $autre_id,
            'statut' => 'marie',
            'date_mariage' => $_POST['date_mariage'] ?: null,
            'date_divorce' => null,
            'commentaire' => $_POST['commentaire' ]?? null
        ];
    }

    $db->relations->insertOne($relation);
    header("Location: individual.php?id=$id");
    exit;
}

/* =========================
   RÉCUPÉRATION DES RELATIONS
   ========================= */
$relations = [];
if ($id) {
    $relations = $db->relations->find([
        '$or' => [
            ['parent' => $id],
            ['enfant' => $id],
            ['personne1' => $id],
            ['personne2' => $id]
        ]
    ]);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Généalogie</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-6">
<div class="mt-6">
    <a href="../index.php" class="text-blue-600 hover:underline">← Retour à l’accueil</a>
</div>

<div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg p-8">

<h1 class="text-3xl font-bold mb-2"><?= $individu['prenom'] ?? 'Nouvel individu' ?> <?= $individu['nom'] ?? '' ?></h1>

<?php if (!empty($individu['date_deces'])): ?>
<p class="text-red-600 font-semibold mb-4">Décédé le <?= $individu['date_deces'] ?></p>
<?php endif; ?>

<h2 class="text-xl font-semibold mb-4">Informations</h2>
<form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <input type="hidden" name="save_individu" value="1">
    <div>
        <label class="text-sm font-medium">Prénom</label>
        <input class="w-full border rounded-lg p-2" type="text" name="prenom" value="<?= $individu['prenom'] ?? '' ?>" required>
    </div>
    <div>
        <label class="text-sm font-medium">Nom</label>
        <input class="w-full border rounded-lg p-2" type="text" name="nom" value="<?= $individu['nom'] ?? '' ?>" required>
    </div>
    <div>
        <label class="text-sm font-medium">Date de naissance</label>
        <input class="w-full border rounded-lg p-2" type="date" name="date_naissance" value="<?= $individu['date_naissance'] ?? '' ?>" required>
    </div>
    <div>
        <label class="text-sm font-medium">Date de décès</label>
        <input class="w-full border rounded-lg p-2" type="date" name="date_deces" value="<?= $individu['date_deces'] ?? '' ?>">
    </div>
    <div>
        <label class="text-sm font-medium">Sexe</label>
        <select class="w-full border rounded-lg p-2" name="sexe">
<option value="M" <?php if (!empty($individu['sexe']) && $individu['sexe'] === 'M') { echo 'selected'; } ?>>Homme</option>
<option value="F" <?php if (!empty($individu['sexe']) && $individu['sexe'] === 'F') { echo 'selected'; } ?>>Femme</option>

        </select>
    </div>
    <div class="flex items-end">
        <button class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Enregistrer</button>
    </div>
</form>

<?php if ($id): ?>
<form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette personne ? Cette action est irréversible.')">
    <input type="hidden" name="delete_individu" value="1">
    <button class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 mb-6">Supprimer cette personne</button>
</form>

<h2 class="text-xl font-semibold mb-4">Relations</h2>
<ul class="space-y-2 mb-8">
<?php foreach ($relations as $r): 
    $autreId = null;
    $role = '';
    $statut = $r['statut'] ?? 'marie';
    $commentaire = $r['commentaire'] ?? '';

    if ($r['type'] === 'parent_enfant') {
        if ($r['parent'] === $id) {
            $autreId = $r['enfant'];
            $role = 'enfant';
        } else {
            $autreId = $r['parent'];
            $role = 'parent';
        }
    }
    if ($r['type'] === 'couple') {
        $autreId = ($r['personne1'] === $id) ? $r['personne2'] : $r['personne1'];
        $role = ($statut === 'divorce') ? 'ex-conjoint' : 'conjoint';
    }
    $autre = $db->individuals->findOne(['_id' => $autreId]);
    $nomAutre = $autre ? $autre['prenom'].' '.$autre['nom'] : 'Inconnu';
?>
<li class="flex justify-between items-center border rounded-lg p-3">
    <span><strong><?= $role ?></strong> : <?= $nomAutre ?></span>
    <span><?= htmlspecialchars($commentaire) ?></span>
    <div class="flex gap-2">
        <?php if ($r['type'] === 'couple' && $statut === 'marie'): ?>
        <form method="post" class="inline">
            <input type="hidden" name="divorce" value="1">
            <input type="hidden" name="relation_id" value="<?= $r['_id'] ?>">
            <button class="bg-red-500 text-white px-4 py-1 rounded hover:bg-red-600">Divorcer</button>
        </form>
        <?php endif; ?>
        <form method="post" class="inline">
            <input type="hidden" name="delete_relation" value="1">
            <input type="hidden" name="relation_id" value="<?= $r['_id'] ?>">
            <button class="bg-gray-400 text-white px-4 py-1 rounded hover:bg-gray-500">Supprimer</button>
        </form>
    </div>
</li>
<?php endforeach; ?>
</ul>

<h3 class="text-lg font-semibold mb-3">Ajouter une relation</h3>
<form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <input type="hidden" name="save_relation" value="1">

    <div>
        <label class="text-sm font-medium">Type</label>
        <select id="relation_type" class="w-full border rounded-lg p-2" name="type" onchange="toggleParentChild()">
            <option value="parent_enfant">Parent / Enfant</option>
            <option value="couple">Couple</option>
        </select>
    </div>

    <div id="parent_child_choice" class="mt-2">
        <span class="text-sm font-medium">Rôle dans la relation :</span><br>
        <label><input type="radio" name="role_parent_enfant" value="parent" checked> Je suis le parent</label>
        <label class="ml-4"><input type="radio" name="role_parent_enfant" value="enfant"> Je suis l’enfant</label>
    </div>

    <div>
        <label class="text-sm font-medium">Date de mariage</label>
        <input class="w-full border rounded-lg p-2" type="date" name="date_mariage">
    </div>
        <div>
        <label class="text-sm font-medium">Commentaire</label>
        <textarea class="w-full border rounded-lg p-2" name="commentaire"></textarea>
    </div>

    <div class="md:col-span-2">
        <label class="text-sm font-medium">Autre personne</label>
        <select class="w-full border rounded-lg p-2" name="autre_id">
            <?php foreach ($db->individuals->find() as $p): ?>
                <?php if ($p['_id'] !== $id): ?>
                    <option value="<?= $p['_id'] ?>"><?= $p['prenom'].' '.$p['nom'] ?></option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="md:col-span-2">
        <button class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">Ajouter</button>
    </div>
</form>
<?php endif; ?>
</div>

<script>
function toggleParentChild() {
    const type = document.getElementById('relation_type').value;
    document.getElementById('parent_child_choice').style.display = (type === 'parent_enfant') ? 'block' : 'none';
}
toggleParentChild();
</script>

</body>
</html>
