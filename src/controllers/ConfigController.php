<?php
// src/controllers/ConfigController.php
// (No necesitas session_start() ni require de db.php aquí porque ya se hicieron en index.php)

require_once __DIR__ . '/../models/ConfiguracionModel.php';

// 1. Instanciamos el modelo usando la conexión $pdo que viene de index.php
$configModel = new ConfiguracionModel($pdo);
$mensaje = "";

// 2. Procesamos el guardado si el usuario hizo submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['config'])) {
    $configModel->updateMultiple($_POST['config']);
    $mensaje = "Configuraciones actualizadas correctamente.";
}

// 3. Obtenemos los datos limpios desde el Modelo
$configs = $configModel->getAll();

// 4. Cargamos la Vista. La vista heredará las variables $configs y $mensaje
require_once __DIR__ . '/../views/view_configuraciones.php';