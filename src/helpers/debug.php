<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Obtiene el nivel de debug actual de la sesión (0 por defecto)
 */
function getDebugLevel(): int {
    return isset($_SESSION['debug']) ? (int)$_SESSION['debug'] : 0;
}

/**
 * Renderiza el bloque HTML de debug
 */
function renderDebugBox(string $mensajeError, mixed $data = null): void {
    $level = getDebugLevel();

    if ($level === 0) {
        return;
    }

    // Renderizado del componente HTML estilo Tailwind
    ?>
    <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl flex flex-col gap-2 my-4 font-mono text-sm shadow-lg">
        <div class="flex items-center gap-3">
            <span class="text-xl">❌</span>
            <p class="font-bold"><?= htmlspecialchars($mensajeError) ?></p>
        </div>
        <?php if ($data !== null): ?>
            <pre class="bg-black/40 p-3 rounded-lg overflow-x-auto text-xs text-red-300 border border-red-500/20"><?= htmlspecialchars(print_r($data, true)) ?></pre>
        <?php endif; ?>
    </div>
    <?php

    // Si es nivel 2, detenemos la ejecución inmediatamente
    if ($level === 2) {
        exit();
    }
}

/**
 * Función para depurar variables en caliente
 */
function dd(mixed $var, string $label = 'Debug Info'): void {
    renderDebugBox($label, $var);
}

// Configurar el manejador de excepciones de PHP para capturar errores fatales según el nivel
set_exception_handler(function (\Throwable $e) {
    renderDebugBox('Excepción no capturada: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
});