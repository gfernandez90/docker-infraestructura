<?php
// Procesar guardado si viene por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['config'] as $id => $valor) {
        $stmt = $pdo->prepare("UPDATE configuraciones SET valor = :valor, actualizado_en = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute(['valor' => $valor, 'id' => $id]);
    }
    $mensaje = "Configuraciones actualizadas correctamente.";
}

// Obtener configuraciones de la BD
$configs = $pdo->query("SELECT * FROM configuraciones ORDER BY clave ASC")->fetchAll();
?>

<div class="p-8 max-w-4xl">
    <h1 class="text-2xl font-bold text-slate-800 mb-2">Parametrización del Sistema</h1>
    <p class="text-slate-600 mb-6">Administra las URLs globales y parámetros de integración.</p>

    <?php if (isset($mensaje)): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg">
            <?= $mensaje ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
        <?php foreach ($configs as $cfg): ?>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">
                    <?= htmlspecialchars($cfg['clave']) ?>
                </label>
                <input type="text" 
                       name="config[<?= $cfg['id'] ?>]" 
                       value="<?= htmlspecialchars($cfg['valor']) ?>" 
                       class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <p class="text-xs text-slate-400 mt-1"><?= htmlspecialchars($cfg['descripcion']) ?></p>
            </div>
        <?php endforeach; ?>

        <div class="pt-4">
            <button type="submit" class="bg-blue-600 text-white px-5 py-2.5 rounded-lg hover:bg-blue-700 transition font-medium">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
