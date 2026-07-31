<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';

$ticketId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$ticket = null;
$subtareas = [];
$relacionadas = [];
$seguidores = [];
$categoriaCustom = null;
$pmResponsableCustom = null;
$error = null;

if ($ticketId) {
    // 1. Obtener la información completa del ticket local
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            e.nombre AS estado_nombre,
            e.is_closed,
            p.nombre AS prioridad_nombre,
            u_autor.nombre_completo AS autor_nombre,
            u_asig.nombre_completo AS asignado_nombre,
            proj.nombre AS proyecto_nombre,
            proj.identifier AS proyecto_identifier,
            padre.asunto AS padre_asunto
        FROM redmine_tareas t
        LEFT JOIN redmine_estados e ON t.estado_id = e.id
        LEFT JOIN redmine_prioridades p ON t.prioridad_id = p.id
        LEFT JOIN redmine_usuarios u_autor ON t.autor_id = u_autor.id
        LEFT JOIN redmine_usuarios u_asig ON t.asignado_a_id = u_asig.id
        LEFT JOIN redmine_proyectos proj ON t.proyecto_id = proj.id
        LEFT JOIN redmine_tareas padre ON t.parent_id = padre.id
        WHERE t.id = :id
    ");
    $stmt->execute([':id' => $ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Consultar en vivo a la API usando RedmineService
    try {
        $redmineService = new RedmineService();
        $apiDetail = $redmineService->getTareaDetalle($ticketId);

        if ($apiDetail) {
            // Fallback si no estaba en la BD local aún
            if (!$ticket) {
                $ticket = [
                    'id' => $apiDetail['id'],
                    'asunto' => $apiDetail['subject'],
                    'descripcion' => $apiDetail['description'] ?? '',
                    'proyecto_nombre' => $apiDetail['project']['name'] ?? '',
                    'tracker_nombre' => $apiDetail['tracker']['name'] ?? 'Tarea',
                    'estado_nombre' => $apiDetail['status']['name'] ?? '',
                    'is_closed' => $apiDetail['status']['is_closed'] ?? false,
                    'prioridad_nombre' => $apiDetail['priority']['name'] ?? '',
                    'autor_nombre' => $apiDetail['author']['name'] ?? '',
                    'asignado_nombre' => $apiDetail['assigned_to']['name'] ?? 'Sin Asignar',
                    'parent_id' => $apiDetail['parent']['id'] ?? null,
                    'padre_asunto' => '',
                    'created_on' => $apiDetail['created_on'] ?? null,
                    'updated_on' => $apiDetail['updated_on'] ?? null,
                    'start_date' => $apiDetail['start_date'] ?? null,
                    'due_date' => $apiDetail['due_date'] ?? null,
                    'porcentaje_done' => $apiDetail['done_ratio'] ?? 0,
                    'estimated_hours' => $apiDetail['estimated_hours'] ?? null,
                    'spent_hours' => $apiDetail['spent_hours'] ?? null,
                    'categoria' => $apiDetail['category']['name'] ?? '',
                ];
            } else {
                // Asegurar refresco de campos de tiempo si vinieron en la API
                if (isset($apiDetail['start_date'])) $ticket['start_date'] = $apiDetail['start_date'];
                if (isset($apiDetail['due_date'])) $ticket['due_date'] = $apiDetail['due_date'];
                if (isset($apiDetail['estimated_hours'])) $ticket['estimated_hours'] = $apiDetail['estimated_hours'];
                if (isset($apiDetail['done_ratio'])) $ticket['porcentaje_done'] = $apiDetail['done_ratio'];
                if (isset($apiDetail['category']['name'])) { $ticket['categoria'] = $apiDetail['category']['name'];
    }
            }

            // Extraer Custom Fields: Categoría y PM Responsable
            if (isset($apiDetail['custom_fields']) && is_array($apiDetail['custom_fields'])) {
                foreach ($apiDetail['custom_fields'] as $cf) {
                    $nombreCf = mb_strtolower(trim($cf['name'] ?? ''));
                    if (str_contains($nombreCf, 'categor') || str_contains($nombreCf, 'categoria')) {
                        $categoriaCustom = is_array($cf['value']) ? implode(', ', $cf['value']) : $cf['value'];
                    }
                    if (str_contains($nombreCf, 'pm') || str_contains($nombreCf, 'responsable')) {
                        $pmResponsableCustom = is_array($cf['value']) ? implode(', ', $cf['value']) : $cf['value'];
                    }
                }
            }

            // Seguidores
            if (isset($apiDetail['watchers'])) {
                foreach ($apiDetail['watchers'] as $w) {
                    $seguidores[] = $w['name'];
                }
            }

            // Peticiones Relacionadas
            if (isset($apiDetail['relations'])) {
                foreach ($apiDetail['relations'] as $rel) {
                    $targetId = ($rel['issue_id'] == $ticketId) ? $rel['issue_to_id'] : $rel['issue_id'];
                    
                    $stmtRelInfo = $pdo->prepare("
                        SELECT t.asunto, p.nombre AS proyecto_nombre, t.porcentaje_done 
                        FROM redmine_tareas t 
                        LEFT JOIN redmine_proyectos p ON t.proyecto_id = p.id 
                        WHERE t.id = :tid
                    ");
                    $stmtRelInfo->execute([':tid' => $targetId]);
                    $infoDestino = $stmtRelInfo->fetch(PDO::FETCH_ASSOC);

                    $relacionadas[] = [
                        'id' => $targetId,
                        'tipo' => $rel['relation_type'] ?? 'relates',
                        'asunto' => $infoDestino['asunto'] ?? 'Tarea #' . $targetId,
                        'proyecto_nombre' => $infoDestino['proyecto_nombre'] ?? 'Redmine SITA',
                        'porcentaje_done' => $infoDestino['porcentaje_done'] ?? 0
                    ];
                }
            }

            // Subtareas
            if (isset($apiDetail['children'])) {
                foreach ($apiDetail['children'] as $child) {
                    $subtareas[] = [
                        'id' => $child['id'],
                        'asunto' => $child['subject'] ?? 'Subtarea #' . $child['id'],
                        'porcentaje_done' => 0
                    ];
                }
            }
        }
    } catch (Exception $e) {
        // En caso de fallo de API, continúa con BD local
    }

    // Fallbacks locales
    if (empty($subtareas) && $ticket) {
        $stmtSub = $pdo->prepare("SELECT id, asunto, porcentaje_done FROM redmine_tareas WHERE parent_id = :id ORDER BY id ASC");
        $stmtSub->execute([':id' => $ticketId]);
        $subtareas = $stmtSub->fetchAll(PDO::FETCH_ASSOC);
    }

    if (empty($seguidores) && $ticket) {
        $stmtSeg = $pdo->prepare("
            SELECT u.nombre_completo 
            FROM redmine_tarea_seguidores ts 
            JOIN redmine_usuarios u ON ts.usuario_id = u.id 
            WHERE ts.tarea_id = :id 
            ORDER BY u.nombre_completo ASC
        ");
        $stmtSeg->execute([':id' => $ticketId]);
        $seguidores = $stmtSeg->fetchAll(PDO::FETCH_COLUMN);
    }

    if (!$ticket) {
        $error = "No se encontró el ticket #{$ticketId}.";
    }
}
?>

<div class="space-y-6">
    <!-- Buscador por ID -->
    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">🔍 Leer Incidente / Tarea</h1>
            <p class="text-xs text-slate-400 mt-0.5">Ingresá un ID de ticket de Redmine para inspeccionar sus detalles.</p>
        </div>

        <form method="GET" action="/index.php" class="flex items-center gap-2 w-full md:w-auto">
            <input type="hidden" name="page" value="ver_incidente">
            <div class="relative flex-1 md:w-64">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500 font-mono text-sm">#</span>
                <input type="number" name="id" value="<?= $ticketId ? htmlspecialchars($ticketId) : '' ?>" placeholder="Ej: 24517" required class="w-full bg-slate-900 border border-slate-700 rounded-lg pl-7 pr-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-sky-500 font-mono">
            </div>
            <button type="submit" class="bg-sky-600 hover:bg-sky-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Cargar</button>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="bg-amber-500/10 border border-amber-500/30 text-amber-400 p-4 rounded-xl flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <p class="text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($ticket): ?>
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-slate-900/90 border-b border-slate-700 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="font-mono text-xl font-bold text-sky-400">#<?= $ticket['id'] ?></span>
                        <span class="bg-slate-800 text-slate-300 text-xs px-2.5 py-0.5 rounded border border-slate-700 font-semibold"><?= htmlspecialchars($ticket['proyecto_nombre'] ?? 'Sin Proyecto') ?></span>
                        <span class="bg-slate-800 text-sky-300 text-xs px-2.5 py-0.5 rounded border border-slate-700"><?= htmlspecialchars($ticket['tracker_nombre'] ?? 'Tarea') ?></span>
                    </div>
                    <h2 class="text-2xl font-bold text-white"><?= htmlspecialchars($ticket['asunto']) ?></h2>
                    
                    <?php if (!empty($ticket['parent_id'])): ?>
                        <p class="text-xs text-amber-400 mt-2 flex items-center gap-1">
                            <span>↳ Tarea Padre:</span>
                            <a href="/index.php?page=ver_incidente&id=<?= $ticket['parent_id'] ?>" class="underline hover:text-amber-300 font-mono">
                                #<?= $ticket['parent_id'] ?> <?= htmlspecialchars($ticket['padre_asunto'] ?? '') ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>

                <a href="https://sita.anep.edu.uy/issues/<?= $ticket['id'] ?>" target="_blank" class="bg-slate-700 hover:bg-slate-600 text-slate-200 px-4 py-2 rounded-lg text-xs font-semibold transition border border-slate-600 flex items-center gap-1.5 whitespace-nowrap">
                    <span>🔗</span> Abrir en Redmine SITA
                </a>
            </div>

            <!-- Cabezal de Datos Principal -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-slate-900/40 border-b border-slate-700/80 text-sm">
                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Estado</span>
                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-xs font-semibold <?= !empty($ticket['is_closed']) ? 'bg-slate-700 text-slate-400' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                        <?= htmlspecialchars($ticket['estado_nombre'] ?? 'N/A') ?>
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Prioridad</span>
                    <span class="font-medium text-slate-200 mt-1 block"><?= htmlspecialchars($ticket['prioridad_nombre'] ?? 'Normal') ?></span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Solicitante (Autor)</span>
                    <span class="font-medium text-slate-200 mt-1 block"><?= htmlspecialchars($ticket['autor_nombre'] ?? 'Desconocido') ?></span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Asignado a</span>
                    <span class="font-medium text-slate-200 mt-1 block"><?= htmlspecialchars($ticket['asignado_nombre'] ?? 'Sin Asignar') ?></span>
                </div>

                <!-- Nuevos Campos Solicitados -->
                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Fecha de Inicio</span>
                    <span class="font-mono text-xs text-slate-300 mt-1 block">
                        <?= !empty($ticket['start_date']) ? date('d/m/Y', strtotime($ticket['start_date'])) : '-' ?>
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Fecha Fin (Vencimiento)</span>
                    <span class="font-mono text-xs text-slate-300 mt-1 block">
                        <?= !empty($ticket['due_date']) ? date('d/m/Y', strtotime($ticket['due_date'])) : '-' ?>
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">Tiempo Estimado</span>
                    <span class="font-mono text-xs text-slate-300 mt-1 block">
                        <?= !empty($ticket['estimated_hours']) ? $ticket['estimated_hours'] . ' horas' : '-' ?>
                    </span>
                </div>

                <div>
                    <span class="block text-xs font-semibold text-slate-500 uppercase">% Realizado</span>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-20 bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-sky-500 h-full" style="width: <?= (int)($ticket['porcentaje_done'] ?? 0) ?>%"></div>
                        </div>
                        <span class="font-mono text-xs text-slate-300"><?= (int)($ticket['porcentaje_done'] ?? 0) ?>%</span>
                    </div>
                </div>

                <!-- Custom Fields -->
                <div>
                    <span class="block text-xs font-semibold text-amber-500 uppercase">Categoría</span>
                    <span class="font-medium text-amber-200 mt-1 block">
		    	    <?= !empty($ticket['categoria']) ? htmlspecialchars($ticket['categoria']) : (!empty($categoriaCustom) ? htmlspecialchars($categoriaCustom) : '<em class="text-slate-500 text-xs">Sin categoría</em>') ?>
		             </span>
                </div>
                <div>
                    <span class="block text-xs font-semibold text-amber-500 uppercase">PM Responsable</span>
                    <span class="font-medium text-amber-200 mt-1 block">
                        <?= !empty($pmResponsableCustom) ? htmlspecialchars($pmResponsableCustom) : '<em class="text-slate-600 text-xs">Sin definir</em>' ?>
                    </span>
                </div>
            </div>

            <!-- Descripción -->
            <div class="p-6 space-y-3 border-b border-slate-700/80">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Descripción</h3>
                <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 font-sans text-sm text-slate-300 whitespace-pre-wrap leading-relaxed min-h-[100px]">
                    <?= !empty(trim($ticket['descripcion'])) ? htmlspecialchars($ticket['descripcion']) : '<em class="text-slate-600">Sin descripción proporcionada.</em>' ?>
                </div>
            </div>

            <!-- Subtareas, Peticiones Relacionadas y Seguidores -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-6">
                <!-- Subtareas -->
                <div class="space-y-3">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Subtareas</span>
                        <span class="bg-slate-900 px-2 py-0.5 rounded text-slate-300 font-mono text-xs"><?= count($subtareas) ?></span>
                    </h3>
                    <?php if (empty($subtareas)): ?>
                        <p class="text-xs text-slate-500 italic bg-slate-900/50 p-3 rounded-lg border border-slate-800">Sin subtareas.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($subtareas as $sub): ?>
                                <a href="/index.php?page=ver_incidente&id=<?= $sub['id'] ?>" class="block bg-slate-900/70 hover:bg-slate-900 p-2.5 rounded-lg border border-slate-700/80 transition flex items-center justify-between">
                                    <div class="truncate mr-2">
                                        <span class="font-mono text-sky-400 text-xs font-bold">#<?= $sub['id'] ?></span>
                                        <span class="text-xs text-slate-200 ml-1"><?= htmlspecialchars($sub['asunto']) ?></span>
                                    </div>
                                    <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded border border-slate-700 font-mono"><?= (int)$sub['porcentaje_done'] ?>%</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Peticiones Relacionadas -->
                <div class="space-y-3">
                    <h3 class="text-xs font-semibold text-indigo-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Peticiones Relacionadas</span>
                        <span class="bg-slate-900 px-2 py-0.5 rounded text-indigo-300 font-mono text-xs"><?= count($relacionadas) ?></span>
                    </h3>
                    <?php if (empty($relacionadas)): ?>
                        <p class="text-xs text-slate-500 italic bg-slate-900/50 p-3 rounded-lg border border-slate-800">Sin tareas relacionadas.</p>
                    <?php else: ?>
                        <div class="space-y-2">
                            <?php foreach ($relacionadas as $rel): ?>
                                <a href="/index.php?page=ver_incidente&id=<?= $rel['id'] ?>" class="block bg-indigo-950/30 hover:bg-indigo-900/40 p-2.5 rounded-lg border border-indigo-500/30 transition flex items-center justify-between">
                                    <div class="truncate mr-2">
                                        <span class="font-mono text-indigo-400 text-xs font-bold">#<?= $rel['id'] ?></span>
                                        <span class="text-xs text-slate-200 ml-1"><?= htmlspecialchars($rel['asunto']) ?></span>
                                        <span class="block text-[10px] text-slate-400 mt-0.5"><?= htmlspecialchars($rel['proyecto_nombre']) ?></span>
                                    </div>
                                    <span class="text-[10px] bg-indigo-900/60 text-indigo-300 px-2 py-0.5 rounded border border-indigo-700/50 font-mono"><?= (int)$rel['porcentaje_done'] ?>%</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Seguidores -->
                <div class="space-y-3">
                    <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wider flex items-center justify-between">
                        <span>Seguidores (Watchers)</span>
                        <span class="bg-slate-900 px-2 py-0.5 rounded text-slate-300 font-mono text-xs"><?= count($seguidores) ?></span>
                    </h3>
                    <?php if (empty($seguidores)): ?>
                        <p class="text-xs text-slate-500 italic bg-slate-900/50 p-3 rounded-lg border border-slate-800">Sin seguidores.</p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach ($seguidores as $seg): ?>
                                <span class="bg-slate-900 text-slate-300 border border-slate-700 px-2.5 py-1 rounded-lg text-xs flex items-center gap-1">
                                    <span>👤</span> <?= htmlspecialchars($seg) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
