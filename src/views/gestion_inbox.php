<?php
require_once __DIR__ . '/../config/db.php';

// Filtros
$estadoFiltro    = $_GET['estado'] ?? 'abiertas';
$busqueda        = trim($_GET['q'] ?? '');

// Construir Query
$sql = "
    SELECT 
        t.id,
        t.asunto,
        t.descripcion,
        t.tracker_nombre,
        t.porcentaje_done,
        t.created_on,
        t.updated_on,
        e.nombre AS estado_nombre,
        e.is_closed,
        p.nombre AS prioridad_nombre,
        u_autor.nombre_completo AS autor_nombre,
        u_asig.nombre_completo AS asignado_nombre,
        proj.nombre AS proyecto_nombre,
        proj.identifier AS proyecto_identifier,
        t.parent_id
    FROM redmine_tareas t
    LEFT JOIN redmine_estados e ON t.estado_id = e.id
    LEFT JOIN redmine_prioridades p ON t.prioridad_id = p.id
    LEFT JOIN redmine_usuarios u_autor ON t.autor_id = u_autor.id
    LEFT JOIN redmine_usuarios u_asig ON t.asignado_a_id = u_asig.id
    LEFT JOIN redmine_proyectos proj ON t.proyecto_id = proj.id
    WHERE 1=1
";

$params = [];

if ($estadoFiltro === 'abiertas') {
    $sql .= " AND (e.is_closed = FALSE OR e.is_closed IS NULL)";
} elseif ($estadoFiltro === 'cerradas') {
    $sql .= " AND e.is_closed = TRUE";
}

if (!empty($busqueda)) {
    $sql .= " AND (t.asunto ILIKE :q OR CAST(t.id AS TEXT) LIKE :q_id OR u_autor.nombre_completo ILIKE :q)";
    $params[':q'] = "%{$busqueda}%";
    $params[':q_id'] = "%{$busqueda}%";
}

