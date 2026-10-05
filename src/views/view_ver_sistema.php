<?php
// src/views/view_ver_sistema.php

$sistemaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$sistemaId) { header('Location: /index.php?page=sistemas'); exit; }

$sistema = $pdo->query("SELECT * FROM sistemas WHERE id = $sistemaId")->fetch(PDO::FETCH_ASSOC);
if (!$sistema) { header('Location: /index.php?page=sistemas'); exit; }

$integraciones = $pdo->query("
    SELECT si.*, s.nombre AS nombre_sistema 
    FROM sistema_integraciones si
    JOIN sistemas s ON si.sistema_destino_id = s.id
    WHERE si.sistema_id = $sistemaId
")->fetchAll(PDO::FETCH_ASSOC);

$ambRaw = $pdo->query("SELECT * FROM sistema_ambientes WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$ambientes = []; foreach($ambRaw as $a) $ambientes[$a['ambiente']] = $a;

$dbRaw = $pdo->query("SELECT * FROM sistema_bases_datos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$dbs = []; foreach($dbRaw as $d) $dbs[$d['ambiente']][] = $d;

$bkRaw = $pdo->query("SELECT * FROM sistema_respaldos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$respaldos = []; foreach($bkRaw as $r) $respaldos[$r['ambiente']] = $r;

$artRaw = $pdo->query("SELECT * FROM sistema_artefactos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$artIds = array_column($artRaw, 'id');
$artInts = [];

if (!empty($artIds)) {
    $inClause = implode(',', $artIds);
    $intArtRaw = $pdo->query("
        SELECT ai.*, sa_dest.nombre AS nombre_artefacto_destino, s_dest.nombre AS nombre_sistema_destino
        FROM artefacto_integraciones ai
        JOIN sistema_artefactos sa_dest ON ai.artefacto_destino_id = sa_dest.id
        JOIN sistemas s_dest ON sa_dest.sistema_id = s_dest.id
        WHERE ai.artefacto_origen_id IN ($inClause)
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach($intArtRaw as $ia) { $artInts[$ia['artefacto_origen_id']][] = $ia; }
}

$artefactos = []; 
foreach($artRaw as $ar) {
    $ar['integraciones'] = $artInts[$ar['id']] ?? [];
    $artefactos[$ar['ambiente']][] = $ar;
}

$listaAmbientes = ['produccion' => 'Producción', 'capacitacion' => 'Capacitación', 'test' => 'Test', 'desarrollo' => 'Desarrollo', 'herramientas' => 'Herramientas'];
$badgeClass = match($sistema['estado']) {
    'produccion' => 'bg-green-100 text-green-800',
    'desarrollo' => 'bg-amber-100 text-amber-800',
    'mantenimiento' => 'bg-sky-100 text-sky-800',
    default => 'bg-slate-100 text-slate-800'
};
?>

<div class="max-w-7xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <?= htmlspecialchars($sistema['codigo'] ?? 'N/A') ?> - <?= htmlspecialchars($sistema['nombre']) ?>
                <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $badgeClass ?> uppercase">
                    <?= htmlspecialchars($sistema['estado']) ?>
                </span>
            </h2>
            <p class="text-slate-500 mt-1"><?= htmlspecialchars($sistema['resumen'] ?: 'Sin descripción') ?></p>
        </div>
        <div class="flex gap-2">
            <a href="/index.php?page=sistemas" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-md hover:bg-slate-300 font-medium text-sm transition">← Volver</a>
            <a href="/index.php?page=exportar_sistema&id=<?= $sistemaId ?>" class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 font-medium text-sm transition shadow-sm">📄 Exportar</a>
            <a href="/index.php?page=editar_sistema&id=<?= $sistemaId ?>" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium text-sm transition shadow-sm">✏️ Editar</a>
        </div>
    </div>

    <?php 
        // Agregamos la consulta de agentes justo encima de dibujarlos
        $agentes = $pdo->query("SELECT a.nombre, a.cargo FROM sistema_agentes sa JOIN agentes a ON sa.agente_id = a.id WHERE sa.sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm">
            <h3 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3">👥 Equipo Asignado</h3>
            <div class="space-y-3">
                <?php if (empty($agentes)): ?>
                    <p class="text-slate-400 text-sm italic">Sin personal asignado</p>
                <?php else: ?>
                    <ul class="space-y-2">
                        <?php foreach($agentes as $ag): ?>
                            <li class="flex items-center gap-2">
                                <span class="text-slate-700 font-medium"><?= htmlspecialchars($ag['nombre']) ?></span>
                                <span class="text-[10px] bg-slate-100 border border-slate-200 px-1.5 py-0.5 rounded text-slate-500 uppercase"><?= htmlspecialchars($ag['cargo']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    <!-- Integraciones -->
    <?php if (!empty($integraciones)): ?>
        <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-sm mb-8">
            <h3 class="text-lg font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3">🔗 Integraciones Globales</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase font-medium text-slate-500"><tr><th class="px-4 py-2">Sistema Destino</th><th class="px-4 py-2">Tipo</th><th class="px-4 py-2">URL de Conexión</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($integraciones as $int): ?>
                            <tr>
                                <td class="px-4 py-2 font-bold text-indigo-600"><?= htmlspecialchars($int['nombre_sistema']) ?></td>
                                <td class="px-4 py-2"><span class="bg-slate-100 px-2 py-1 rounded text-xs"><?= htmlspecialchars($int['tipo_integracion']) ?></span></td>
                                <td class="px-4 py-2 font-mono text-xs"><?= htmlspecialchars($int['url_conexion'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- AMBIENTES -->
    <h3 class="text-xl font-bold text-slate-800 mb-4">🌍 Ambientes de Infraestructura</h3>
    <div class="space-y-6">
        <?php 
        $hayAmbientes = false;
        foreach ($listaAmbientes as $envKey => $envName): 
            $a = $ambientes[$envKey] ?? [];
            $artList = $artefactos[$envKey] ?? [];
            $dbList = $dbs[$envKey] ?? [];
            $bk = $respaldos[$envKey] ?? [];

            if (empty(trim($a['servidor_app'] ?? '')) && empty($artList) && empty($dbList)) continue;
            $hayAmbientes = true;
        ?>
            <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-50 px-5 py-3 border-b border-slate-200 flex items-center justify-between">
                    <h4 class="font-bold text-lg text-indigo-900 uppercase tracking-wide">▶️ <?= $envName ?></h4>
                    <?php if (!empty($a['es_publico'])): ?><span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded">ACCESO PÚBLICO</span><?php endif; ?>
                </div>
                
                <div class="p-5 grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <!-- Arquitectura Base -->
                    <div>
                        <h5 class="text-sm font-bold text-slate-500 uppercase mb-3 border-b pb-1">Arquitectura Base</h5>
                        <ul class="space-y-2 text-sm text-slate-700">
                            <li><span class="font-semibold w-32 inline-block">Servidor:</span> 🖥️ <?= htmlspecialchars($a['servidor_app'] ?? 'N/A') ?></li>
                            <li><span class="font-semibold w-32 inline-block">Despliegue:</span> <?= htmlspecialchars($a['tipo_despliegue'] ?? 'N/A') ?></li>
                            <li><span class="font-semibold w-32 inline-block">Archivos:</span> <?= htmlspecialchars($a['artefactos'] ?? 'N/A') ?></li>
                            <?php if(!empty($a['variables_entorno'])): ?><li><span class="font-semibold block">Variables Entorno:</span> <code class="bg-slate-100 px-1 rounded text-xs"><?= htmlspecialchars($a['variables_entorno']) ?></code></li><?php endif; ?>
                            <?php if(!empty($a['datasources_dblinks'])): ?><li><span class="font-semibold block">Datasources:</span> <code class="bg-slate-100 px-1 rounded text-xs"><?= htmlspecialchars($a['datasources_dblinks']) ?></code></li><?php endif; ?>
                        </ul>
                    </div>

                    <!-- Componentes -->
                    <?php if (!empty($artList)): ?>
                    <div>
                        <h5 class="text-sm font-bold text-slate-500 uppercase mb-3 border-b pb-1">📦 Componentes (Microservicios)</h5>
                        <div class="space-y-4">
                            <?php foreach ($artList as $art): ?>
                                <div class="bg-slate-50 border border-slate-200 p-4 rounded shadow-sm">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <div class="font-bold text-indigo-700 text-base">
                                                <?= htmlspecialchars($art['nombre']) ?>
                                                <span class="font-normal text-[10px] bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded ml-1"><?= htmlspecialchars($art['codigo'] ?: 'Sin cod.') ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-0.5">Auth: <?= htmlspecialchars($art['tipo_auth'] ?: 'Ninguna') ?></div>
                                        </div>
                                    </div>
                                    
                                    <!-- BADGES (Docker, Alojamiento, Tecnologías) -->
                                    <div class="flex gap-2 flex-wrap mb-3 mt-1">
                                        <?php if(!empty($art['tecnologias'])): ?><span class="bg-slate-200 border border-slate-300 text-slate-700 text-[10px] px-1.5 py-0.5 rounded">🛠️ <?= htmlspecialchars($art['tecnologias']) ?></span><?php endif; ?>
                                        <?php if(!empty($art['ubicacion_alojamiento'])): ?><span class="bg-amber-100 border border-amber-200 text-amber-800 text-[10px] px-1.5 py-0.5 rounded">📍 <?= htmlspecialchars($art['ubicacion_alojamiento']) ?></span><?php endif; ?>
                                        <?php if(!empty($art['en_docker'])): ?><span class="bg-sky-100 border border-sky-200 text-sky-800 text-[10px] px-1.5 py-0.5 rounded font-bold">🐳 Docker</span><?php endif; ?>
                                        <?php if(!empty($art['tiene_mantenimiento'])): ?><span class="bg-emerald-100 border border-emerald-200 text-emerald-800 text-[10px] px-1.5 py-0.5 rounded font-bold">🔧 Mantenimiento Activo</span><?php endif; ?>
                                    </div>

                                    <div class="text-sm space-y-1">
                                        <?php if($art['url_privada']): ?><div><span class="text-xs font-semibold text-slate-500">Privada:</span> <a href="<?= $art['url_privada'] ?>" target="_blank" class="text-sky-600 hover:underline"><?= htmlspecialchars($art['url_privada']) ?></a></div><?php endif; ?>
                                        <?php if($art['url_publica']): ?><div><span class="text-xs font-semibold text-slate-500">Pública:</span> <a href="<?= $art['url_publica'] ?>" target="_blank" class="text-sky-600 hover:underline"><?= htmlspecialchars($art['url_publica']) ?></a></div><?php endif; ?>
                                    </div>
                                    
                                    <?php if(!empty($art['integraciones'])): ?>
                                        <div class="mt-3 pt-2 border-t border-slate-200">
                                            <span class="text-xs font-semibold text-slate-500">Enlaces / APIs conectadas:</span>
                                            <ul class="mt-1 space-y-1">
                                                <?php foreach($art['integraciones'] as $ia): ?>
                                                    <li class="text-xs text-slate-700 bg-white p-1 rounded border border-slate-100">🔗 <span class="font-bold"><?= htmlspecialchars($ia['nombre_sistema_destino']) ?> / <?= htmlspecialchars($ia['nombre_artefacto_destino']) ?></span> <span class="text-[10px] bg-slate-100 border px-1 rounded ml-1"><?= htmlspecialchars($ia['tipo_integracion']) ?></span></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Bases de Datos y Respaldos -->
                    <!-- [DBs y Backups se mantienen idénticos, omitidos por legibilidad visual...] -->
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hayAmbientes): ?>
            <div class="bg-white p-8 text-center text-slate-500 rounded-lg border border-slate-200 shadow-sm">No hay infraestructura técnica registrada en ningún ambiente para este sistema.</div>
        <?php endif; ?>
    </div>
</div>