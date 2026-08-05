<?php
// src/models/TareaModel.php

class TareaModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getTickets(string $tab, bool $mostrarCerrados): array {
        $condicionEstado = $mostrarCerrados 
            ? "AND e.is_closed IS TRUE" 
            : "AND (e.is_closed IS NOT TRUE)";

        $sql = "
            SELECT 
                t.id, t.proyecto_id, t.tracker_nombre, t.estado_id,
                COALESCE(e.nombre, 'Sin Estado') as estado_nombre,
                COALESCE(e.is_closed::int, 0) as is_closed,
                t.prioridad_id, t.asunto, t.descripcion, t.autor_id,
                t.asignado_a_id, t.porcentaje_done, t.created_on,
                t.updated_on, t.parent_id, t.categoria
            FROM redmine_tareas t
            LEFT JOIN redmine_estados e ON t.estado_id = e.id
            WHERE 1=1 {$condicionEstado}
        ";

        if ($tab === 'sin_categoria') {
            $sql .= " AND (t.categoria IS NULL OR t.categoria = '')";
        } elseif ($tab === 'operativa') {
            $sql .= " AND (t.categoria IS NULL OR (LOWER(t.categoria) != 'proyecto' AND t.categoria != '108'))";
        } elseif ($tab === 'proyectos') {
            $sql .= " AND (LOWER(t.categoria) = 'proyecto' OR t.categoria = '108')";
        }
        /*
        Operativa REAL:
         AND (t.categoria IS NULL OR (LOWER(t.categoria) != 'proyecto' AND t.categoria != '108'))
AND (LOWER(e.nombre) != 'resuelto') 
AND (LOWER(e.nombre) != 'rechazada') 
AND (LOWER(e.nombre) != 'pronto para testing') 
AND (LOWER(e.nombre) != 'resuelto en desarrollo') 
AND (LOWER(e.nombre) != 'validado')

Para el inbox seria
Operativa 
Finalizada (Resuelto / Rechazada)
Proyectos
Todas


Cambiar en la logica -> NO CARGAR EN OPERATIVA TAREAS QUE PERTENEZCAN AL PROYECTO PROYECTOS INFRA

        */

        $sql .= " ORDER BY t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCounts(bool $mostrarCerrados): array {
        $condicionEstado = $mostrarCerrados 
            ? "AND e.is_closed IS TRUE" 
            : "AND (e.is_closed IS NOT TRUE)";

        $sqlCounts = "
            SELECT 
                COUNT(CASE WHEN t.categoria IS NULL OR t.categoria = '' THEN 1 END) as sin_cat,
                COUNT(CASE WHEN t.categoria IS NULL OR (LOWER(t.categoria) != 'proyecto' AND t.categoria != '108') THEN 1 END) as operativas,
                COUNT(CASE WHEN LOWER(t.categoria) = 'proyecto' OR t.categoria = '108' THEN 1 END) as proyectos,
                COUNT(*) as total
            FROM redmine_tareas t
            LEFT JOIN redmine_estados e ON t.estado_id = e.id
            WHERE 1=1 {$condicionEstado}
        ";
        
        return $this->db->query($sqlCounts)->fetch(PDO::FETCH_ASSOC);
    }
}