<?php
// src/controllers/eliminar_sistema.php

// Obtener el ID de la URL
$sistemaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$sistemaId) {
    $_SESSION['flash_error'] = 'ID de sistema no proporcionado o inválido.';
    header('Location: /index.php?page=sistemas');
    exit;
}

try {
    // Al tener ON DELETE CASCADE en la BD, esto borra también los ambientes y respaldos asociados
    $stmt = $pdo->prepare("DELETE FROM sistemas WHERE id = :id");
    $stmt->execute([':id' => $sistemaId]);

    // Verificamos si realmente se borró algo
    if ($stmt->rowCount() > 0) {
        $_SESSION['flash_success'] = "El sistema y toda su configuración asociada fueron eliminados.";
    } else {
        $_SESSION['flash_error'] = "No se encontró el sistema para eliminar (es posible que ya haya sido borrado).";
    }

} catch (Exception $e) {
    error_log("Error al eliminar sistema ID {$sistemaId}: " . $e->getMessage());
    $_SESSION['flash_error'] = "Ocurrió un error en la base de datos al intentar eliminar el sistema.";
}

// Redirigir de vuelta al listado
header('Location: /index.php?page=sistemas');
exit;
