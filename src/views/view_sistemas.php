<?php
// src/views/view_sistemas.php

$query = "SELECT * FROM sistemas ORDER BY id DESC";
$sistemas = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

$ambientesRaw = $pdo->query("SELECT sistema_id, ambiente, url_acceso, servidor_app, tipo_auth, es_publico FROM sistema_ambientes")->fetchAll(PDO::FETCH_ASSOC);
$ambientesPorSistema = []; $sistemasPublicos = [];
foreach($ambientesRaw as $amb) {
    $ambientesPorSistema[$amb['sistema_id']][] = $amb;
    if (!empty($amb['es_publico'])) $sistemasPublicos[$amb['sistema_id']] = true;
}

$artefactosRaw = $pdo->query("SELECT sistema_id, ambiente, nombre, url_privada, url_publica, tipo_auth FROM sistema_artefactos")->fetchAll(PDO::FETCH_ASSOC);
$artefactosPorAmbiente = [];
foreach($artefactosRaw as $art) {
    $artefactosPorAmbiente[$art['sistema_id']][$art['ambiente']][] = $art;
    if (!empty(trim($art['url_publica']))) $sistemasPublicos[$art['sistema_id']] = true;
}

$dbsRaw = $pdo->query("SELECT sistema_id, ip FROM sistema_bases_datos WHERE ip IS NOT NULL AND ip != ''")->fetchAll(PDO::FETCH_ASSOC);
$ipsPorSistema = [];
foreach($dbsRaw as $db) $ipsPorSistema[$db['sistema_id']][] = trim($db['ip']);

// AQUI CARGAMOS LA TABLA AGENTES EN LUGAR DEL CAMPO RESPONSABLE
$agentesRaw = $pdo->query("SELECT sa.sistema_id, a.nombre, a.cargo FROM sistema_agentes sa JOIN agentes a ON sa.agente_id = a.id")->fetchAll(PDO::FETCH_ASSOC);
$agentesPorSistema = [];
foreach($agentesRaw as $ag) $agentesPorSistema[$ag['sistema_id']][] = $ag;

$totalSistemas = count($sistemas);
$totalProd = count(array_filter($sistemas, fn($s) => $s['estado'] === 'produccion'));
$totalDev = count(array_filter($sistemas, fn($s) => $s['estado'] === 'desarrollo'));
$totalPublicos = count($sistemasPublicos);
?>

