<?php
// src/views/gestion_inbox.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';
/*
if (!isset($pdo) || !$pdo) {
    if (function_exists('getDB')) {
        $pdo = getDB();
    } elseif (function_exists('getDbConnection')) {
        $pdo = getDbConnection();
    } else {
        die("Error: No se encontró una conexión válida a la base de datos.");
    }
}
*/
// Determinar pestaña activa desde GET (por defecto 'todas')
$tab = $_GET['tab'] ?? 'todas';

// Consulta base
$sql = "
    SELECT 
        id,
        redmine_id,
        issue_id,
        subject,
        status_name,
        priority_name,
        assigned_to_name,
        author_name,
        created_on,
        updated_on,
        project_name,
        category_id,
        category_name
    FROM redmine_tareas
    WHERE project_id IN ('redes-drafts', 'incidentes-diarios')
      AND status_name NOT IN ('Cerrado', 'Cerrada', 'Resuelto', 'Resuelta', 'Rechazado', 'Rechazada')
";

// Filtrar según la pestaña seleccionada
if ($tab === 'sin_categoria') {
    $sql .= " AND (category_id IS NULL OR category_name IS NULL OR category_name = '')";
} elseif ($tab === 'operativa') {
    // Categorías operativas de rutina (Excluye ID 108 que es Proyecto)
    $sql .= " AND (category_id != 108 OR category_id IS NULL) AND category_name != 'Proyecto'";
} elseif ($tab === 'proyectos') {
    // Únicamente categoría Proyecto (ID 108)
    $sql .= " AND (category_id = 108 OR category_name = 'Proyecto')";
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Conteo rápido para insignias (Badges) en las pestañas
$sqlCounts = "
    SELECT 
        COUNT(CASE WHEN category_id IS NULL OR category_name IS NULL OR category_name = '' THEN 1 END) as sin_cat,
        COUNT(CASE WHEN (category_id != 108 OR category_id IS NULL) AND (category_name != 'Proyecto' OR category_name IS NULL) THEN 1 END) as operativas,
        COUNT(CASE WHEN category_id = 108 OR category_name = 'Proyecto' THEN 1 END) as proyectos,
        COUNT(*) as total
    FROM redmine_tareas
    WHERE project_id IN ('redes-drafts', 'incidentes-diarios')
      AND status_name NOT IN ('Cerrado', 'Cerrada', 'Resuelto', 'Resuelta', 'Rechazado', 'Rechazada')
";
$counts = $pdo->query($sqlCounts)->fetch(PDO::FETCH_ASSOC);

include __DIR__ . '/layouts/header.php';
include __DIR__ . '/layouts/sidebar.php';
?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Mesa de Entrada - Triage de Infraestructura</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="sync_redmine.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-sync-alt">
</i> Sincronizar Ahora
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Navegación de Pestañas / Filtros por Categoría -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'todas' ? 'active font-weight-bold' : '' ?>" href="gestion_inbox.php?tab=todas">
                        <i class="fas fa-inbox">
</i> Todas <span class="badge badge-secondary ml-1"><?= $counts['total'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'sin_categoria' ? 'active font-weight-bold' : '' ?>" href="gestion_inbox.php?tab=sin_categoria">
                        <i class="fas fa-exclamation-triangle text-warning">
</i> Sin Categoría 
                        <span class="badge badge-warning ml-1"><?= $counts['sin_cat'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'operativa' ? 'active font-weight-bold' : '' ?>" href="gestion_inbox.php?tab=operativa">
                        <i class="fas fa-tools text-info">
</i> Operativas / Incidentes 
                        <span class="badge badge-info ml-1"><?= $counts['operativas'] ?></span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $tab === 'proyectos' ? 'active font-weight-bold' : '' ?>" href="gestion_inbox.php?tab=proyectos">
                        <i class="fas fa-project-diagram text-success">
</i> Proyectos 
                        <span class="badge badge-success ml-1"><?= $counts['proyectos'] ?></span>
                    </a>
                </li>
            </ul>

            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Peticiones Pendientes de Atención</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th># Redmine</th>
                                    <th>Asunto</th>
                                    <th>Proyecto</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Asignado a</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tickets)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success">
</i>
                                            No hay tareas pendientes en este filtro.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tickets as $t): ?>
                                        <?php 
                                            $redmineId = $t['issue_id'] ?? $t['redmine_id'] ?? $t['id'];
                                            $catId = $t['category_id'] ?? null;
                                            $catName = $t['category_name'] ?? '';

                                            // REDIRECCIÓN INTELIGENTE: Si es Categoría Proyecto (108) -> ver_proyecto.php, sino -> ver_incidente.php
                                            $esProyecto = ($catId == 108 || strtolower($catName) === 'proyecto');
                                            $urlVer = $esProyecto 
                                                ? "ver_proyecto.php?id={$redmineId}" 
                                                : "ver_incidente.php?id={$redmineId}";
                                        ?>
                                        <tr>
                                            <td>
                                                <a href="<?= $urlVer ?>" class="font-weight-bold">
                                                    #<?= htmlspecialchars($redmineId) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <a href="<?= $urlVer ?>" class="text-dark">
                                                    <?= htmlspecialchars($t['subject']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge badge-light border">
                                                    <?= htmlspecialchars($t['project_name'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($esProyecto): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-project-diagram">
</i> Proyecto
                                                    </span>
                                                <?php elseif (!empty($catName)): ?>
                                                    <span class="badge badge-info">
                                                        <?= htmlspecialchars($catName) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning text-dark">
                                                        <i class="fas fa-exclamation-circle">
</i> Sin Categoría
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-secondary">
                                                    <?= htmlspecialchars($t['status_name'] ?? 'Desconocido') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                    $prio = $t['priority_name'] ?? 'Normal';
                                                    $badgeClass = ($prio === 'Alta' || $prio === 'Urgente') ? 'badge-danger' : 'badge-primary';
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= htmlspecialchars($prio) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($t['assigned_to_name'] ?? 'Sin Asignar') ?></td>
                                            <td>
                                                <a href="<?= $urlVer ?>" class="btn btn-xs <?= $esProyecto ? 'btn-success' : 'btn-info' ?>">
                                                    <i class="fas <?= $esProyecto ? 'fa-project-diagram' : 'fa-eye' ?>">
</i> 
                                                    <?= $esProyecto ? 'Ver Proyecto' : 'Atender' ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include __DIR__ . '/layouts/footer.php'; ?>