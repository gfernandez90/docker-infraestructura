<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';

$mensajeExito = null;
$mensajeError = null;

// Mapeo exacto con IDs de la base de datos de Redmine
$usuariosRedmineMap = [
    'dgeip infraestructura'  => 7,
    'florencia del castillo' => 379,
    'gastón caballero'       => 409,
    'gustavo lembo'          => 7, // Fallback a DGEIP Infraestructura si no tiene ID
    'hector kusminsky'       => 413,
    'pedro sanabria'         => 106,
    'ricardo roque'          => 101,
    'gabriel fernández'      => 5,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proyecto_btn'])) {
    try {
        $asunto = trim($_POST['asunto'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $subtareasRaw = $_POST['subtareas'] ?? [];
        $asignadosRaw = $_POST['asignados'] ?? [];
        $horasRaw = $_POST['horas'] ?? [];

        if (empty($asunto)) {
            throw new Exception("El asunto del proyecto es obligatorio.");
        }

        $redmine = new RedmineService();

        // 1. Obtener Tracker "Tarea"
        $trackerId = 1;
        try {
            $trackersList = $redmine->getRequest('/trackers.json');
            if (!empty($trackersList['trackers'])) {
                foreach ($trackersList['trackers'] as $tr) {
                    if (mb_strtolower($tr['name']) === 'tarea' || mb_strtolower($tr['name']) === 'task') {
                        $trackerId = $tr['id'];
                        break;
                    }
                }
            }
        } catch (Exception $e) {}

/*	// 2. Obtener/Asignar ID de Categoría "Proyecto"
	$idCategoriaProyecto = null;
	try {
	    $categoriesList = $redmine->getRequest('/projects/incidentes-diarios/categories.json');
	    if (!empty($categoriesList['issue_categories'])) {
	        foreach ($categoriesList['issue_categories'] as $cat) {
	            if (mb_strtolower(trim($cat['name'])) === 'proyecto') {
	                $idCategoriaProyecto = (int)$cat['id'];
	                break;
	            }
	        }
	    }
	} catch (Exception $e) {}
	
	// Si la API no devuelve la categoría por temas de permisos, colocamos el ID directo de tu Redmine
	if (!$idCategoriaProyecto) {
	    // Reemplaza este número por el ID real de la categoría "Proyecto" en Redmine
	    $idCategoriaProyecto = 12; 
	}

        // 3. Resolver ID del grupo DGEIP Infraestructura
        $idDgeipInfra = $usuariosRedmineMap['dgeip infraestructura'] ?? null;

        // 4. Crear Tarea Padre en "Incidentes Diarios" (slug: incidentes-diarios)
        $dataPadre = [
            'project_id'   => 'incidentes-diarios',
            'tracker_id'   => $trackerId,
            'status_id'    => 1, // Nueva
            'priority_id'  => 2, // Normal
            'subject'      => $asunto,
            'description'  => $descripcion,
            'custom_fields' => [
                [
                    'id'    => 71, // Custom field PM Responsable
                    'value' => 'Gabriel Fernandez'
                ]
            ]
        ];
*/
	// 1. Mapeo de Categorías de Redmine SITA
	$idCategoriaProyecto = 108; // ID exacto de la categoría 'Proyecto' extraído de SITA
	
	// 2. ID de DGEIP Infraestructura
	$idDgeipInfra = 7;
	
	// 3. Payload de la Tarea Padre
	$dataPadre = [
	    'project_id'     => 'incidentes-diarios',
	    'tracker_id'     => $trackerId,
	    'status_id'      => 1,   // Nueva
	    'priority_id'    => 2,   // Normal
	    'subject'        => $asunto,
	    'description'    => $descripcion,
	    'category_id'    => $idCategoriaProyecto, // Asigna "Proyecto" (ID 108)
	    'assigned_to_id' => $idDgeipInfra,       // Asigna a DGEIP Infraestructura (ID 7)
	    'custom_fields'  => [
	        [
	            'id'    => 71, // Campo "PM Responsable"
	            'value' => 'Gabriel Fernandez'
	        ]
	    ]
	];
        if ($idCategoriaProyecto !== null) {
            $dataPadre['category_id'] = $idCategoriaProyecto;
        }

        if ($idDgeipInfra !== null) {
            $dataPadre['assigned_to_id'] = $idDgeipInfra;
        }

        $padreCreado = $redmine->crearTarea($dataPadre);

        if (!$padreCreado || !isset($padreCreado['id'])) {
            throw new Exception("No se pudo obtener el ID de la tarea padre creada en Redmine.");
        }

        $padreId = $padreCreado['id'];
        $subtareasCreadasCount = 0;

        // 5. Crear Subtareas en "Proyectos Infraestructura" (slug: proyectos-infraestructura)
        foreach ($subtareasRaw as $index => $subAsuntoRaw) {
            $subAsunto = trim($subAsuntoRaw);
            if (empty($subAsunto)) continue;

            $nombreAsignado = mb_strtolower(trim($asignadosRaw[$index] ?? 'dgeip infraestructura'));
            $horasEstimadas = !empty($horasRaw[$index]) ? (float)$horasRaw[$index] : null;

            // Obtener ID numérico de usuario/grupo de Redmine
            $assignedId = $usuariosRedmineMap[$nombreAsignado] ?? $idDgeipInfra;

            $dataHijo = [
                'project_id'      => 'proyectos-infraestructura',
                'tracker_id'      => $trackerId,
                'status_id'       => 1, // Nueva
                'priority_id'     => 2, // Normal
                'parent_issue_id' => $padreId, // Vinculación a la Tarea Padre
                'subject'         => $subAsunto,
                'description'     => $descripcion, // Hereda la descripción
                'custom_fields'   => [
                    [
                        'id'    => 71,
                        'value' => 'Gabriel Fernandez'
                    ]
                ]
            ];

            if ($assignedId !== null) {
                $dataHijo['assigned_to_id'] = $assignedId;
            }

            if ($horasEstimadas !== null && $horasEstimadas > 0) {
                $dataHijo['estimated_hours'] = $horasEstimadas;
            }

            $redmine->crearTarea($dataHijo);
            $subtareasCreadasCount++;
        }

        $mensajeExito = "🚀 ¡Proyecto creado con éxito! Tarea Padre (#{$padreId}) asignada a DGEIP Infraestructura con categoría 'Proyecto'. Subtareas asignadas e ingresadas: {$subtareasCreadasCount}.";

    } catch (Exception $e) {
        $mensajeError = $e->getMessage();
    }
}
?>

<div class="space-y-6">
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
        <div class="bg-indigo-500/10 border-b border-indigo-500/20 p-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-indigo-400 font-bold text-lg">
                <span>➕</span>
                <h2>Crear Nuevo Proyecto de Infraestructura</h2>
            </div>
            <span class="text-xs text-slate-400 bg-slate-900/60 px-2.5 py-1 rounded-full border border-slate-700">Redmine SITA</span>
        </div>

        <form method="POST" action="/index.php?page=crear_proyecto" class="p-6 space-y-5">
            <input type="hidden" name="crear_proyecto_btn" value="1">

            <div>
                <label for="asunto" class="block text-xs font-semibold text-slate-300 uppercase mb-2">Asunto del Proyecto *</label>
                <input type="text" name="asunto" id="asunto" required placeholder="Ej: Migración de Servidores GLPI a PostgreSQL 17" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-sm text-slate-100 focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label for="descripcion" class="block text-xs font-semibold text-slate-300 uppercase mb-2">Descripción General del Proyecto</label>
                <textarea name="descripcion" id="descripcion" rows="4" placeholder="Detalles de arquitectura, alcance, requerimientos..." class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-sm text-slate-100 focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="pt-2">
                <label class="block text-xs font-semibold text-slate-300 uppercase mb-2">Lista de Tareas (se crearán en "Proyectos Infraestructura")</label>
                
                <div id="taskList" class="space-y-3">
                    <!-- Fila Tarea Inicial -->
                    <div class="p-3 bg-slate-950/60 border border-slate-700/80 rounded-lg flex flex-col md:flex-row gap-3 items-center">
                        <input type="text" name="subtareas[]" required placeholder="Título de la tarea..." class="flex-1 w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500">
                        
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <!-- Combo Asignado A -->
                            <select name="asignados[]" class="bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                                <option value="DGEIP Infraestructura" selected>DGEIP Infraestructura</option>
                                <option value="Florencia Del Castillo">Florencia Del Castillo</option>
                                <option value="Gastón Caballero">Gastón Caballero</option>
                                <option value="Gustavo Lembo">Gustavo Lembo</option>
                                <option value="Hector Kusminsky">Hector Kusminsky</option>
                                <option value="Pedro Sanabria">Pedro Sanabria</option>
                                <option value="Ricardo Roque">Ricardo Roque</option>
                                <option value="Gabriel Fernández">Gabriel Fernández</option>
                            </select>

                            <!-- Campo Horas Estimadas -->
                            <input type="number" step="0.5" min="0" name="horas[]" placeholder="Horas" title="Horas estimadas" class="w-20 bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-xs text-slate-100 text-center focus:outline-none focus:border-indigo-500">
                            
                            <button type="button" onclick="this.closest('.p-3').remove()" class="text-red-400 hover:text-red-300 px-2 font-bold">✕</button>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addTaskRow()" class="mt-3 text-xs font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                    ➕ Agregar Tarea a la Lista
                </button>
            </div>

            <div class="pt-4 border-t border-slate-700/80 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-lg transition duration-150 flex items-center gap-2 shadow-lg">
                    <span>🚀</span> Crear Proyecto & Generar Tareas
                </button>
            </div>
        </form>
    </div>

    <?php if ($mensajeError): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl flex items-center gap-3">
            <span class="text-xl">❌</span>
            <p class="text-sm font-mono"><?= htmlspecialchars($mensajeError) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($mensajeExito): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl flex items-center gap-3">
            <span class="text-xl">✅</span>
            <p class="text-sm font-semibold"><?= htmlspecialchars($mensajeExito) ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
function addTaskRow() {
    const container = document.getElementById('taskList');
    const div = document.createElement('div');
    div.className = 'p-3 bg-slate-950/60 border border-slate-700/80 rounded-lg flex flex-col md:flex-row gap-3 items-center';
    div.innerHTML = `
        <input type="text" name="subtareas[]" required placeholder="Nueva tarea..." class="flex-1 w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 text-sm text-slate-100 focus:outline-none focus:border-indigo-500">
        <div class="flex items-center gap-2 w-full md:w-auto">
            <select name="asignados[]" class="bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-xs text-slate-200 focus:outline-none focus:border-indigo-500">
                <option value="DGEIP Infraestructura" selected>DGEIP Infraestructura</option>
                <option value="Florencia Del Castillo">Florencia Del Castillo</option>
                <option value="Gastón Caballero">Gastón Caballero</option>
                <option value="Gustavo Lembo">Gustavo Lembo</option>
                <option value="Hector Kusminsky">Hector Kusminsky</option>
                <option value="Pedro Sanabria">Pedro Sanabria</option>
                <option value="Ricardo Roque">Ricardo Roque</option>
                <option value="Gabriel Fernández">Gabriel Fernández</option>
            </select>
            <input type="number" step="0.5" min="0" name="horas[]" placeholder="Horas" title="Horas estimadas" class="w-20 bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-xs text-slate-100 text-center focus:outline-none focus:border-indigo-500">
            <button type="button" onclick="this.closest('.p-3').remove()" class="text-red-400 hover:text-red-300 px-2 font-bold">✕</button>
        </div>
    `;
    container.appendChild(div);
}
</script>
