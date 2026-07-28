<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = (int)$_POST['ticket_id'];
    $accion = $_POST['accion'] ?? 'aprobar';

    if ($accion === 'rechazar') {
        // 1. Buscamos el ID del estado 'Rechazado'
        $stmtEst = $pdo->prepare("SELECT id FROM redmine_estados WHERE LOWER(nombre) = 'rechazado' LIMIT 1");
        $stmtEst->execute();
        $estadoRechazadoId = $stmtEst->fetchColumn();

        if ($estadoRechazadoId) {
            // 2. Actualizamos estado_id Y le asignamos categoria = 'Rechazado' 
            // Esto evita que t.categoria IS NULL lo vuelva a traer al Triage
            $sqlRechazar = "UPDATE redmine_tareas 
                            SET estado_id = :estado_id, 
                                categoria = 'Rechazado', 
                                updated_on = NOW() 
                            WHERE id = :id";
            
            $stmt = $pdo->prepare($sqlRechazar);
            $stmt->execute([
                ':estado_id' => $estadoRechazadoId, 
                ':id' => $ticket_id
            ]);
        }
    } else {
        // Aprobar / Clasificar
        $categoria = trim($_POST['categoria'] ?? '');
        $tracker_nombre = $_POST['tracker_nombre'] ?? $categoria;
        $asignado_a_id = (int)$_POST['asignado_a_id'];
        $estimated_hours = (float)$_POST['estimated_hours'];
        $estado_id = (int)$_POST['estado_id'];

        $sql = "UPDATE redmine_tareas SET 
                    tracker_nombre = :tracker_nombre,
                    categoria = :categoria,
                    asignado_a_id = :asignado_a_id,
                    estimated_hours = :estimated_hours,
                    estado_id = :estado_id,
                    updated_on = NOW()
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':tracker_nombre' => $tracker_nombre,
            ':categoria' => $categoria,
            ':asignado_a_id' => $asignado_a_id,
            ':estimated_hours' => $estimated_hours,
            ':estado_id' => $estado_id,
            ':id' => $ticket_id
        ]);
    }

    // Redirección compatible con output previo
    echo "<script>window.location.href = 'index.php?page=triage';</script>";
    echo "<noscript><meta http-equiv='refresh' content='0;url=index.php?page=triage'></noscript>";
    exit;
}