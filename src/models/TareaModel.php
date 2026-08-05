<?php
// src/models/TareaModel.php

class TareaModel {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getTickets(string $tab, bool $mostrarCerrados): array {
        // 1. Filtro Global por Proyecto
        // ATENCIÓN: Si tu campo t.proyecto_id es numérico, cambiá 'incidentes-diarios' por su ID correspondiente (ej: 5).
        $proyectoCondition = "AND t.proyecto_id = '88'";

        // 2. Filtro de Estados (Activos vs Finalizados/Cerrados)
        if ($mostrarCerrados) {
            // Trae los tickets cerrados nativamente OR los que cayeron en estados de finalización
            $condicionEstado = "AND (e.is_closed IS TRUE OR LOWER(e.nombre) IN ('resuelto', 'rechazada', 'rechadaza', 'validado', 'cerrado', 'resuelto en desarrollo'))";
        } else {
            // Trae los tickets abiertos y excluye explícitamente los estados que indicaste
            $condicionEstado = "AND e.is_closed IS NOT TRUE AND LOWER(e.nombre) NOT IN ('resuelto', 'rechazada', 'rechadaza', 'validado', 'cerrado', 'resuelto en desarrollo')";
        }

        // 3. Consulta Base
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
            WHERE 1=1 
            {$proyectoCondition} 
            {$condicionEstado}
        ";

        // 4. Filtros de Pestañas
        if ($tab === 'sin_categoria') {
            // Sin categoría asignada
            $sql .= " AND (t.categoria IS NULL OR t.categoria = '')";
        } elseif ($tab === 'operativa') {
            // Tienen categoría asignada Y NO son proyectos
            $sql .= " AND (t.categoria IS NOT NULL AND t.categoria != '') AND (LOWER(t.categoria) != 'proyecto' AND t.categoria != '108')";
        } elseif ($tab === 'proyectos') {
            // Son de categoría proyecto
            $sql .= " AND (LOWER(t.categoria) = 'proyecto' OR t.categoria = '108')";
        }

        $sql .= " ORDER BY t.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCounts(bool $mostrarCerrados): array {
        // Replicamos las mismas condiciones globales y de estado para que los contadores coincidan
        $proyectoCondition = "AND t.proyecto_id = '88'";
        
        if ($mostrarCerrados) {
            $condicionEstado = "AND (e.is_closed IS TRUE OR LOWER(e.nombre) IN ('resuelto', 'rechazada', 'rechadaza', 'validado', 'cerrado', 'resuelto en desarrollo'))";
        } else {
            $condicionEstado = "AND e.is_closed IS NOT TRUE AND LOWER(e.nombre) NOT IN ('resuelto', 'rechazada', 'rechadaza', 'validado', 'cerrado', 'resuelto en desarrollo')";
        }

        // Contamos aplicando la lógica de cada pestaña directamente en el SELECT
        $sqlCounts = "
            SELECT 
                COUNT(CASE WHEN (t.categoria IS NULL OR t.categoria = '') THEN 1 END) as sin_cat,
                COUNT(CASE WHEN (t.categoria IS NOT NULL AND t.categoria != '') AND (LOWER(t.categoria) != 'proyecto' AND t.categoria != '108') THEN 1 END) as operativas,
                COUNT(CASE WHEN LOWER(t.categoria) = 'proyecto' OR t.categoria = '108' THEN 1 END) as proyectos,
                COUNT(*) as total
            FROM redmine_tareas t
            LEFT JOIN redmine_estados e ON t.estado_id = e.id
            WHERE 1=1 
            {$proyectoCondition} 
            {$condicionEstado}
        ";
        
        return $this->db->query($sqlCounts)->fetch(PDO::FETCH_ASSOC);
    }
}