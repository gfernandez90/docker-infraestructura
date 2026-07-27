<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/RedmineService.php';

$mensajeExito = null;
$mensajeError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_incidente_btn'])) {
    try {
        $asunto = trim($_POST['asunto'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $prioridadId = (int)($_POST['prioridad_id'] ?? 2);

        if (empty($asunto)) {
            throw new Exception("El asunto del incidente es obligatorio.");
        }

        $redmine = new RedmineService();

        // 1. Obtener ID del tracker "Tarea"
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
        } catch (Exception $e) {
            // Se mantiene el valor por defecto
        }

        // 2. Armar el payload para Redmine SITA
        $dataIncidente = [
            'project_id'   => 'incidentes-diarios',
            'tracker_id'   => $trackerId,
            'status_id'    => 1, // Nueva
            'priority_id'  => $prioridadId,
            'subject'      => $asunto,
            'description'  => $descripcion,
            'custom_fields' => [
                [
                    'id'    => 71, // Custom Field 'PM Responsable'
                    'value' => 'Gabriel Fernandez'
                ]
            ]
        ];

        // 3. Crear el incidente vía API
        $incidenteCreado = $redmine->crearTarea($dataIncidente);

        if (!$incidenteCreado || !isset($incidenteCreado['id'])) {
            throw new Exception("No se pudo obtener el ID del incidente creado en Redmine.");
        }

        $incidenteId = $incidenteCreado['id'];
        $mensajeExito = "⚡ ¡Incidente creado con éxito en Incidentes Diarios! Ticket ID: #{$incidenteId}.";

    } catch (Exception $e) {
        $mensajeError = $e->getMessage();
    }
}
?>

<div class="space-y-6">
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden max-w-3xl mx-auto">
        <div class="bg-amber-500/10 border-b border-amber-500/20 p-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-400 font-bold text-lg">
                <span>⚠️</span>
                <h2>Crear Incidente Individual</h2>
            </div>
            <span class="text-xs text-slate-400 bg-slate-900/60 px-2.5 py-1 rounded-full border border-slate-700">Incidentes Diarios</span>
        </div>

        <form method="POST" action="/index.php?page=crear_incidente" class="p-6 space-y-5">
            <input type="hidden" name="crear_incidente_btn" value="1">

            <div>
                <label for="asunto" class="block text-xs font-semibold text-slate-300 uppercase mb-2">Asunto / Resumen del Incidente *</label>
                <input type="text" name="asunto" id="asunto" required placeholder="Ej: Caída de enlace L2TP en router de borde" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-sm text-slate-100 focus:outline-none focus:border-amber-500">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="prioridad_id" class="block text-xs font-semibold text-slate-300 uppercase mb-2">Prioridad</label>
                    <select name="prioridad_id" id="prioridad_id" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-sm text-slate-100 focus:outline-none focus:border-amber-500">
                        <option value="1">Baja</option>
                        <option value="2" selected>Normal</option>
                        <option value="3">Alta</option>
                        <option value="4">Urgente</option>
                        <option value="5">Inmediata</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-2">PM Responsable</label>
                    <input type="text" value="Gabriel Fernandez" disabled class="w-full bg-slate-900 border border-slate-800 rounded-lg p-3 text-sm text-slate-500 cursor-not-allowed">
                </div>
            </div>

            <div>
                <label for="descripcion" class="block text-xs font-semibold text-slate-300 uppercase mb-2">Descripción / Detalle Técnico</label>
                <textarea name="descripcion" id="descripcion" rows="5" placeholder="Detallar síntomas, logs, IP afectada o impacto observado..." class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 text-sm text-slate-100 focus:outline-none focus:border-amber-500"></textarea>
            </div>

            <div class="pt-4 border-t border-slate-700/80 flex justify-end">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-6 py-3 rounded-lg transition duration-150 flex items-center gap-2 shadow-lg">
                    <span>⚡</span> Reportar Incidente
                </button>
            </div>
        </form>
    </div>

    <?php if ($mensajeError): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl flex items-center gap-3 max-w-3xl mx-auto">
            <span class="text-xl">❌</span>
            <p class="text-sm font-mono"><?= htmlspecialchars($mensajeError) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($mensajeExito): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl flex items-center gap-3 max-w-3xl mx-auto">
            <span class="text-xl">✅</span>
            <p class="text-sm font-semibold"><?= htmlspecialchars($mensajeExito) ?></p>
        </div>
    <?php endif; ?>
</div>
