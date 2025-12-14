<?php
require '../config/mongo.php'; // connexion à la base

// Export des individus
$individuals = $db->individuals->find()->toArray();
file_put_contents('individuals.json', json_encode($individuals, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Fichier individuals.json créé !<br>";

// Export des relations
$relations = $db->relations->find()->toArray();
file_put_contents('relations.json', json_encode($relations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Fichier relations.json créé !<br>";
