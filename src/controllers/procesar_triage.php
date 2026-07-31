<?php
// src/views/procesar_triage.php

// 1. Mostrar errores en pantalla para depurar (si algo falla nos dirá qué es en vez de quedar en blanco)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';

// Verificamos si viene por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $ticket_id = $_POST['ticket_id'] ?? null;
    $accion = $_POST['accion'] ?? null;

    if (!$ticket_id || !$accion) {
        echo "<script>alert('Error: Faltan datos obligatorios.'); window.location.href = 'triage.php';</script>";
        exit;
    }

    $redmineError = null;

    try {
        if ($accion === 'aprobar') {
            // 1. Recibir datos del formulario de aprobación
            $categoria_id = $_POST['categoria_id'] ?? null;
            $asignado_a_id = $_POST['asignado_a_id'] ?? null;
            $estimated_hours = $_POST['estimated_hours'] ?? null;
            $estado_id = $_POST['estado_id'] ?? null;

            // 2. Actualizar en Base de Datos Local (PostgreSQL)
            $sql = "UPDATE redmine_tareas 
                    SET categoria = :categoria, 
                        asignado_a_id = :asignado_a, 
                        estimated_hours = :horas, 
                        estado_id = :estado_id, 
                        updated_on = NOW() 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':categoria'  => $categoria_id,
                ':asignado_a' => $asignado_a_id,
                ':horas'      => $estimated_hours,
                ':estado_id'  => $estado_id,
                ':id'         => $ticket_id
            ]);

            // 3. Intentar sincronizar con Redmine
            try {
                $redmineService = new RedmineService();
                $datosRedmine = [
                    'assigned_to_id'  => (int)$asignado_a_id,
                    'estimated_hours' => (float)$estimated_hours,
                    'status_id'       => (int)$estado_id
                ];

                $resultadoRedmine = $redmineService->updateIssue($ticket_id, $datosRedmine);
                if (!$resultadoRedmine) {
                    $redmineError = "No se pudo actualizar en Redmine (Verificar credenciales o conectividad).";
                }
            } catch (Throwable $e) {
                $redmineError = "Excepción en RedmineService: " . $e->getMessage();
            }

        } elseif ($accion === 'rechazar') {
            $estadoRechazadoId = 6; 

            // 1. Actualizar en Base de Datos Local
            $sql = "UPDATE redmine_tareas 
                    SET estado_id = :estado_id, 
                        updated_on = NOW() 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':estado_id' => $estadoRechazadoId,
                ':id'        => $ticket_id
            ]);

            // 2. Intentar sincronizar con Redmine
            try {
                $redmineService = new RedmineService();
                $datosRedmine = [
                    'status_id' => $estadoRechazadoId
                ];

                $resultadoRedmine = $redmineService->updateIssue($ticket_id, $datosRedmine);
                if (!$resultadoRedmine) {
                    $redmineError = "No se pudo actualizar en Redmine.";
                }
            } catch (Throwable $e) {
                $redmineError = "Excepción en RedmineService: " . $e->getMessage();
            }
        }

        // Si hubo un error con Redmine, mostramos alerta antes de redirigir
        if ($redmineError) {
            $msg = addslashes($redmineError);
            echo "<script>
                alert('Guardado en local, pero falló Redmine: $msg');
                window.location.href = 'triage.php';
            </script>";
            exit;
        }

        // Si todo salió bien, redirige limpio
        echo "<script>window.location.href = 'triage.php';</script>";
        exit;

    } catch (Exception $e) {
        $errorMsg = addslashes($e->getMessage());
        echo "<script>alert('Error en base de datos local: $errorMsg'); window.location.href = 'triage.php';</script>";
        exit;
    }
} else {
    echo "<script>window.location.href = 'triage.php';</script>";
    exit;
}
?>