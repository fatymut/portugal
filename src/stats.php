<?php
require '../config/mongo.php';

echo "Nombre d'individus : " . $db->individuals->countDocuments() . "<br>";
echo "Sans date de naissance : " .
     $db->individuals->countDocuments(['date_naissance' => null]) . "<br>";
echo "Nombre de relations : " . $db->relations->countDocuments() . "<br>";

echo "Couple Mariés : " .
     $db->relations->countDocuments(['type' => 'couple', 'date_mariage' => ['$ne' => '']]) . "<br>";

$couplesDivorces = $db->relations->countDocuments([
    'type' => 'couple',
    'date_divorce' => ['$ne' => null]
]);
echo "Couple Divorcés : " . $couplesDivorces . "<br>";

echo "Nombre Homme : " .
     $db->individuals->countDocuments(['sexe' => 'M']) . "<br>";

     echo "Nombre Femme : " .
     $db->individuals->countDocuments(['sexe' => 'F']) . "<br>";

     $villes = $db->individuals->aggregate([
    [
        '$group' => [
            '_id' => '$lieu_naissance',   // clé de regroupement : la ville
            'total' => ['$sum' => 1]     // compter le nombre
        ]
    ]
]);

echo "Par ville :<br>";
foreach ($villes as $ville) {
    echo $ville['_id'] . " : " . $ville['total'] . "<br>";
}

     