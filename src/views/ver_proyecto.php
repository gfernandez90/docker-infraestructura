<?php
// views/ver_proyecto.php
require_once __DIR__ . '/../config/db.php';

$id = $_GET['id'] ?? null;
$proyecto = null;
$subtareas = [];
$error = null;

if ($id) {
    // 1. Obtener proyecto relacionando únicamente con ru.nombre_completo
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            COALESCE(e.nombre, 'Sin Estado') as estado_nombre,
            COALESCE(e.is_closed::int, 0) as is_closed,
            COALESCE(ru.nombre_completo, 'Sin Asignar') as asignado_nombre
        FROM redmine_tareas t
        LEFT JOIN redmine_estados e ON t.estado_id = e.id
        LEFT JOIN redmine_usuarios ru ON t.asignado_a_id = ru.id
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proyecto) {
        $error = "No se encontró ningún proyecto o ticket con el ID #{$id}.";
    } else {
        // 2. Obtener subtareas asociadas relacionando con ru.nombre_completo
        $stmtSub = $pdo->prepare("
            SELECT 
                t.*,
                COALESCE(e.nombre, 'Sin Estado') as estado_nombre,
                COALESCE(e.is_closed::int, 0) as is_closed,
                COALESCE(ru.nombre_completo, 'Sin Asignar') as asignado_nombre
            FROM redmine_tareas t
            LEFT JOIN redmine_estados e ON t.estado_id = e.id
            LEFT JOIN redmine_usuarios ru ON t.asignado_a_id = ru.id
            WHERE t.parent_id = ?
            ORDER BY t.id ASC
        ");
        $stmtSub->execute([$id]);
        $subtareas = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="space-y-6 w-full">

    <!-- Buscador por ID -->
    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">📂 Inspeccionar Proyecto</h1>
            <p class="text-xs text-slate-400 mt-0.5">Ingresá un ID de ticket de Redmine para inspeccionar el proyecto y sus subtareas.</p>
        </div>

        <form method="GET" action="/index.php" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="page" value="ver_proyecto">
            <div class="relative flex-1 md:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 font-mono text-sm">#</span>
                <input type="number" name="id" value="<?= $id ? htmlspecialchars($id) : '' ?>" placeholder="Ej: 33105" required class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-7 pr-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-purple-500 font-mono">
            </div>
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Cargar</button>
        </form>
    </div>

    <!-- Alerta de Error -->
    <?php if ($error): ?>
        <div class="bg-amber-500/10 border border-amber-500/30 text-amber-400 p-4 rounded-xl flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <p class="text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <!-- Detalle del Proyecto -->
    <?php if ($proyecto): ?>
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
            
            <!-- Header del Proyecto -->
            <div class="p-6 bg-slate-800 border-b border-slate-700 flex justify-between items-start gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="text-purple-400 font-bold text-xl">#<?= htmlspecialchars($proyecto['id']) ?></span>
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-950/80 text-purple-300 border border-purple-800/60">
                            📂 Proyecto
                        </span>
                        <span class="px-2.5 py-1 rounded text-xs font-medium border <?= ((int)$proyecto['is_closed'] === 1) ? 'bg-slate-900 text-slate-400 border-slate-700' : 'bg-emerald-950/80 text-emerald-300 border-emerald-800/60' ?>">
                            <?= htmlspecialchars($proyecto['estado_nombre']) ?>
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($proyecto['asunto']) ?></h1>
                </div>
                <a href="/index.php?page=gestion_inbox" class="px-3.5 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 text-xs font-semibold rounded-lg transition border border-slate-600 whitespace-nowrap">
                    ← Volver a Mesa
                </a>
            </div>

            <!-- Métricas / Información General -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 p-6 bg-slate-900/60 border-b border-slate-700 text-sm">
                <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                    <span class="text-slate-400 text-xs block mb-1">Avance Global</span>
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-slate-900 rounded-full h-2 overflow-hidden">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: <?= (int)$proyecto['porcentaje_done'] ?>%"></div>
                        </div>
                        <span class="font-semibold text-white text-xs"><?= (int)$proyecto['porcentaje_done'] ?>%</span>
                    </div>
                </div>
                <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                    <span class="text-slate-400 text-xs block mb-1">Asociado / Asignado A</span>
                    <span class="text-purple-300 font-medium flex items-center gap-1.5">
                        👤 <?= htmlspecialchars($proyecto['asignado_nombre']) ?>
                    </span>
                </div>
                <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                    <span class="text-slate-400 text-xs block mb-1">Categoría</span>
                    <span class="text-slate-200 font-medium"><?= htmlspecialchars($proyecto['categoria'] ?: 'Sin Categoría') ?></span>
                </div>
                <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                    <span class="text-slate-400 text-xs block mb-1">Fecha Creación</span>
                    <span class="text-slate-200 font-medium"><?= date('d/m/Y H:i', strtotime($proyecto['created_on'])) ?></span>
                </div>
                <div class="bg-slate-800 p-4 rounded-xl border border-slate-700">
                    <span class="text-slate-400 text-xs block mb-1">Última Actualización</span>
                    <span class="text-slate-200 font-medium"><?= date('d/m/Y H:i', strtotime($proyecto['updated_on'])) ?></span>
                </div>
            </div>

            <!-- Descripción -->
            <div class="p-6 border-b border-slate-700">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Descripción del Proyecto</h3>
                <div class="bg-slate-900/80 border border-slate-700 rounded-xl p-4 text-slate-300 text-sm whitespace-pre-wrap leading-relaxed">
                    <?= !empty($proyecto['descripcion']) ? htmlspecialchars($proyecto['descripcion']) : '<span class="text-slate-500 italic">Sin descripción proporcionada.</span>' ?>
                </div>
            </div>

            <!-- Listado de Subtareas -->
            <div class="p-6">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-4">
                    Subtareas Asociadas (<?= count($subtareas) ?>)
                </h3>

                <?php if (empty($subtareas)): ?>
                    <div class="p-6 bg-slate-900/50 border border-slate-700/60 rounded-xl text-center text-slate-400 text-sm">
                        Este proyecto no tiene subtareas vinculadas actualmente.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-slate-700">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-700">
                                    <th class="p-4"># Subtarea</th>
                                    <th class="p-4">Asunto</th>
                                    <th class="p-4">Asociado / Asignado A</th>
                                    <th class="p-4">Estado</th>
                                    <th class="p-4">Avance</th>
                                    <th class="p-4 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/60 text-sm">
                                <?php foreach ($subtareas as $sub): ?>
                                    <tr class="hover:bg-slate-700/30 transition-colors">
                                        <td class="p-4 font-bold text-slate-300 whitespace-nowrap">
                                            #<?= htmlspecialchars($sub['id']) ?>
                                        </td>
                                        <td class="p-4 font-medium text-slate-200">
                                            <?= htmlspecialchars($sub['asunto']) ?>
                                        </td>
                                        <td class="p-4 text-slate-300 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1.5 text-xs text-sky-300 bg-sky-950/50 border border-sky-800/50 px-2.5 py-1 rounded-full">
                                                👤 <?= htmlspecialchars($sub['asignado_nombre']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <span class="px-2.5 py-1 rounded text-xs font-medium border <?= ((int)$sub['is_closed'] === 1) ? 'bg-slate-900 text-slate-400 border-slate-700' : 'bg-emerald-950/80 text-emerald-300 border-emerald-800/60' ?>">
                                                <?= htmlspecialchars($sub['estado_nombre']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <div class="w-20 bg-slate-900 rounded-full h-2 overflow-hidden mb-1">
                                                <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= (int)$sub['porcentaje_done'] ?>%"></div>
                                            </div>
                                            <span class="text-xs text-slate-400"><?= (int)$sub['porcentaje_done'] ?>%</span>
                                        </td>
                                        <td class="p-4 text-right whitespace-nowrap">
                                            <a href="/index.php?page=ver_incidente&id=<?= $sub['id'] ?>" class="px-3 py-1.5 inline-block bg-sky-900/40 text-sky-300 hover:bg-sky-800/60 border border-sky-700/50 rounded-lg text-xs font-medium transition">
                                                Atender →
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    <?php endif; ?>

</div>