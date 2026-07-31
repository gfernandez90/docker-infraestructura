<?php
// src/controllers/InboxController.php

require_once __DIR__ . '/../models/TareaModel.php';

// 1. Recoger variables de la URL (GET)
$tab = $_GET['tab'] ?? 'todas';
$mostrarCerrados = isset($_GET['cerrados']) && $_GET['cerrados'] === '1';

// 2. Instanciar el modelo usando la conexión $pdo (que ya viene de index.php)
$tareaModel = new TareaModel($pdo);

// 3. Obtener los datos (estas son las variables que le faltaban a tu vista)
$tickets = $tareaModel->getTickets($tab, $mostrarCerrados);
$counts = $tareaModel->getCounts($mostrarCerrados);

// 4. Renderizar la vista (la vista ahora puede usar $tab, $mostrarCerrados, $tickets y $counts)
require_once __DIR__ . '/../views/view_inbox.php';