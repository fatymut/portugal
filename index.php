<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Généalogie Silva</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-sans">

    <!-- Header -->
    <header class="bg-blue-600 text-white py-6 shadow-md mb-8">
        <h1 class="text-3xl font-bold text-center">Famille Silva – Lisbonne</h1>
    </header>

    <main class="max-w-3xl mx-auto px-4">

        <!-- Recherche -->
        <form action="src/search.php" method="get" class="flex gap-2 mb-6">
            <input type="text" name="q" placeholder="Nom ou prénom" 
                   class="flex-1 border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                Rechercher
            </button>
        </form>

        <!-- Menu -->
        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <li>
                <a href="src/stats.php" 
                   class="block bg-white rounded-xl shadow-md p-4 text-center font-semibold text-gray-700 hover:bg-blue-50 transition">
                    Statistiques
                </a>
            </li>
            <li>
                <a href="src/incoherences.php" 
                   class="block bg-white rounded-xl shadow-md p-4 text-center font-semibold text-gray-700 hover:bg-blue-50 transition">
                    Incohérences
                </a>
            </li>
            <li>
                <a href="src/add.php" 
                   class="block bg-white rounded-xl shadow-md p-4 text-center font-semibold text-gray-700 hover:bg-blue-50 transition">
                    Ajouter une personne
                </a>
            </li>
            <li>
                <a href="src/tree.php" 
                   class="block bg-white rounded-xl shadow-md p-4 text-center font-semibold text-gray-700 hover:bg-blue-50 transition">
                    Arbre généalogique
                </a>
            </li>
        </ul>

    </main>

</body>
</html>
