<?php
// views/triage.php

// 1. Obtener la lista de tickets que requieren Triage (Proyecto ID = 1)
$sqlTriage = "
    SELECT 
        t.id,
        t.asunto,
        t.descripcion,
        t.tracker_nombre,
        t.created_on,
        t.categoria,
        t.asignado_a_id,
        t.estimated_hours,
        t.estado_id,
        e.nombre AS estado_nombre,
        autor.nombre_completo AS autor_nombre,
        asig.nombre_completo AS asignado_nombre
    FROM redmine_tareas t
    LEFT JOIN redmine_estados e ON t.estado_id = e.id
    LEFT JOIN redmine_usuarios autor ON t.autor_id = autor.id
    LEFT JOIN redmine_usuarios asig ON t.asignado_a_id = asig.id
    WHERE t.proyecto_id = 1
    AND LOWER(e.nombre) NOT IN ('cerrado','rechazada','rechazado','resuelto','resuelta') -- Excluye cerrados y rechazados
    AND (
      LOWER(e.nombre) = 'nuevo'
      OR t.categoria IS NULL 
      OR t.categoria = ''
      OR t.asignado_a_id IS NULL
      OR t.estimated_hours IS NULL 
      OR t.estimated_hours = 0
    )
    ORDER BY t.created_on ASC
";