$sql .= " ORDER BY t.created_on DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de usuarios para asignaciones en el Modal
$usuarios = $pdo->query("SELECT id, nombre_completo FROM redmine_usuarios ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                📥 Mesa de Entrada - Incidentes & Solicitudes
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                Clasificación de tickets entrantes y promoción a Proyectos de Infraestructura.
            </p>
        </div>
        
        <!-- Filtros y Buscador -->
        <form method="GET" action="/index.php" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="page" value="gestion_inbox">
            
            <input type="text" 
                   name="q" 
                   value="<?= htmlspecialchars($busqueda) ?>" 
                   placeholder="Buscar por ID, asunto, autor..." 
                   class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500 w-64">

            <select name="estado" onchange="this.form.submit()" class="bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500">
                <option value="abiertas" <?= $estadoFiltro === 'abiertas' ? 'selected' : '' ?>>Abiertas</option>
                <option value="cerradas" <?= $estadoFiltro === 'cerradas' ? 'selected' : '' ?>>Cerradas</option>
                <option value="todas" <?= $estadoFiltro === 'todas' ? 'selected' : '' ?>>Todas</option>
            </select>

            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Filtrar
            </button>
        </form>
    </div>

    <!-- Tabla de Tareas -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/80 text-slate-400 text-xs uppercase font-semibold border-b border-slate-700">
                    <tr>
                        <th class="p-4 w-16">ID</th>
                        <th class="p-4">Proyecto / Tracker</th>
                        <th class="p-4">Asunto</th>
                        <th class="p-4">Solicitante</th>
                        <th class="p-4">Asignado</th>
                        <th class="p-4">Estado</th>
                        <th class="p-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/60">
                    <?php if (empty($tareas)): ?>
                        <tr>
                            <td colspan="7" class="p-8 text-center text-slate-500">
                                No se encontraron tickets con los criterios ingresados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tareas as $t): ?>
                            <tr class="hover:bg-slate-700/30 transition">
                                <td class="p-4 font-mono text-emerald-400 font-bold">
                                    #<?= $t['id'] ?>
                                </td>
                                <td class="p-4">
                                    <span class="block text-xs font-semibold text-slate-400"><?= htmlspecialchars($t['proyecto_nombre'] ?? 'N/A') ?></span>
                                    <span class="inline-block bg-slate-900 text-slate-300 text-[10px] px-2 py-0.5 rounded mt-1 border border-slate-700">
                                        <?= htmlspecialchars($t['tracker_nombre'] ?? 'Tarea') ?>
                                    </span>
                                </td>
				<td class="p-4 font-medium text-slate-100 max-w-md">
				    <a href="/index.php?page=ver_incidente&id=<?= $t['id'] ?>" class="hover:underline hover:text-sky-300">
				        <?= htmlspecialchars($t['asunto']) ?>
				    </a>
				    <?php if ($t['parent_id']): ?>
					        <span class="block text-[11px] text-amber-400 mt-0.5">↳ Subtarea de #<?= $t['parent_id'] ?></span>
				    <?php endif; ?>
				</td>
                                <td class="p-4 text-slate-300 text-xs">
                                    <?= htmlspecialchars($t['autor_nombre'] ?? 'Desconocido') ?>
                                </td>
                                <td class="p-4 text-slate-300 text-xs">
                                    <?= htmlspecialchars($t['asignado_nombre'] ?? 'Sin Asignar') ?>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $t['is_closed'] ? 'bg-slate-700 text-slate-400' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' ?>">
                                        <?= htmlspecialchars($t['estado_nombre'] ?? 'Nuevo') ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <button onclick="openPromoteModal(<?= htmlspecialchars(json_encode($t)) ?>)" 
                                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition shadow flex items-center gap-1 mx-auto">
                                        <span>🚀</span> Promover a Proyecto
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Promover a Proyecto de Infraestructura -->
<div id="promoteModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4">
    <div class="bg-slate-800 border border-slate-700 rounded-xl shadow-2xl max-w-2xl w-full p-6 space-y-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b border-slate-700 pb-4">
            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                🚀 Promover Ticket <span id="modalTicketId" class="text-emerald-400">#</span> a Proyecto de Infraestructura
            </h3>
            <button onclick="closePromoteModal()" class="text-slate-400 hover:text-white text-xl">✕</button>
        </div>

        <form id="promoteForm" onsubmit="handlePromote(event)" class="space-y-4">
            <input type="hidden" id="originTicketId">

            <div>
                <label class="block text-xs font-semibold text-slate-400 uppercase mb-1">Ticket Origen (SITA)</label>
                <input type="text" id="modalTicketSubject" readonly class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-slate-300 text-sm font-medium">
            </div>

            <hr class="border-slate-700">

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Asunto de la Tarea Padre (Proyecto)</label>
                <input type="text" id="parentSubject" required class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Asignar Responsable en Infraestructura</label>
                <select id="assignedUser" class="w-full bg-slate-900 border border-slate-700 rounded-lg p-2.5 text-slate-100 text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="">-- Seleccionar Funcionario --</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombre_completo']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Checklist / Subtareas Iniciales</label>
                <div id="subtasksContainer" class="space-y-2">
                    <div class="flex gap-2">
                        <input type="text" placeholder="Ej: Configurar VM en Proxmox" class="subtask-input flex-1 bg-slate-900 border border-slate-700 rounded-lg p-2 text-sm text-slate-100 focus:outline-none">
                        <button type="button" onclick="removeSubtaskRow(this)" class="text-red-400 hover:text-red-300 px-2">✕</button>
                    </div>
                </div>
                <button type="button" onclick="addSubtaskRow()" class="mt-2 text-xs font-semibold text-indigo-400 hover:text-indigo-300 flex items-center gap-1">
                    ➕ Agregar Subtarea al Checklist
                </button>
            </div>

            <div class="pt-4 border-t border-slate-700 flex justify-end gap-3">
                <button type="button" onclick="closePromoteModal()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-slate-200 rounded-lg text-sm font-semibold transition">
                    Cancelar
                </button>
                <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold transition flex items-center gap-2">
                    <span>⚡</span> Generar Proyecto
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPromoteModal(ticket) {
    document.getElementById('modalTicketId').innerText = '#' + ticket.id;
    document.getElementById('modalTicketSubject').value = ticket.asunto;
    document.getElementById('originTicketId').value = ticket.id;
    document.getElementById('parentSubject').value = '[Proyecto] ' + ticket.asunto;
    
    document.getElementById('promoteModal').classList.remove('hidden');
}

function closePromoteModal() {
    document.getElementById('promoteModal').classList.add('hidden');
}

function addSubtaskRow() {
    const container = document.getElementById('subtasksContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2';
    div.innerHTML = `
        <input type="text" placeholder="Ej: Crear base de datos PostgreSQL" class="subtask-input flex-1 bg-slate-900 border border-slate-700 rounded-lg p-2 text-sm text-slate-100 focus:outline-none">
        <button type="button" onclick="removeSubtaskRow(this)" class="text-red-400 hover:text-red-300 px-2">✕</button>
    `;
    container.appendChild(div);
}

function removeSubtaskRow(btn) {
    btn.parentElement.remove();
}

function handlePromote(e) {
    e.preventDefault();
    const originId = document.getElementById('originTicketId').value;
    const parentSubject = document.getElementById('parentSubject').value;
    const assignedUser = document.getElementById('assignedUser').value;
    
    const subtaskInputs = document.querySelectorAll('.subtask-input');
    const subtasks = Array.from(subtaskInputs).map(i => i.value.trim()).filter(v => v.length > 0);

    alert(`🚀 Plan de Promoción Creado!\n\n- Ticket Origen: #${originId}\n- Proyecto Padre: "${parentSubject}"\n- Subtareas Checklist: ${subtasks.length}\n\n(En la siguiente etapa conectaremos este botón directamente a la API de Redmine para crear las tareas automáticamente).`);
    
    closePromoteModal();
}
</script>
