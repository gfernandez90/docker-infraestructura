<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';

$mensaje = null;
$error = null;
$detallesSync = [];

$proyectosObjetivo = [
    'redes-draft',
    'subidas-a-produccion'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ejecutar_sync'])) {
    try {
        $redmine = new RedmineService();

        // 1. Limpieza de tablas (TRUNCATE)
        $pdo->exec("
            TRUNCATE TABLE redmine_tareas, redmine_proyectos, redmine_estados, redmine_prioridades, redmine_usuarios, redmine_tarea_seguidores CASCADE;
        ");

        $totalProyectos = 0;
        $totalTareas = 0;

        // Sentencias preparadas
        $stmtProj = $pdo->prepare("
            INSERT INTO redmine_proyectos (id, identifier, nombre, descripcion, created_on, updated_on)
            VALUES (:id, :identifier, :nombre, :descripcion, :created_on, :updated_on)
            ON CONFLICT (id) DO UPDATE SET nombre = EXCLUDED.nombre;
        ");

        $stmtUser = $pdo->prepare("
            INSERT INTO redmine_usuarios (id, nombre_completo) VALUES (:id, :nombre)
            ON CONFLICT (id) DO UPDATE SET nombre_completo = EXCLUDED.nombre_completo;
        ");

        $stmtEstado = $pdo->prepare("
            INSERT INTO redmine_estados (id, nombre, is_closed) VALUES (:id, :nombre, :is_closed)
            ON CONFLICT (id) DO NOTHING;
        ");

        $stmtPrioridad = $pdo->prepare("
            INSERT INTO redmine_prioridades (id, nombre) VALUES (:id, :nombre)
            ON CONFLICT (id) DO NOTHING;
        ");

        // INSERT corregido (19 columnas = 19 placeholders)
        $stmtIssue = $pdo->prepare("
            INSERT INTO redmine_tareas (
                id, proyecto_id, parent_id, tracker_nombre, categoria, estado_id, prioridad_id, asunto, descripcion,
                autor_id, asignado_a_id, porcentaje_done, estimated_hours, spent_hours,
                start_date, due_date, created_on, updated_on, closed_on
            ) VALUES (
                :id, :proyecto_id, :parent_id, :tracker_nombre, :categoria, :estado_id, :prioridad_id, :asunto, :descripcion,
                :autor_id, :asignado_a_id, :porcentaje_done, :estimated_hours, :spent_hours,
                :start_date, :due_date, :created_on, :updated_on, :closed_on
            );
        ");

        // Statement para guardar los seguidores/watchers
        $stmtWatcher = $pdo->prepare("
            INSERT INTO redmine_tarea_seguidores (tarea_id, usuario_id)
            VALUES (:tarea_id, :usuario_id)
            ON CONFLICT DO NOTHING;
        ");

        foreach ($proyectosObjetivo as $projIdentifier) {
            // A) Obtener Proyecto
            $projData = $redmine->getProyecto($projIdentifier);
            if (!$projData) {
                $detallesSync[] = "⚠️ Proyecto no encontrado o inaccesible: {$projIdentifier}";
                continue;
            }

            $stmtProj->execute([
                ':id'          => $projData['id'],
                ':identifier'  => $projData['identifier'],
                ':nombre'      => $projData['name'],
                ':descripcion' => $projData['description'] ?? null,
                ':created_on'  => $projData['created_on'] ?? null,
                ':updated_on'  => $projData['updated_on'] ?? null
            ]);
            $totalProyectos++;

            // B) Obtener Tareas del Proyecto
            $issues = $redmine->getTareasPorProyecto($projIdentifier);

            foreach ($issues as $issue) {
                // Sincronizar Usuario Autor
                if (isset($issue['author'])) {
                    $stmtUser->execute([
                        ':id'     => $issue['author']['id'],
                        ':nombre' => $issue['author']['name']
                    ]);
                }

                // Sincronizar Usuario Asignado
                $asignadoId = null;
                if (isset($issue['assigned_to'])) {
                    $asignadoId = $issue['assigned_to']['id'];
                    $stmtUser->execute([
                        ':id'     => $issue['assigned_to']['id'],
                        ':nombre' => $issue['assigned_to']['name']
                    ]);
                }

                // Sincronizar Estado
                if (isset($issue['status'])) {
                    $stmtEstado->execute([
                        ':id'        => $issue['status']['id'],
                        ':nombre'    => $issue['status']['name'],
                        ':is_closed' => isset($issue['status']['is_closed']) ? ($issue['status']['is_closed'] ? 1 : 0) : 0
                    ]);
                }

                // Sincronizar Prioridad
                if (isset($issue['priority'])) {
                    $stmtPrioridad->execute([
                        ':id'     => $issue['priority']['id'],
                        ':nombre' => $issue['priority']['name']
                    ]);
                }

                // Capturar Categoría nativa de Redmine
                $categoriaNombre = $issue['category']['name'] ?? null;

                // Insertar Tarea (Exactamente 19 parámetros mapeados)
                $stmtIssue->execute([
                    ':id'              => $issue['id'],
                    ':proyecto_id'     => $projData['id'],
                    ':parent_id'       => $issue['parent']['id'] ?? null,
                    ':tracker_nombre'  => $issue['tracker']['name'] ?? 'Tarea',
                    ':categoria'       => $categoriaNombre,
                    ':estado_id'       => $issue['status']['id'] ?? null,
                    ':prioridad_id'    => $issue['priority']['id'] ?? null,
                    ':asunto'          => $issue['subject'],
                    ':descripcion'     => $issue['description'] ?? null,
                    ':autor_id'        => $issue['author']['id'] ?? null,
                    ':asignado_a_id'   => $asignadoId,
                    ':porcentaje_done' => $issue['done_ratio'] ?? 0,
                    ':estimated_hours' => $issue['estimated_hours'] ?? null,
                    ':spent_hours'     => $issue['spent_hours'] ?? null,
                    ':start_date'      => $issue['start_date'] ?? null,
                    ':due_date'        => $issue['due_date'] ?? null,
                    ':created_on'      => $issue['created_on'] ?? null,
                    ':updated_on'      => $issue['updated_on'] ?? null,
                    ':closed_on'       => $issue['closed_on'] ?? null
                ]);

                // Sincronizar Seguidores / Watchers (si existen)
                if (isset($issue['watchers']) && is_array($issue['watchers'])) {
                    foreach ($issue['watchers'] as $watcher) {
                        $stmtUser->execute([
                            ':id'     => $watcher['id'],
                            ':nombre' => $watcher['name']
                        ]);

                        $stmtWatcher->execute([
                            ':tarea_id'   => $issue['id'],
                            ':usuario_id' => $watcher['id']
                        ]);
                    }
                }

                $totalTareas++;
            }

            $detallesSync[] = "✅ Proyecto '{$projData['name']}' ({$projIdentifier}): " . count($issues) . " tareas sincronizadas.";
        }

        $mensaje = "Sincronización completada exitosamente. Se importaron {$totalProyectos} proyectos y {$totalTareas} tareas con sus relaciones de parentesco, categorías y seguidores.";

    } catch (Exception $e) {
        $error = "Error durante la sincronización: " . $e->getMessage();
    }
}

// Consultar estado actual de datos localmente
$cantProyectos  = $pdo->query("SELECT COUNT(*) FROM redmine_proyectos")->fetchColumn();
$cantTareas     = $pdo->query("SELECT COUNT(*) FROM redmine_tareas")->fetchColumn();
$cantSeguidores = $pdo->query("SELECT COUNT(*) FROM redmine_tarea_seguidores")->fetchColumn();
?>

<div class="space-y-6">
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
        <div class="bg-red-500/10 border-b border-red-500/20 p-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-red-400 font-bold text-lg">
                <span>🔄</span>
                <h2>Sincronización con Redmine</h2>
            </div>
            <span class="text-xs text-slate-400 bg-slate-900/60 px-2.5 py-1 rounded-full border border-slate-700">API Redmine SITA</span>
        </div>

        <div class="p-6 space-y-6">
            <!-- Info del origen de datos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-slate-900/70 p-4 rounded-lg border border-slate-700/80">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Servidor Redmine</span>
                    <p class="text-slate-200 font-mono text-sm mt-1">https://sita.anep.edu.uy/</p>
                </div>
                <div class="bg-slate-900/70 p-4 rounded-lg border border-slate-700/80">
                    <span class="text-xs text-slate-400 uppercase font-semibold">Proyectos Destino</span>
                    <div class="flex gap-2 mt-1">
                        <span class="bg-slate-800 text-red-300 font-mono text-xs px-2.5 py-1 rounded border border-slate-700">redes-draft</span>
                        <span class="bg-slate-800 text-red-300 font-mono text-xs px-2.5 py-1 rounded border border-slate-700">subidas-a-produccion</span>
                    </div>
                </div>
            </div>

            <!-- Resumen actual en BD -->
            <div class="bg-slate-950 p-4 rounded-lg border border-slate-800 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-slate-300">Registros actuales en PostgreSQL:</h4>
                    <p class="text-xs text-slate-500 mt-0.5">Estado local de la base de datos de infraestructura.</p>
                </div>
                <div class="flex gap-4">
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-white"><?= $cantProyectos ?></span>
                        <span class="text-xs text-slate-400">Proyectos</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-red-400"><?= $cantTareas ?></span>
                        <span class="text-xs text-slate-400">Tareas</span>
                    </div>
                    <div class="text-center">
                        <span class="block text-2xl font-bold text-amber-400"><?= $cantSeguidores ?></span>
                        <span class="text-xs text-slate-400">Seguidores</span>
                    </div>
                </div>
            </div>

            <!-- Botón de Ejecución -->
            <form method="POST" action="/index.php?page=sync_redmine" class="pt-2">
                <input type="hidden" name="ejecutar_sync" value="1">
                <button type="submit"
                        onclick="return confirm('⚠️ Esto vaciará las tablas de Redmine en PostgreSQL y las volverá a cargar completamente. ¿Deseas continuar?');"
                        class="w-full md:w-auto bg-red-600 hover:bg-red-700 text-white font-bold px-6 py-3 rounded-lg transition duration-150 flex items-center justify-center gap-2 shadow-lg">
                    <span>🔄</span> Limpiar Base de Datos y Sincronizar Ahora
                </button>
            </form>
        </div>
    </div>

    <!-- Feedback / Alertas -->
    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl flex items-start gap-3">
            <span class="text-xl">❌</span>
            <div>
                <h4 class="font-bold text-red-300">Error durante la sincronización:</h4>
                <p class="text-sm mt-1"><?= htmlspecialchars($error) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($mensaje): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl space-y-2">
            <div class="flex items-center gap-2 font-bold text-lg">
                <span>✅</span>
                <h3><?= htmlspecialchars($mensaje) ?></h3>
            </div>
            <?php if (!empty($detallesSync)): ?>
                <ul class="text-xs font-mono space-y-1 pl-6 list-disc text-emerald-300">
                    <?php foreach ($detallesSync as $det): ?>
                        <li><?= htmlspecialchars($det) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
