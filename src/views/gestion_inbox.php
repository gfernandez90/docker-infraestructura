<?php
// views/gestion_inbox.php
require_once __DIR__ . '/../config/db.php';

$tab = $_GET['tab'] ?? 'todas';
$mostrarCerrados = isset($_GET['cerrados']) && $_GET['cerrados'] === '1';

// Condición SQL PostgreSQL compatible con booleans
$condicionEstado = $mostrarCerrados 
    ? "AND e.is_closed IS TRUE" 
    : "AND (e.is_closed IS NOT TRUE)";

// 1. Consulta SQL principal (Casteamos e.is_closed::int para evitar mismatch en COALESCE)
$sql = "
    SELECT 
        t.id,
        t.proyecto_id,
        t.tracker_nombre,
        t.estado_id,
        COALESCE(e.nombre, 'Sin Estado') as estado_nombre,
        COALESCE(e.is_closed::int, 0) as is_closed,
        t.prioridad_id,
        t.asunto,
        t.descripcion,
        t.autor_id,
        t.asignado_a_id,
        t.porcentaje_done,
        t.created_on,
        t.updated_on,
        t.parent_id,
        t.categoria
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

$sql .= " ORDER BY t.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Conteo de badges
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
$counts = $pdo->query($sqlCounts)->fetch(PDO::FETCH_ASSOC);
?>
<div class="w-full">
    <!-- Tarjeta Contenedora Única (Modal Completo) -->
    <div class="bg-slate-900 rounded-2xl shadow-2xl border border-slate-800 overflow-hidden w-full">
        
        <!-- 1. Encabezado de la Tarjeta -->
        <div class="p-6 bg-slate-900 border-b border-slate-800/80 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white">Mesa de Entrada - Triage de Infraestructura</h1>
                <p class="text-slate-400 text-sm mt-1">
                    <?= $mostrarCerrados ? 'Mostrando tickets cerrados/finalizados' : 'Mostrando únicamente tareas activas en proceso' ?>
                </p>
            </div>
            <div class="flex items-center gap-3">
                <?php if ($mostrarCerrados): ?>
                    <a href="/index.php?page=gestion_inbox&tab=<?= urlencode($tab) ?>" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-semibold rounded-lg transition border border-slate-700">
                        Ver Activos
                    </a>
                <?php else: ?>
                    <a href="/index.php?page=gestion_inbox&tab=<?= urlencode($tab) ?>&cerrados=1" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium rounded-lg transition border border-slate-700">
                        Ver Finalizados / Cerrados
                    </a>
                <?php endif; ?>

                <a href="/index.php?page=sync_redmine" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-medium transition flex items-center gap-2 shadow-lg shadow-emerald-900/30">
                    <span>🔄 Sincronizar</span>
                </a>
            </div>
        </div>

        <!-- 2. Pestañas Integradas (Sub-header) -->
        <div class="px-6 bg-slate-950/60 border-b border-slate-800 flex gap-2 pt-2">
            <?php $cerradosParam = $mostrarCerrados ? '&cerrados=1' : ''; ?>
            
            <a href="/index.php?page=gestion_inbox&tab=todas<?= $cerradosParam ?>" 
               class="px-4 py-3 border-b-2 font-medium text-sm transition flex items-center gap-2 <?= $tab === 'todas' ? 'border-emerald-500 text-emerald-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' ?>">
                📥 Todas <span class="px-2 py-0.5 rounded-full text-xs bg-slate-800 text-slate-300 border border-slate-700"><?= $counts['total'] ?? 0 ?></span>
            </a>
            <a href="/index.php?page=gestion_inbox&tab=sin_categoria<?= $cerradosParam ?>" 
               class="px-4 py-3 border-b-2 font-medium text-sm transition flex items-center gap-2 <?= $tab === 'sin_categoria' ? 'border-amber-500 text-amber-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' ?>">
                ⚠️ Sin Categoría <span class="px-2 py-0.5 rounded-full text-xs bg-amber-950/60 text-amber-300 border border-amber-800/50"><?= $counts['sin_cat'] ?? 0 ?></span>
            </a>
            <a href="/index.php?page=gestion_inbox&tab=operativa<?= $cerradosParam ?>" 
               class="px-4 py-3 border-b-2 font-medium text-sm transition flex items-center gap-2 <?= $tab === 'operativa' ? 'border-sky-500 text-sky-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' ?>">
                🔧 Operativas <span class="px-2 py-0.5 rounded-full text-xs bg-sky-950/60 text-sky-300 border border-sky-800/50"><?= $counts['operativas'] ?? 0 ?></span>
            </a>
            <a href="/index.php?page=gestion_inbox&tab=proyectos<?= $cerradosParam ?>" 
               class="px-4 py-3 border-b-2 font-medium text-sm transition flex items-center gap-2 <?= $tab === 'proyectos' ? 'border-purple-500 text-purple-400 font-semibold' : 'border-transparent text-slate-400 hover:text-slate-200' ?>">
                📂 Proyectos <span class="px-2 py-0.5 rounded-full text-xs bg-purple-950/60 text-purple-300 border border-purple-800/50"><?= $counts['proyectos'] ?? 0 ?></span>
            </a>
        </div>

        <!-- 3. Tabla Integrada -->
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-950/80 border-b border-slate-800 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        <th class="p-4 pl-6"># Ticket</th>
                        <th class="p-4">Asunto</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4">Categoría</th>
                        <th class="p-4">Avance</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4 pr-6 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-sm">
                    <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-12 text-slate-500">
                                No hay tickets registrados en este filtro.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tickets as $t): ?>
                            <?php 
                                $ticketId = $t['id'];
                                $catVal = $t['categoria'] ?? '';
                                $esProyecto = (strtolower($catVal) === 'proyecto' || $catVal == '108');
                                $esCerrado = ((int)$t['is_closed'] === 1);
                                $urlVer = $esProyecto 
                                    ? "/index.php?page=ver_proyecto&id={$ticketId}" 
                                    : "/index.php?page=ver_incidente&id={$ticketId}";
                            ?>
                            <tr class="hover:bg-slate-800/40 transition-colors">
                                <td class="p-4 pl-6 font-bold text-slate-300 whitespace-nowrap">
                                    <a href="<?= $urlVer ?>" class="hover:text-white hover:underline">#<?= htmlspecialchars($ticketId) ?></a>
                                </td>
                                <td class="p-4">
                                    <a href="<?= $urlVer ?>" class="font-medium text-slate-200 hover:text-emerald-400 transition-colors">
                                        <?= htmlspecialchars($t['asunto']) ?>
                                    </a>
                                    <?php if (!empty($t['parent_id'])): ?>
                                        <span class="block text-xs text-slate-500 mt-0.5">Subtarea de #<?= $t['parent_id'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded text-xs font-medium border <?= $esCerrado ? 'bg-slate-800 text-slate-400 border-slate-700' : 'bg-emerald-950/80 text-emerald-300 border-emerald-800/60' ?>">
                                        <?= htmlspecialchars($t['estado_nombre']) ?>
                                    </span>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <?php if ($esProyecto): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-950/80 text-purple-300 border border-purple-800/60">📂 Proyecto</span>
                                    <?php elseif (!empty($catVal)): ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-sky-950/80 text-sky-300 border border-sky-800/60"><?= htmlspecialchars($catVal) ?></span>
                                    <?php else: ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-950/80 text-amber-300 border border-amber-800/60">⚠️ Sin Categoría</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <div class="w-24 bg-slate-800 rounded-full h-2 overflow-hidden mb-1">
                                        <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= (int)$t['porcentaje_done'] ?>%"></div>
                                    </div>
                                    <span class="text-xs text-slate-400"><?= (int)$t['porcentaje_done'] ?>%</span>
                                </td>
                                <td class="p-4 text-xs text-slate-400 whitespace-nowrap">
                                    <?= date('d/m/Y', strtotime($t['created_on'])) ?>
                                </td>
                                <td class="p-4 pr-6 text-right whitespace-nowrap">
                                    <a href="<?= $urlVer ?>" class="px-3 py-1.5 inline-block <?= $esProyecto ? 'bg-purple-900/40 text-purple-300 hover:bg-purple-800/60 border border-purple-700/50' : 'bg-sky-900/40 text-sky-300 hover:bg-sky-800/60 border border-sky-700/50' ?> rounded-lg text-xs font-medium transition">
                                        <?= $esProyecto ? 'Ver Proyecto' : 'Atender' ?> →
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