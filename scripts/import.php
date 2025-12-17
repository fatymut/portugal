<?php
require '/../config/mongo.php';

$individuals = json_decode(file_get_contents(__DIR__.'/../data/individuals.json'), true);
$relations = json_decode(file_get_contents(__DIR__.'/../data/relations.json'), true);

$db->individuals->deleteMany([]);
$db->relations->deleteMany([]);

$db->individuals->insertMany($individuals);
$db->relations->insertMany($relations);

echo "Import terminé avec succès\n";
