<?php
// On charge l'autoloader de Composer
require 'vendor/autoload.php';

use Faker\Factory;
use MongoDB\BSON\ObjectId;

// 1. CONNEXION
try {
    $client = new MongoDB\Client("mongodb://localhost:27017");
    $collection = $client->familytree->individus;
    
    // 2. NETTOYAGE : On vide la collection pour repartir propre
    $collection->drop();
    echo "🧹 Base de données nettoyée.\n";

} catch (Exception $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Initialisation de Faker (Langue Portugaise)
$faker = Factory::create('pt_PT');

echo "--- Génération de la Famille Silva (Schema V2) ---\n";

// --- ETAPE 1 : LES GRANDS-PARENTS (Manuel & Maria) ---
// On pré-génère les IDs pour pouvoir les lier entre eux (Mariage)
$idPapi = new ObjectId();
$idMamie = new ObjectId();

// Le Grand-Père
$papi = [
    '_id' => $idPapi,
    'identite' => [
        'nom' => 'Silva',
        'prenom' => 'Manuel',
        'sexe' => 'M',
        'nationalite' => 'Portugaise'
    ],
    'naissance' => [
        'date' => '1950-05-12',
        'lieu' => 'Lisboa'
    ],
    'parents' => [],
    // AJOUT DE LA RELATION (MARIAGE)
    'relations' => [
        [
            'partner_id' => $idMamie,
            'type' => 'Mariage',
            'date_debut' => '1973-06-20',
            'date_fin' => null,
            'statut' => 'Actif'
        ]
    ]
];

// La Grand-Mère
$mamie = [
    '_id' => $idMamie,
    'identite' => [
        'nom' => 'Silva', // Elle a pris le nom (traditionnel) ou gardé le sien
        'prenom' => 'Maria',
        'sexe' => 'F',
        'nationalite' => 'Portugaise'
    ],
    'naissance' => [
        'date' => '1952-08-14',
        'lieu' => 'Porto'
    ],
    'parents' => [],
    // RELATION RECIPROQUE
    'relations' => [
        [
            'partner_id' => $idPapi,
            'type' => 'Mariage',
            'date_debut' => '1973-06-20',
            'date_fin' => null,
            'statut' => 'Actif'
        ]
    ]
];

$collection->insertOne($papi);
$collection->insertOne($mamie);
echo "👴👵 Grands-parents créés et mariés.\n";


// --- ETAPE 2 : LE PÈRE (João) ---
// Fils de Manuel et Maria
$idPapa = new ObjectId();

$papa = [
    '_id' => $idPapa,
    'identite' => [
        'nom' => 'Silva',
        'prenom' => 'João',
        'sexe' => 'M',
        'nationalite' => 'Portugaise'
    ],
    'naissance' => [
        'date' => '1975-03-10',
        'lieu' => 'Coimbra'
    ],
    // COMPATIBILITÉ V2 : On ajoute 'role' et 'nature'
    'parents' => [
        [
            'id' => $idPapi,
            'role' => 'Pere',
            'nature' => 'Biologique'
        ],
        [
            'id' => $idMamie,
            'role' => 'Mere',
            'nature' => 'Biologique'
        ]
    ],
    'relations' => []
];

$collection->insertOne($papa);
echo "👨 Père (João) créé.\n";


// --- ETAPE 3 : L'ENFANT (Cristiano) ---
// Fils de João (Mère inconnue pour l'instant)
$enfant = [
    'identite' => [
        'nom' => 'Silva',
        'prenom' => 'Cristiano',
        'sexe' => 'M',
        'nationalite' => 'Portugaise'
    ],
    'naissance' => [
        'date' => '2005-08-20',
        'lieu' => 'Funchal'
    ],
    'parents' => [
        [
            'id' => $idPapa,
            'role' => 'Pere',
            'nature' => 'Biologique'
        ]
    ],
    'relations' => []
];

$collection->insertOne($enfant);
echo "👦 Enfant (Cristiano) créé.\n";

echo "\n✅ BASE DE DONNÉES RÉGÉNÉRÉE AVEC SUCCÈS !\n";
echo "Allez sur visualiser.php pour voir le résultat.\n";
?>