<div class="max-w-7xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <div><h2 class="text-2xl font-bold text-slate-800">🖥️ Inventario de Sistemas</h2><p class="text-slate-500 text-sm mt-1">Gestión centralizada de infraestructura</p></div>
        <a href="/index.php?page=crear_sistema" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">➕ Nuevo Sistema</a>
    </div>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="bg-emerald-100 border border-emerald-400 text-emerald-800 px-4 py-3 rounded mb-4"><span class="font-medium"><?= htmlspecialchars($_SESSION['flash_success']) ?></span></div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-slate-500"><div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Sistemas</div><div class="text-2xl font-bold text-slate-800 mt-1"><?= $totalSistemas ?></div></div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500"><div class="text-xs font-bold text-slate-500 uppercase tracking-wider">En Producción</div><div class="text-2xl font-bold text-green-600 mt-1"><?= $totalProd ?></div></div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-amber-500"><div class="text-xs font-bold text-slate-500 uppercase tracking-wider">En Desarrollo</div><div class="text-2xl font-bold text-amber-500 mt-1"><?= $totalDev ?></div></div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-sky-500"><div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Públicos</div><div class="text-2xl font-bold text-sky-500 mt-1"><?= $totalPublicos ?></div></div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="col-span-1 md:col-span-2"><input type="text" id="buscador" placeholder="Buscar por nombre, código, agente, URL o IP..." class="w-full border border-slate-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-indigo-500 text-sm"></div>
            <div>
                <select id="filtroEstado" class="w-full border border-slate-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-indigo-500 text-sm bg-white">
                    <option value="">Todos los estados</option><option value="produccion">Producción</option><option value="desarrollo">Desarrollo</option><option value="mantenimiento">Mantenimiento</option><option value="obsoleto">Obsoleto</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr><th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Sistema</th><th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Estado</th><th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Ambientes y Accesos</th><th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">Acciones</th></tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php foreach ($sistemas as $s): 
                        $badgeClass = match($s['estado']) { 'produccion' => 'bg-green-100 text-green-800', 'desarrollo' => 'bg-amber-100 text-amber-800', 'mantenimiento' => 'bg-sky-100 text-sky-800', default => 'bg-slate-100 text-slate-800' };
                        
                        // ELIMINADA LA REFERENCIA A $s['responsable']
                        $searchTerms = [$s['codigo'] ?? '', $s['nombre']];
                        $agentes = $agentesPorSistema[$s['id']] ?? [];
                        foreach ($agentes as $ag) { $searchTerms[] = $ag['nombre']; $searchTerms[] = $ag['cargo']; }
                        if (isset($ipsPorSistema[$s['id']])) $searchTerms = array_merge($searchTerms, $ipsPorSistema[$s['id']]);
                        $ambs = $ambientesPorSistema[$s['id']] ?? [];
                        foreach ($ambs as $a) { $searchTerms[] = $a['url_acceso']; $searchTerms[] = $a['servidor_app']; }
                        if (isset($artefactosPorAmbiente[$s['id']])) {
                            foreach ($artefactosPorAmbiente[$s['id']] as $envArts) {
                                foreach ($envArts as $art) { $searchTerms[] = $art['nombre']; $searchTerms[] = $art['url_privada']; $searchTerms[] = $art['url_publica']; }
                            }
                        }
                        $searchString = strtolower(implode(' ', array_filter($searchTerms)));
                    ?>
                    <tr class="sistema-fila hover:bg-slate-50 transition" data-estado="<?= htmlspecialchars($s['estado']) ?>" data-search="<?= htmlspecialchars($searchString) ?>">
                        <td class="px-6 py-4 align-top w-1/4">
                            <?php if(!empty($s['codigo'])): ?><div class="text-[10px] font-mono text-slate-400 mb-0.5"><?= htmlspecialchars($s['codigo']) ?></div><?php endif; ?>
                            <div class="text-sm font-bold text-indigo-700 mb-2"><?= htmlspecialchars($s['nombre']) ?></div>
                            <?php if (empty($agentes)): ?>
                                <div class="text-[11px] text-slate-400 italic mt-1">Sin personal asignado</div>
                            <?php else: ?>
                                <div class="flex flex-col gap-1 mt-2">
                                    <?php foreach ($agentes as $ag): ?>
                                        <div class="text-[11px] leading-tight flex items-start gap-1"><span class="text-slate-400">👤</span><div><span class="font-semibold text-slate-700 block"><?= htmlspecialchars($ag['nombre']) ?></span><span class="text-[9px] bg-slate-100 border border-slate-200 px-1 rounded text-slate-500 uppercase"><?= htmlspecialchars($ag['cargo']) ?></span></div></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 align-top"><span class="px-2 py-0.5 inline-flex text-[11px] font-bold rounded-full <?= $badgeClass ?> uppercase"><?= htmlspecialchars($s['estado']) ?></span></td>
                        <td class="px-6 py-4 align-top">
                            <?php if (empty($ambs)): ?>
                                <span class="text-xs text-slate-400 italic">Sin infraestructura técnica</span>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($ambs as $amb): 
                                        $envKey = $amb['ambiente']; $servidorApp = trim($amb['servidor_app'] ?? ''); $arts = $artefactosPorAmbiente[$s['id']][$envKey] ?? [];
                                    ?>
                                        <?php if (!empty($servidorApp) || !empty($arts)): ?>
                                            <div class="text-xs text-slate-700 border-l-2 border-indigo-400 pl-3 py-1 bg-slate-50 rounded-r">
                                                <div class="font-bold uppercase text-indigo-900 mb-2 flex items-center gap-2"><?= htmlspecialchars($envKey) ?><?php if (!empty($amb['es_publico'])): ?><span class="bg-blue-100 text-blue-700 text-[9px] px-1 rounded">PUB</span><?php endif; ?></div>
                                                <?php if (!empty($servidorApp)): ?><div class="mb-1 text-slate-600"><span class="opacity-75">🖥️</span> <?= htmlspecialchars($servidorApp) ?></div><?php endif; ?>
                                                <?php if(!empty($arts)): ?>
                                                    <div class="space-y-1 mt-2">
                                                        <?php foreach ($arts as $art): ?>
                                                            <div class="pl-2 border-l border-slate-300"><span class="font-semibold text-slate-800 text-[11px]">📦 <?= htmlspecialchars($art['nombre']) ?></span><?php if (!empty(trim($art['url_publica']))): ?><div class="truncate pl-3 mt-0.5"><a href="<?= htmlspecialchars($art['url_publica']) ?>" target="_blank" class="text-sky-600 hover:underline text-[10px]"><?= htmlspecialchars($art['url_publica']) ?></a></div><?php endif; ?></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium space-x-2 align-top whitespace-nowrap">
                            <a href="/index.php?page=ver_sistema&id=<?= $s['id'] ?>" class="text-sky-600 hover:text-sky-900 bg-sky-50 px-3 py-1.5 rounded transition">👁️ Ver</a>
                            <a href="/index.php?page=exportar_sistema&id=<?= $s['id'] ?>" class="text-emerald-600 hover:text-emerald-900 bg-emerald-50 px-3 py-1.5 rounded transition">📄 Wiki</a>
                            <a href="/index.php?page=editar_sistema&id=<?= $s['id'] ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1.5 rounded transition">✏️ Editar</a>
                            <a href="/index.php?page=eliminar_sistema&id=<?= $s['id'] ?>" onclick="return confirm('¿Eliminar sistema completo?');" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1.5 rounded transition">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('buscador'); const filtroEstado = document.getElementById('filtroEstado'); const filas = document.querySelectorAll('.sistema-fila');
    const filtrar = () => {
        const texto = buscador.value.toLowerCase().trim(); const estado = filtroEstado.value.toLowerCase();
        filas.forEach(fila => {
            const contenido = fila.dataset.search; const estadoFila = fila.dataset.estado.toLowerCase();
            fila.style.display = ((texto === '' || contenido.includes(texto)) && (estado === '' || estadoFila === estado)) ? '' : 'none';
        });
    };
    buscador.addEventListener('input', filtrar); filtroEstado.addEventListener('change', filtrar);
});
</script>