$stmt = $pdo->prepare($sqlTriage);
$stmt->execute();
$pendientesTriage = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Usuarios y Estados
$usuarios = $pdo->query("SELECT id, nombre_completo FROM redmine_usuarios ORDER BY nombre_completo ASC")->fetchAll(PDO::FETCH_ASSOC);
$estados = $pdo->query("SELECT id, nombre FROM redmine_estados WHERE LOWER(nombre) != 'cerrado' ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Contenedor principal con fondo oscuro explicito para corregir la vista -->
<div class="w-full min-h-screen bg-slate-900 p-6 text-slate-100">
    
    <!-- Header -->
    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3 text-white">
                <span>🔍 Mesa de Triage</span>
                <span class="px-3 py-1 text-xs font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30 rounded-full">
                    <?= count($pendientesTriage) ?> Pendientes
                </span>
            </h1>
            <p class="text-sm text-slate-400 mt-1">
                Clasifica, asigna y estimá los tickets entrantes antes de pasarlos a la cola de trabajo.
            </p>
        </div>
    </div>

    <?php if (empty($pendientesTriage)): ?>
        <div class="flex flex-col items-center justify-center p-12 bg-slate-800/40 border border-slate-700/50 rounded-xl text-center">
            <div class="w-16 h-16 bg-emerald-500/10 text-emerald-400 rounded-full flex items-center justify-center mb-4 text-2xl">
                ✓
            </div>
            <h3 class="text-lg font-semibold text-slate-200">¡Mesa limpia!</h3>
            <p class="text-slate-400 text-sm mt-1">No hay incidentes pendientes de triage en este momento.</p>
        </div>
    <?php else: ?>

        <div class="space-y-5">
            <?php foreach ($pendientesTriage as $ticket): ?>
                <div class="bg-slate-800/80 border border-slate-700/80 rounded-xl p-5 shadow-xl">
                    
                    <form method="POST" action="/index.php?page=procesar_triage" class="p-6 space-y-5">
                        <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                    

                        <!-- Header de la tarjeta -->
                        <div class="flex items-start justify-between gap-4 border-b border-slate-700/60 pb-3">
                            <div class="flex items-center gap-3">
                                <span class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-700 text-slate-200 rounded">
                                    #<?= $ticket['id'] ?>
                                </span>
                                <h2 class="text-lg font-semibold text-white">
                                    <?= htmlspecialchars($ticket['asunto']) ?>
                                </h2>
                            </div>
                            <span class="text-xs text-slate-400">
                                Creado: <?= date('d/m/Y H:i', strtotime($ticket['created_on'])) ?> por <strong class="text-slate-200"><?= htmlspecialchars($ticket['autor_nombre'] ?? 'Desconocido') ?></strong>
                            </span>
                        </div>

                        <!-- Descripción del Ticket -->
                        <?php if (!empty($ticket['descripcion'])): ?>
                            <div class="text-sm text-slate-300 bg-slate-900/60 p-3.5 rounded-lg border border-slate-800/80 whitespace-pre-wrap">
                                <?= htmlspecialchars($ticket['descripcion']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Fila de Campos Ajustada con Valores Actuales arriba -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2">
                            
                            <!-- 1. Categoría -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-400">Categoría <span class="text-rose-400">*</span></label>
                                    <span class="text-[11px] font-mono text-indigo-400 bg-indigo-950/50 px-1.5 py-0.5 rounded border border-indigo-800/40">
                                        <?= !empty($ticket['categoria']) ? htmlspecialchars($ticket['categoria']) : 'Sin dato' ?>
                                    </span>
                                </div>
                                <select name="categoria" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="Incidente" <?= (strcasecmp($ticket['categoria'] ?? '', 'Incidente') === 0) ? 'selected' : '' ?>>1. Incidente</option>
                                    <option value="Consulta Rápida" <?= (strcasecmp($ticket['categoria'] ?? '', 'Consulta Rápida') === 0) ? 'selected' : '' ?>>2. Consulta Rápida</option>
                                    <option value="Tarea Rápida" <?= (strcasecmp($ticket['categoria'] ?? '', 'Tarea Rápida') === 0) ? 'selected' : '' ?>>3. Tarea Rápida</option>
                                    <option value="Proyecto" <?= (strcasecmp($ticket['categoria'] ?? '', 'Proyecto') === 0) ? 'selected' : '' ?>>4. Proyecto</option>
                                </select>
                            </div>

                            <!-- 2. Asignar a (Predeterminado: DGEIP Infraestructura) -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-400">Asignar a <span class="text-rose-400">*</span></label>
                                    <span class="text-[11px] font-mono text-indigo-400 bg-indigo-950/50 px-1.5 py-0.5 rounded border border-indigo-800/40">
                                        <?= !empty($ticket['asignado_nombre']) ? htmlspecialchars($ticket['asignado_nombre']) : 'Sin dato' ?>
                                    </span>
                                </div>
                                <select name="asignado_a_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <?php foreach ($usuarios as $u): ?>
                                        <?php 
                                            // Si el ticket NO tiene un asignado asignado, predeterminamos "DGEIP Infraestructura"
                                            $esDefaultDgeip = empty($ticket['asignado_a_id']) && (
                                                strcasecmp($u['nombre_completo'], 'DGEIP Infraestructura') === 0 ||
                                                str_contains(strtolower($u['nombre_completo']), 'infraestructura')
                                            );
                                            
                                            $isSelected = ($ticket['asignado_a_id'] == $u['id']) || $esDefaultDgeip;
                                        ?>
                                        <option value="<?= $u['id'] ?>" <?= $isSelected ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($u['nombre_completo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 3. Horas Estimadas -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-400">Horas Est. <span class="text-rose-400">*</span></label>
                                    <span class="text-[11px] font-mono text-indigo-400 bg-indigo-950/50 px-1.5 py-0.5 rounded border border-indigo-800/40">
                                        <?= (!is_null($ticket['estimated_hours']) && $ticket['estimated_hours'] > 0) ? $ticket['estimated_hours'] . ' hs' : 'Sin dato' ?>
                                    </span>
                                </div>
                                <input type="number" step="0.5" min="0.5" name="estimated_hours" value="<?= $ticket['estimated_hours'] ?? '' ?>" placeholder="Ej: 2.5" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>

                            <!-- 4. Cambiar a Estado -->
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-400">Cambiar a Estado</label>
                                    <span class="text-[11px] font-mono text-indigo-400 bg-indigo-950/50 px-1.5 py-0.5 rounded border border-indigo-800/40">
                                        <?= !empty($ticket['estado_nombre']) ? htmlspecialchars($ticket['estado_nombre']) : 'Sin dato' ?>
                                    </span>
                                </div>
                                <select name="estado_id" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <?php foreach ($estados as $est): ?>
                                        <?php if (strtolower($est['nombre']) !== 'nuevo'): ?>
                                            <option value="<?= $est['id'] ?>" <?= strtolower($est['nombre']) === 'pendiente' ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($est['nombre']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>

                        <!-- Fila de Acciones -->
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-700/50">
                            
                            <!-- FORMULARIO 1: Solo para Rechazar -->
                            <form action="index.php?page=procesar_triage" method="POST" onsubmit="return confirm('¿Confirmas que deseas rechazar este ticket?');">
                                <input type="hidden" name="ticket_id" value="<?= $ticket['id'] ?>">
                                <input type="hidden" name="accion" value="rechazar">
                                <button type="submit" class="px-4 py-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 font-medium text-xs rounded-lg transition flex items-center gap-1">
                                    ❌ Rechazar Ticket
                                </button>
                            </form>

                            <!-- FORMULARIO 2: Para Aprobar y Clasificar (Cierra el form principal que abriste arriba) -->
                                <input type="hidden" name="accion" value="aprobar">
                                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs rounded-lg shadow transition flex items-center gap-1">
                                    🟢 Aprobar y Clasificar
                                </button>
                            </form>
                        </div>

                    </form>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>