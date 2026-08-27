<?php
// src/views/view_sistemas.php

// 1. Obtener Sistemas
$query = "SELECT * FROM sistemas ORDER BY id DESC";
$sistemas = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// 2. Obtener Ambientes
$ambientesRaw = $pdo->query("SELECT sistema_id, ambiente, url_acceso, servidor_app, tipo_auth, es_publico FROM sistema_ambientes")->fetchAll(PDO::FETCH_ASSOC);
$ambientesPorSistema = [];
$sistemasPublicos = []; // Para el contador
foreach($ambientesRaw as $amb) {
    $ambientesPorSistema[$amb['sistema_id']][] = $amb;
    if (!empty($amb['es_publico'])) {
        $sistemasPublicos[$amb['sistema_id']] = true;
    }
}

// 3. Obtener Artefactos
$artefactosRaw = $pdo->query("SELECT sistema_id, ambiente, nombre, url_privada, url_publica, tipo_auth FROM sistema_artefactos")->fetchAll(PDO::FETCH_ASSOC);
$artefactosPorAmbiente = [];
foreach($artefactosRaw as $art) {
    $artefactosPorAmbiente[$art['sistema_id']][$art['ambiente']][] = $art;
    if (!empty(trim($art['url_publica']))) {
        $sistemasPublicos[$art['sistema_id']] = true;
    }
}

// 4. Obtener IPs de las Bases de Datos (Solo para el buscador)
$dbsRaw = $pdo->query("SELECT sistema_id, ip FROM sistema_bases_datos WHERE ip IS NOT NULL AND ip != ''")->fetchAll(PDO::FETCH_ASSOC);
$ipsPorSistema = [];
foreach($dbsRaw as $db) {
    $ipsPorSistema[$db['sistema_id']][] = trim($db['ip']);
}

// Métricas
$totalSistemas = count($sistemas);
$totalProd = count(array_filter($sistemas, fn($s) => $s['estado'] === 'produccion'));
$totalDev = count(array_filter($sistemas, fn($s) => $s['estado'] === 'desarrollo'));
$totalPublicos = count($sistemasPublicos);
?>

