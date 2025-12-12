<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Roboto', 'sans-serif'],
                }
            }
        }
    }
</script>

<header class="fixed top-0 w-full z-50 bg-slate-800 text-white shadow-md h-[70px] flex items-center justify-between px-8 font-sans">
    
    <a href="visualiser.php" class="flex items-center gap-3 text-xl font-bold hover:text-gray-300 transition-colors duration-200">
        <i class="fas fa-tree text-green-400"></i> <span>FamilyTree Pro</span>
    </a>

    <nav class="flex items-center gap-6">
        
        <a href="visualiser.php" class="flex items-center gap-2 text-gray-300 hover:text-white font-medium transition-colors duration-200">
            <i class="fas fa-project-diagram"></i> 
            <span class="hidden sm:inline">Visualiser</span>
        </a>

        <a href="ajouter.php" class="flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-lg font-semibold shadow-sm transition-all transform active:scale-95 duration-200">
            <i class="fas fa-plus-circle"></i> 
            <span>Ajouter</span>
        </a>

    </nav>

</header>

<div class="h-[70px]"></div>