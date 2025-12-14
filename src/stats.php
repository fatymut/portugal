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