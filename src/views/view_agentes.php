<?php
// src/views/view_agentes.php

// OBTENER DATOS PARA EL LISTADO
$query = "
    SELECT a.*, 
           STRING_AGG(s.nombre, '||') as sistemas_nombres
    FROM agentes a
    LEFT JOIN sistema_agentes sa ON a.id = sa.agente_id
    LEFT JOIN sistemas s ON sa.sistema_id = s.id
    GROUP BY a.id
    ORDER BY a.nombre ASC
";
$agentes = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$cargosPermitidos = ['PM / Coordinador', 'Desarrollador', 'Analista Funcional', 'Tester', 'SysAdmin', 'DevOps'];
?>

<div class="max-w-7xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">👥 Gestión de Personal (Actores)</h2>
            <p class="text-slate-500 text-sm mt-1">Administración de equipo y sus asignaciones a sistemas</p>
        </div>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-4 py-3 rounded relative mb-4">
            <span class="block sm:inline font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
        <!-- Pestañas -->
        <div class="border-b border-slate-200 bg-slate-50 flex overflow-x-auto">
            <button type="button" onclick="showTab('tab-listado')" id="btn-tab-listado" class="tab-btn py-4 px-6 border-b-2 font-bold text-sm text-indigo-600 border-indigo-600">📑 Listado y Matriz</button>
            <button type="button" onclick="showTab('tab-nuevo')" id="btn-tab-nuevo" class="tab-btn py-4 px-6 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">➕ Añadir Nuevo</button>
        </div>

        <!-- TAB: LISTADO -->
        <div id="tab-listado" class="tab-content block p-0">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs uppercase font-medium text-slate-500 text-left">
                    <tr><th class="px-6 py-4">Actor</th><th class="px-6 py-4">Cargo</th><th class="px-6 py-4">Sistemas Asignados</th><th class="px-6 py-4 text-right">Acciones</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white text-sm">
                    <?php foreach ($agentes as $ag): 
                        $sistemasAsignados = $ag['sistemas_nombres'] ? explode('||', $ag['sistemas_nombres']) : [];
                    ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-bold text-slate-800"><?= htmlspecialchars($ag['nombre']) ?></td>
                            <td class="px-6 py-4"><span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-1 rounded text-xs font-semibold"><?= htmlspecialchars($ag['cargo']) ?></span></td>
                            <td class="px-6 py-4">
                                <?php if(empty($sistemasAsignados)): ?>
                                    <span class="text-slate-400 text-xs italic">Sin sistemas</span>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-1">
                                        <?php foreach($sistemasAsignados as $sis): ?>
                                            <span class="bg-slate-100 border border-slate-200 text-slate-600 text-[10px] px-1.5 py-0.5 rounded"><?= htmlspecialchars($sis) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <button type="button" onclick="abrirEdicion(<?= $ag['id'] ?>, '<?= htmlspecialchars(addslashes($ag['nombre'])) ?>', '<?= $ag['cargo'] ?>')" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-2 py-1 rounded text-xs font-medium">✏️ Editar</button>
                                <!-- Apunta a guardar_agente -->
                                <form action="/index.php?page=guardar_agente" method="POST" class="inline-block" onsubmit="return confirm('¿Eliminar a este actor? Se removerá de todos los sistemas.');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $ag['id'] ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-50 px-2 py-1 rounded text-xs font-medium">🗑️ Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($agentes)): ?><tr><td colspan="4" class="text-center py-6 text-slate-500">No hay personal registrado</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- TAB: NUEVO -->
        <div id="tab-nuevo" class="tab-content hidden p-6">
            <!-- Apunta a guardar_agente -->
            <form action="/index.php?page=guardar_agente" method="POST" class="max-w-xl bg-slate-50 p-6 rounded border border-slate-200">
                <input type="hidden" name="accion" value="crear">
                <h3 class="font-bold text-slate-800 mb-4 text-lg">Registrar Actor</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre Completo *</label>
                        <input type="text" name="nombre" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cargo / Rol *</label>
                        <select name="cargo" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                            <?php foreach($cargosPermitidos as $cp): ?><option value="<?= $cp ?>"><?= $cp ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="pt-2 text-right">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm font-medium shadow-sm hover:bg-indigo-700">Guardar Actor</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDICIÓN -->
<div id="modal-editar" class="fixed inset-0 bg-slate-900/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-800 text-lg">✏️ Editar Actor</h3>
            <button onclick="cerrarEdicion()" class="text-slate-400 hover:text-slate-600 font-bold text-xl">&times;</button>
        </div>
        <!-- Apunta a guardar_agente -->
        <form action="/index.php?page=guardar_agente" method="POST" class="p-6">
            <input type="hidden" name="accion" value="editar">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Cargo</label>
                    <select name="cargo" id="edit_cargo" required class="w-full border border-slate-300 rounded px-3 py-2 text-sm bg-white">
                        <?php foreach($cargosPermitidos as $cp): ?><option value="<?= $cp ?>"><?= $cp ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="pt-4 flex justify-end gap-2">
                    <button type="button" onclick="cerrarEdicion()" class="border border-slate-300 px-4 py-2 rounded text-sm text-slate-700 hover:bg-slate-50">Cancelar</button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-indigo-700">Actualizar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => { el.classList.remove('text-indigo-600', 'border-indigo-600', 'font-bold'); el.classList.add('text-slate-500', 'border-transparent', 'font-medium'); });
    document.getElementById(tabId).classList.remove('hidden');
    const btn = document.getElementById('btn-' + tabId);
    btn.classList.remove('text-slate-500', 'border-transparent', 'font-medium');
    btn.classList.add('text-indigo-600', 'border-indigo-600', 'font-bold');
}
function abrirEdicion(id, nombre, cargo) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_nombre').value = nombre;
    document.getElementById('edit_cargo').value = cargo;
    const modal = document.getElementById('modal-editar');
    modal.classList.remove('hidden'); modal.classList.add('flex');
}
function cerrarEdicion() {
    const modal = document.getElementById('modal-editar');
    modal.classList.add('hidden'); modal.classList.remove('flex');
}
</script>