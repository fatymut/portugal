<?php
require '../config/mongo.php';

$relations = $db->relations->find(['type' => 'parent_enfant']);

foreach ($relations as $rel) {
    $parent = $db->individuals->findOne(['_id' => $rel['parent']]);
    $enfant = $db->individuals->findOne(['_id' => $rel['enfant']]);

    if (
        $parent['date_naissance'] &&
        $enfant['date_naissance'] &&
        $parent['date_naissance'] > $enfant['date_naissance']
    ) {
        echo "Erreur : {$enfant['prenom']} né avant {$parent['prenom']}<br>";
    }
}
