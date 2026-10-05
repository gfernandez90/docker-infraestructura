<?php
// src/views/view_mapa_integraciones.php

// --- 1. DATA PARA MAPA DE SISTEMAS ---
$sistemasRaw = $pdo->query("SELECT id, nombre, estado FROM sistemas")->fetchAll(PDO::FETCH_ASSOC);
$nodosSistemas = [];
foreach ($sistemasRaw as $s) {
    // Colores basados en el estado
    $color = match($s['estado']) {
        'produccion' => '#a7f3d0', // green-200
        'desarrollo' => '#fde68a', // amber-200
        'mantenimiento' => '#bae6fd', // sky-200
        default => '#e2e8f0' // slate-200
    };
    $nodosSistemas[] = [
        'id' => $s['id'],
        'label' => $s['nombre'],
        'color' => ['background' => $color, 'border' => '#475569'],
        'shape' => 'box',
        'font' => ['face' => 'Inter, sans-serif', 'size' => 16, 'bold' => true]
    ];
}

$aristasSistemas = [];
$intSys = $pdo->query("SELECT sistema_id, sistema_destino_id, tipo_integracion FROM sistema_integraciones")->fetchAll(PDO::FETCH_ASSOC);
foreach ($intSys as $is) {
    $aristasSistemas[] = [
        'from' => $is['sistema_id'],
        'to' => $is['sistema_destino_id'],
        'label' => $is['tipo_integracion'],
        'arrows' => 'to',
        'font' => ['size' => 10, 'align' => 'middle']
    ];
}

// --- 2. DATA PARA MAPA DE ARTEFACTOS ---
$artefactosRaw = $pdo->query("
    SELECT a.id, a.nombre, a.ambiente, s.nombre as sis_nombre, s.estado as sis_estado
    FROM sistema_artefactos a 
    JOIN sistemas s ON a.sistema_id = s.id
")->fetchAll(PDO::FETCH_ASSOC);

$nodosArtefactos = [];
foreach ($artefactosRaw as $a) {
    $color = match($a['sis_estado']) {
        'produccion' => '#a7f3d0', 
        'desarrollo' => '#fde68a', 
        'mantenimiento' => '#bae6fd', 
        default => '#e2e8f0' 
    };
    $nodosArtefactos[] = [
        'id' => $a['id'],
        'label' => $a['sis_nombre'] . "\n(" . $a['nombre'] . ")", // Sistema + Artefacto
        'group' => $a['ambiente'], // Agrupamiento automático por ambiente
        'color' => ['background' => $color, 'border' => '#475569'],
        'shape' => 'ellipse',
        'font' => ['face' => 'Inter, sans-serif', 'size' => 14]
    ];
}

$aristasArtefactos = [];
$intArt = $pdo->query("SELECT artefacto_origen_id, artefacto_destino_id, tipo_integracion FROM artefacto_integraciones")->fetchAll(PDO::FETCH_ASSOC);
foreach ($intArt as $ia) {
    $aristasArtefactos[] = [
        'from' => $ia['artefacto_origen_id'],
        'to' => $ia['artefacto_destino_id'],
        'label' => $ia['tipo_integracion'],
        'arrows' => 'to',
        'font' => ['size' => 10, 'align' => 'middle'],
        'color' => ['color' => '#818cf8'] // indigo-400
    ];
}

// Convertir a JSON
$jsonSysNodes = json_encode($nodosSistemas);
$jsonSysEdges = json_encode($aristasSistemas);
$jsonArtNodes = json_encode($nodosArtefactos);
$jsonArtEdges = json_encode($aristasArtefactos);
?>

<!-- Importar la librería Vis.js -->
<script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>

<div class="max-w-7xl mx-auto mb-10 h-full flex flex-col">
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">🕸️ Diagrama de Integraciones</h2>
            <p class="text-slate-500 text-sm mt-1">Mapa topológico interactivo. Puedes arrastrar los nodos o hacer zoom con el ratón.</p>
        </div>
        <div class="flex items-center gap-4 text-xs font-medium text-slate-600">
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-emerald-200 border border-slate-500"></span> Producción</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-amber-200 border border-slate-500"></span> Desarrollo</span>
            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-full bg-sky-200 border border-slate-500"></span> Mantenimiento</span>
        </div>
    </div>

    <!-- Pestañas para cambiar de mapa -->
    <div class="border-b border-slate-200 bg-white rounded-t-lg">
        <nav class="flex -mb-px px-4" aria-label="Tabs">
            <button type="button" onclick="switchMap('sys')" id="btn-map-sys" class="py-4 px-6 border-b-2 font-bold text-sm text-indigo-600 border-indigo-600">
                🏢 Vista Nivel Sistemas
            </button>
            <button type="button" onclick="switchMap('art')" id="btn-map-art" class="py-4 px-6 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300">
                📦 Vista Nivel Componentes (Microservicios)
            </button>
        </nav>
    </div>

    <!-- Contenedores de los Mapas -->
    <div class="bg-white border border-t-0 border-slate-200 rounded-b-lg shadow-sm p-4 relative" style="height: 700px;">
        <div id="network-sistemas" class="absolute inset-4 border border-slate-200 bg-slate-50/50 rounded"></div>
        <div id="network-artefactos" class="absolute inset-4 border border-slate-200 bg-slate-50/50 rounded hidden"></div>
    </div>
</div>

<script>
    // --- MAPA DE SISTEMAS ---
    const nodesSys = new vis.DataSet(<?= $jsonSysNodes ?>);
    const edgesSys = new vis.DataSet(<?= $jsonSysEdges ?>);
    const containerSys = document.getElementById('network-sistemas');
    const dataSys = { nodes: nodesSys, edges: edgesSys };
    const optionsSys = {
        physics: {
            solver: 'forceAtlas2Based',
            forceAtlas2Based: { gravitationalConstant: -50, centralGravity: 0.01, springLength: 150 }
        },
        interaction: { hover: true, navigationButtons: true }
    };
    new vis.Network(containerSys, dataSys, optionsSys);

    // --- MAPA DE ARTEFACTOS ---
    const nodesArt = new vis.DataSet(<?= $jsonArtNodes ?>);
    const edgesArt = new vis.DataSet(<?= $jsonArtEdges ?>);
    const containerArt = document.getElementById('network-artefactos');
    const dataArt = { nodes: nodesArt, edges: edgesArt };
    const optionsArt = {
        physics: {
            solver: 'forceAtlas2Based',
            forceAtlas2Based: { gravitationalConstant: -80, centralGravity: 0.005, springLength: 200 }
        },
        interaction: { hover: true, navigationButtons: true }
    };
    new vis.Network(containerArt, dataArt, optionsArt);

    // --- LÓGICA DE CAMBIO DE PESTAÑA ---
    function switchMap(type) {
        if (type === 'sys') {
            document.getElementById('network-sistemas').classList.remove('hidden');
            document.getElementById('network-artefactos').classList.add('hidden');
            
            document.getElementById('btn-map-sys').className = "py-4 px-6 border-b-2 font-bold text-sm text-indigo-600 border-indigo-600";
            document.getElementById('btn-map-art').className = "py-4 px-6 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300";
        } else {
            document.getElementById('network-artefactos').classList.remove('hidden');
            document.getElementById('network-sistemas').classList.add('hidden');
            
            document.getElementById('btn-map-art').className = "py-4 px-6 border-b-2 font-bold text-sm text-indigo-600 border-indigo-600";
            document.getElementById('btn-map-sys').className = "py-4 px-6 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700 hover:border-slate-300";
        }
    }
</script>