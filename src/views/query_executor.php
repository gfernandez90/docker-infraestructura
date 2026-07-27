<?php
require_once __DIR__ . '/../config/db.php';

$sql_query = $_POST['sql_query'] ?? '';
$error_msg = null;
$success_msg = null;
$result_data = null;
$affected_rows = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(trim($sql_query))) {
    try {
        $stmt = $pdo->prepare($sql_query);
        $stmt->execute();

        if ($stmt->columnCount() > 0) {
            $result_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $affected_rows = $stmt->rowCount();
            $success_msg = "Consulta ejecutada correctamente. Filas afectadas: {$affected_rows}";
        }
    } catch (PDOException $e) {
        $error_msg = $e->getMessage();
    }
}
?>

<div class="space-y-6">
    <!-- Formulario de Consulta -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
        <div class="bg-amber-500/10 border-b border-amber-500/20 p-4 flex items-center justify-between">
            <div class="flex items-center gap-2 text-amber-400 font-bold text-lg">
                <span>⚠️</span>
                <h2>Ejecución de Querys SQL (PostgreSQL 17)</h2>
            </div>
            <span class="text-xs text-slate-400 bg-slate-900/60 px-2.5 py-1 rounded-full border border-slate-700">Consola de Administración</span>
        </div>

        <form method="POST" action="/index.php?page=query_executor" class="p-5 space-y-4">
            <div>
                <label for="sql_query" class="block text-sm font-medium text-slate-300 mb-2">
                    Sentencia SQL a ejecutar:
                </label>
                <textarea 
                    name="sql_query" 
                    id="sql_query" 
                    rows="6" 
                    class="w-full bg-slate-950 border border-slate-700 rounded-lg p-3 font-mono text-slate-200 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 text-sm leading-relaxed" 
                    placeholder="SELECT * FROM usuarios;" 
                    required><?= htmlspecialchars($sql_query) ?></textarea>
            </div>

            <!-- Panel de Consultas Base / Rápidas -->
            <div class="bg-slate-900/70 border border-slate-700/80 rounded-lg p-3.5 space-y-2">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">
                    📌 Consultas Base
                </span>
                <div class="flex flex-wrap gap-2">
                    <button 
                        type="button" 
                        onclick="setQuery('SELECT datname FROM pg_database WHERE datistemplate = false;')" 
                        class="text-xs bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-600 px-3 py-1.5 rounded-md font-mono transition flex items-center gap-1.5">
                        <span>🗄️</span> Listar Bases de Datos
                    </button>

                    <button 
                        type="button" 
                        onclick="setQuery('SELECT table_name\n  FROM information_schema.tables\n WHERE table_schema=\'public\';')" 
                        class="text-xs bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-600 px-3 py-1.5 rounded-md font-mono transition flex items-center gap-1.5">
                        <span>📋</span> Listar Tablas (Esquema Public)
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between pt-2">
                <span class="text-xs text-slate-400">
                    💡 Permite cualquier instrucción SQL (<code class="text-amber-400">SELECT</code>, <code class="text-amber-400">INSERT</code>, <code class="text-amber-400">UPDATE</code>, <code class="text-amber-400">DELETE</code>, <code class="text-amber-400">DDL</code>, etc.).
                </span>
                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-5 py-2.5 rounded-lg transition duration-150 flex items-center gap-2 shadow-md">
                    <span>▶</span> Ejecutar Consulta
                </button>
            </div>
        </form>
    </div>

    <!-- Salida de Error -->
    <?php if ($error_msg): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-4 rounded-xl flex items-start gap-3">
            <span class="text-xl">❌</span>
            <div>
                <h4 class="font-bold text-red-300">Error en la ejecución PostgreSQL:</h4>
                <pre class="font-mono text-xs mt-1 whitespace-pre-wrap bg-slate-950/50 p-2.5 rounded border border-red-500/20 text-red-300"><?= htmlspecialchars($error_msg) ?></pre>
            </div>
        </div>
    <?php endif; ?>

    <!-- Salida de Éxito -->
    <?php if ($success_msg): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 p-4 rounded-xl flex items-center gap-3">
            <span class="text-xl">✅</span>
            <div class="font-medium"><?= htmlspecialchars($success_msg) ?></div>
        </div>
    <?php endif; ?>

    <!-- Tabla de Resultados -->
    <?php if ($result_data !== null): ?>
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
            <div class="bg-slate-900 p-4 border-b border-slate-700 flex justify-between items-center">
                <h3 class="font-bold text-slate-200 flex items-center gap-2">
                    <span>📋</span> Resultado de la Consulta
                </h3>
                <span class="text-xs font-semibold bg-slate-800 text-slate-300 px-3 py-1 rounded-full border border-slate-700">
                    <?= count($result_data) ?> registro(s)
                </span>
            </div>

            <?php if (count($result_data) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-900/80 text-xs uppercase text-slate-400 border-b border-slate-700 font-mono">
                            <tr>
                                <?php foreach (array_keys($result_data[0]) as $column): ?>
                                    <th class="px-4 py-3 font-semibold tracking-wider"><?= htmlspecialchars($column) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/60 font-mono text-xs">
                            <?php foreach ($result_data as $row): ?>
                                <tr class="hover:bg-slate-700/40 transition">
                                    <?php foreach ($row as $val): ?>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?= $val !== null ? htmlspecialchars($val) : '<em class="text-slate-500">NULL</em>' ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-6 text-center text-slate-400 italic">
                    La consulta se ejecutó con éxito pero no devolvió filas.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function setQuery(sqlText) {
    const textarea = document.getElementById('sql_query');
    textarea.value = sqlText;
    textarea.focus();
}
</script>