<div class="max-w-7xl mx-auto">
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">🖥️ Inventario de Sistemas</h2>
            <p class="text-slate-500 text-sm mt-1">Gestión centralizada de infraestructura y despliegues</p>
        </div>
        <a href="/index.php?page=crear_sistema" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm">
            ➕ Nuevo Sistema
        </a>
    </div>

    <!-- Alertas Flash -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['flash_success']) ?></span>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Tarjetas Resumen (Actualizado a 4 columnas) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-slate-500">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Sistemas</div>
            <div class="text-2xl font-bold text-slate-800 mt-1"><?= $totalSistemas ?></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-green-500">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">En Producción</div>
            <div class="text-2xl font-bold text-green-600 mt-1"><?= $totalProd ?></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-amber-500">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">En Desarrollo</div>
            <div class="text-2xl font-bold text-amber-500 mt-1"><?= $totalDev ?></div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-sky-500">
            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Públicos</div>
            <div class="text-2xl font-bold text-sky-500 mt-1"><?= $totalPublicos ?></div>
        </div>
    </div>

    <!-- Filtros (Actualizado placeholder) -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="col-span-1 md:col-span-2">
                <input type="text" id="buscador" placeholder="Buscar por nombre, responsable, URL, Host o IP..." class="w-full border border-slate-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
            </div>
            <div>
                <select id="filtroEstado" class="w-full border border-slate-300 rounded-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="produccion">Producción</option>
                    <option value="desarrollo">Desarrollo</option>
                    <option value="mantenimiento">Mantenimiento</option>
                    <option value="obsoleto">Obsoleto</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Sistema</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Ambientes y Accesos</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <?php if (empty($sistemas)): ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-slate-500">No hay sistemas registrados.</td></tr>
                    <?php else: ?>
                        <?php foreach ($sistemas as $s): 
                            $badgeClass = match($s['estado']) {
                                'produccion' => 'bg-green-100 text-green-800',
                                'desarrollo' => 'bg-amber-100 text-amber-800',
                                'mantenimiento' => 'bg-sky-100 text-sky-800',
                                default => 'bg-slate-100 text-slate-800'
                            };

                            // CONSTRUIR ÍNDICE DE BÚSQUEDA OCULTO
                            $searchTerms = [$s['nombre'], $s['responsable']];
                            
                            // Añadir IPs
                            if (isset($ipsPorSistema[$s['id']])) {
                                $searchTerms = array_merge($searchTerms, $ipsPorSistema[$s['id']]);
                            }
                            
                            // Añadir Servidores y URLs de ambientes
                            $ambs = $ambientesPorSistema[$s['id']] ?? [];
                            foreach ($ambs as $a) {
                                $searchTerms[] = $a['url_acceso'];
                                $searchTerms[] = $a['servidor_app'];
                            }
                            
                            // Añadir URLs de artefactos
                            if (isset($artefactosPorAmbiente[$s['id']])) {
                                foreach ($artefactosPorAmbiente[$s['id']] as $envArts) {
                                    foreach ($envArts as $art) {
                                        $searchTerms[] = $art['nombre'];
                                        $searchTerms[] = $art['url_privada'];
                                        $searchTerms[] = $art['url_publica'];
                                    }
                                }
                            }
                            // Unir todo en un solo string en minúsculas
                            $searchString = strtolower(implode(' ', array_filter($searchTerms)));
                        ?>
                        
                        <!-- FILA CON DATA-SEARCH OCULTO -->
                        <tr class="sistema-fila hover:bg-slate-50 transition" data-estado="<?= htmlspecialchars($s['estado']) ?>" data-search="<?= htmlspecialchars($searchString) ?>">
                            <td class="px-6 py-4 align-top">
                                <div class="text-sm font-bold text-indigo-600"><?= htmlspecialchars($s['nombre']) ?></div>
                                <div class="text-xs text-slate-500 mt-1">👤 <?= htmlspecialchars($s['responsable'] ?: 'Sin asignar') ?></div>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?> uppercase">
                                    <?= htmlspecialchars($s['estado']) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 align-top">
                                <?php if (empty($ambs)): ?>
                                    <span class="text-xs text-slate-400">Sin ambientes configurados</span>
                                <?php else: ?>
                                    <div class="space-y-3">
                                        <?php foreach ($ambs as $amb): 
                                            $envKey = $amb['ambiente'];
                                            $servidorApp = trim($amb['servidor_app'] ?? '');
                                            $arts = $artefactosPorAmbiente[$s['id']][$envKey] ?? [];
                                        ?>
                                            <?php if (!empty($servidorApp) || !empty($arts)): ?>
                                                <div class="text-xs text-slate-700 border-l-2 border-indigo-400 pl-3 py-1 bg-slate-50 rounded-r">
                                                    
                                                    <div class="font-bold uppercase text-indigo-900 mb-2">
                                                        <?= htmlspecialchars($envKey) ?>
                                                        <?php if (!empty($servidorApp)): ?>
                                                            <span class="text-slate-500 font-normal normal-case ml-2 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                                                🖥️ <?= htmlspecialchars($servidorApp) ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="space-y-2">
                                                        <?php foreach ($arts as $art): ?>
                                                            <div class="pl-2 border-l border-slate-300">
                                                                <div class="flex items-center gap-2 mb-0.5">
                                                                    <span class="font-semibold text-slate-800 tracking-tight">
                                                                        📦 <?= htmlspecialchars($art['nombre']) ?>
                                                                    </span>
                                                                    <?php if (!empty(trim($art['tipo_auth']))): ?>
                                                                        <span class="bg-white border border-slate-300 rounded px-1 text-[9px] text-slate-600">
                                                                            🔐 <?= htmlspecialchars($art['tipo_auth']) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                
                                                                <div class="space-y-0.5">
                                                                    <?php if (!empty(trim($art['url_privada']))): ?>
                                                                        <div class="truncate pl-4">
                                                                            <span class="opacity-50 text-[10px]">🔒 Privada:</span> 
                                                                            <a href="<?= htmlspecialchars($art['url_privada']) ?>" target="_blank" class="text-sky-600 hover:underline"><?= htmlspecialchars($art['url_privada']) ?></a>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (!empty(trim($art['url_publica']))): ?>
                                                                        <div class="truncate pl-4">
                                                                            <span class="opacity-50 text-[10px]">🌐 Pública:</span> 
                                                                            <a href="<?= htmlspecialchars($art['url_publica']) ?>" target="_blank" class="text-sky-600 hover:underline"><?= htmlspecialchars($art['url_publica']) ?></a>
                                                                            <span class="bg-blue-100 text-blue-700 text-[9px] font-bold px-1 rounded ml-1">PUB</span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
			<td class="px-6 py-4 text-right text-sm font-medium space-x-2 align-top whitespace-nowrap">
			    <a href="/index.php?page=exportar_sistema&id=<?= $s['id'] ?>" class="text-emerald-600 hover:text-emerald-900 bg-emerald-50 px-3 py-1 rounded transition" title="Exportar a Wiki.js">
			        📄 Wiki
			    </a>
			    <a href="/index.php?page=editar_sistema&id=<?= $s['id'] ?>" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 px-3 py-1 rounded transition">
			        ✏️ Editar
			    </a>
			    <a href="/index.php?page=eliminar_sistema&id=<?= $s['id'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este sistema por completo?');" class="text-red-600 hover:text-red-900 bg-red-50 px-3 py-1 rounded transition">
			        🗑️
			    </a>
			</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('buscador');
    const filtroEstado = document.getElementById('filtroEstado');
    const filas = document.querySelectorAll('.sistema-fila');

    const filtrar = () => {
        const texto = buscador.value.toLowerCase().trim();
        const estado = filtroEstado.value.toLowerCase();

        filas.forEach(fila => {
            // AHORA BUSCAMOS DENTRO DEL DATASET, QUE CONTIENE NOMBRES, URLS, HOSTS E IPS
            const contenido = fila.dataset.search; 
            const estadoFila = fila.dataset.estado.toLowerCase();
            
            const coincideTexto = (texto === '' || contenido.includes(texto));
            const coincideEstado = (estado === '' || estadoFila === estado);
            
            fila.style.display = (coincideTexto && coincideEstado) ? '' : 'none';
        });
    };

    buscador.addEventListener('input', filtrar);
    filtroEstado.addEventListener('change', filtrar);
});
</script>
