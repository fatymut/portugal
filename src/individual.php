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

        if ($role === 'parent') {
            $relation = [
                '_id' => 'rel_' . uniqid(),
                'type' => 'parent_enfant',
                'parent' => $id,
                'enfant' => $autre_id
            ];
        } else {
            $relation = [
                '_id' => 'rel_' . uniqid(),
                'type' => 'parent_enfant',
                'parent' => $autre_id,
                'enfant' => $id
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
            'date_divorce' => null
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
