<?php
// Mostrar errores de PHP directamente en pantalla
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config/cas.php';

$page = $_GET['page'] ?? 'dashboard';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Infraestructura - DGEIP</title>
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
                $file = __DIR__ . "/../views/{$page}.php";
                if (file_exists($file)) {
                    include $file;
                } else {
                    echo "<h1 class='text-xl text-red-600 font-bold'>Página no encontrada</h1>";
                }
            ?>
        </main>
    </div>

</body>
</html>
