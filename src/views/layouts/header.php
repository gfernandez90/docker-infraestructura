<?php
// Consulta para la alerta de Triage
$total_triage = 0;
try {
    $stmtTriageCount = $pdo->query("
        SELECT COUNT(*) as total_triage
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
    ");
    $total_triage = (int) $stmtTriageCount->fetchColumn();
} catch (Exception $e) {
    // Si la tabla o consulta falla, prevemos que no rompa el header
    $total_triage = 0;
}
?>
<header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6">
    <div class="text-slate-600 font-medium">
        Sistema de Gestión de Infraestructura
    </div>
    <div class="flex items-center gap-4">
        <a href="/index.php?page=triage" class="relative inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-300 hover:text-white hover:bg-slate-700/50 rounded-lg transition">
            <span>🔍 Triage</span>

            <?php if ($total_triage > 0): ?>
                <!-- Badge Activo (Rojo/Amber parpadeante si hay pendientes) -->
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-amber-100 bg-amber-600/90 border border-amber-500 rounded-full animate-pulse shadow-sm">
                    <?= $total_triage ?>
                </span>
            <?php else: ?>
                <!-- Badge Neutro (Si está al día) -->
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium leading-none text-slate-400 bg-slate-800 border border-slate-700 rounded-full">
                    0
                </span>
            <?php endif; ?>
        </a>
        <span class="text-sm font-semibold text-slate-700">
            👤 <?= htmlspecialchars($_SESSION['user']['username'] ?? 'Usuario') ?>
            <span class="text-xs font-normal text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">Admin</span>
        </span>
        <a href="/logout.php" class="text-sm text-red-600 hover:text-red-800 font-medium hover:underline">
            Cerrar sesión
        </a>
    </div>
</header>
