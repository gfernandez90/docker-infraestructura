<?php
// src/public/index.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Requerimos dependencias core
require_once __DIR__ . '/../config/cas.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/debug.php'; // Tu nuevo helper de debug

$page = $_GET['page'] ?? 'dashboard';
// --- INTERCEPTAR ACCIONES POST ANTES DEL HTML ---
// Si la acción es guardar o actualizar, cargamos el controlador y detenemos la ejecución (exit)
// para que la redirección por header() funcione sin imprimir HTML.
if ($page === 'guardar_sistema') {
    require_once __DIR__ . '/../controllers/guardar_sistema.php';
    exit; 
}
if ($page === 'actualizar_sistema') {
    require_once __DIR__ . '/../controllers/actualizar_sistema.php';
    exit;
}
if ($page === 'eliminar_sistema') { // <-- NUEVO BLOQUE
    require_once __DIR__ . '/../controllers/eliminar_sistema.php';
    exit;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Infraestructura - DGEIP</title>
    <!-- Aquí es donde se carga el motor de estilos visuales -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 flex min-h-screen">

    <!-- Sidebar -->
    <?php include __DIR__ . '/../views/layouts/sidebar.php'; ?>

    <div class="flex-1 flex flex-col">
        <!-- Top Menu -->
        <?php include __DIR__ . '/../views/layouts/header.php'; ?>

        <!-- Contenido Central -->
        <main class="flex-1 p-6">
            <?php 
            // Enrutador: Decidir qué CONTROLADOR (o vista directa) cargar según la página
            switch ($page) {
                case 'configuraciones':
                    require_once __DIR__ . '/../controllers/ConfigController.php';
                    break;
                    
                case 'inbox':
                    require_once __DIR__ . '/../controllers/InboxController.php';
                    break;

                case 'dashboard':
                    require_once __DIR__ . '/../views/dashboard.php';
                    break;

                // Puedes agregar más "cases" a medida que migres otras secciones
                // case 'ver_incidente':
                //     require_once __DIR__ . '/../controllers/IncidenteController.php';
                //     break;

                default:
                    // Fallback automático por si aún no migraste la página al patrón MVC
                    $file = __DIR__ . "/../views/view_{$page}.php";
                    if (file_exists($file)) {
                        require_once $file;
                    } else {
                        // Intentar buscar sin el prefijo view_ (por si acaso)
                        $fileFallback = __DIR__ . "/../views/{$page}.php";
                        if (file_exists($fileFallback)) {
                            require_once $fileFallback;
                        } else {
                            echo "<h1 class='text-xl text-red-600 font-bold'>Página no encontrada (404)</h1>";
                        }
                    }
                    break;
            }
            ?>
        </main>
        
        <!-- Footer general (Si existe el archivo) -->
        <?php 
        $footer = __DIR__ . '/../views/layouts/footer.php';
        if(file_exists($footer)) {
            require_once $footer;
        }
        ?>
    </div>

</body>
</html>
