<?php
// src/controllers/guardar_agente.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    try {
        if ($accion === 'crear') {
            $stmt = $pdo->prepare("INSERT INTO agentes (nombre, cargo) VALUES (?, ?)");
            $stmt->execute([trim($_POST['nombre']), trim($_POST['cargo'])]);
            $_SESSION['flash_success'] = "Agente registrado exitosamente.";
        } 
        elseif ($accion === 'editar') {
            $stmt = $pdo->prepare("UPDATE agentes SET nombre = ?, cargo = ? WHERE id = ?");
            $stmt->execute([trim($_POST['nombre']), trim($_POST['cargo']), (int)$_POST['id']]);
            $_SESSION['flash_success'] = "Agente actualizado exitosamente.";
        } 
        elseif ($accion === 'eliminar') {
            $stmt = $pdo->prepare("DELETE FROM agentes WHERE id = ?");
            $stmt->execute([(int)$_POST['id']]);
            $_SESSION['flash_success'] = "Agente eliminado del sistema.";
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = "Error al procesar el agente: " . $e->getMessage();
    }
    
    header('Location: /index.php?page=agentes'); 
    exit;
}

header('Location: /index.php?page=agentes');
exit;