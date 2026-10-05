<?php
// src/views/view_editar_sistema.php

$sistemaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$sistemaId) { header('Location: /index.php?page=sistemas'); exit; }

$sistema = $pdo->query("SELECT * FROM sistemas WHERE id = $sistemaId")->fetch(PDO::FETCH_ASSOC);
if (!$sistema) { header('Location: /index.php?page=sistemas'); exit; }

// Catálogos para Dropdowns
$todosSistemas = $pdo->query("SELECT id, nombre FROM sistemas ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$todosArtefactosRaw = $pdo->query("SELECT id, sistema_id, nombre, ambiente FROM sistema_artefactos ORDER BY ambiente, nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
$catalogoArtefactos = [];
foreach ($todosArtefactosRaw as $art) { $catalogoArtefactos[$art['sistema_id']][] = $art; }
$jsonSistemas = json_encode($todosSistemas);
$jsonArtefactos = json_encode($catalogoArtefactos);

// Cargar datos
$ambRaw = $pdo->query("SELECT * FROM sistema_ambientes WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$ambientes = []; foreach($ambRaw as $a) $ambientes[$a['ambiente']] = $a;

$bkRaw = $pdo->query("SELECT * FROM sistema_respaldos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$respaldos = []; foreach($bkRaw as $r) $respaldos[$r['ambiente']] = $r;

$dbRaw = $pdo->query("SELECT * FROM sistema_bases_datos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$dbs = []; foreach($dbRaw as $d) $dbs[$d['ambiente']][] = $d;

$intSysRaw = $pdo->query("SELECT * FROM sistema_integraciones WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);

// Artefactos
$artRaw = $pdo->query("SELECT * FROM sistema_artefactos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$artIds = array_column($artRaw, 'id');
$artInts = [];

if (!empty($artIds)) {
    $inClause = implode(',', $artIds);
    $intArtRaw = $pdo->query("
        SELECT ai.*, sa_dest.sistema_id AS sistema_destino_id 
        FROM artefacto_integraciones ai
        JOIN sistema_artefactos sa_dest ON ai.artefacto_destino_id = sa_dest.id
        WHERE ai.artefacto_origen_id IN ($inClause)
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($intArtRaw as $ia) { $artInts[$ia['artefacto_origen_id']][] = $ia; }
}

$artefactos = []; 
foreach($artRaw as $ar) {
    $ar['integraciones'] = $artInts[$ar['id']] ?? [];
    $artefactos[$ar['ambiente']][] = $ar;
}

$listaAmbientes = ['desarrollo' => 'Desarrollo', 'test' => 'Test', 'produccion' => 'Producción', 'capacitacion' => 'Capacitación', 'herramientas' => 'Herramientas'];
$todosAgentes = $pdo->query("SELECT * FROM agentes ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// SOLO EN view_editar_sistema.php añade también esto para pre-seleccionar los guardados:
$agentesAsignados = $pdo->query("SELECT agente_id FROM sistema_agentes WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="max-w-7xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">✏️ Editar Sistema: <?= htmlspecialchars($sistema['nombre'] ?? '') ?></h2>
        <a href="/index.php?page=sistemas" class="text-slate-500 hover:text-slate-800 font-medium text-sm">← Volver</a>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
        
        <!-- Pestañas -->
        <div class="border-b border-slate-200 bg-slate-50 flex overflow-x-auto">
            <button type="button" onclick="showTab('tab-general')" id="btn-tab-general" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-indigo-600 border-indigo-600">📑 General</button>
            <button type="button" onclick="showTab('tab-devops')" id="btn-tab-devops" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">🛠️ DevOps</button>
            <button type="button" onclick="showTab('tab-integraciones')" id="btn-tab-integraciones" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">🔗 Int. de Sistema</button>
            <div class="border-l border-slate-300 mx-2 my-2"></div>
            <?php foreach ($listaAmbientes as $key => $name): ?>
                <button type="button" onclick="showTab('tab-env-<?= $key ?>')" id="btn-tab-env-<?= $key ?>" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">🖥️ <?= $name ?></button>
            <?php endforeach; ?>
        </div>

        <form action="/index.php?page=actualizar_sistema" method="POST" class="p-6">
            <input type="hidden" name="sistema_id" value="<?= $sistema['id'] ?>">
            
            <!-- 1. GENERAL -->
            <div id="tab-general" class="tab-content block space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Código del Sistema</label>
                        <input type="text" name="codigo" value="<?= htmlspecialchars($sistema['codigo'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm bg-slate-50" placeholder="Ej: ANEP-DGEIP-SIS-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Sistema *</label>
                        <input type="text" name="nombre" value="<?= htmlspecialchars($sistema['nombre'] ?? '') ?>" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Estado</label>
                        <select name="estado" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm bg-white">
                            <option value="produccion" <?= ($sistema['estado'] ?? '') === 'produccion' ? 'selected' : '' ?>>Producción</option>
                            <option value="desarrollo" <?= ($sistema['estado'] ?? '') === 'desarrollo' ? 'selected' : '' ?>>Desarrollo</option>
                            <option value="mantenimiento" <?= ($sistema['estado'] ?? '') === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                            <option value="obsoleto" <?= ($sistema['estado'] ?? '') === 'obsoleto' ? 'selected' : '' ?>>Obsoleto</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Resumen</label>
                        <textarea name="resumen" rows="2" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm"><?= htmlspecialchars($sistema['resumen'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2 lg:col-span-3">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Actores Asignados al Sistema</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 border border-slate-300 p-4 rounded-md bg-white h-48 overflow-y-auto shadow-inner">
                            <?php foreach($todosAgentes as $ag): 
                                // Para vista CREAR, quita el in_array y deja $checked = '';
                                $checked = isset($agentesAsignados) && in_array($ag['id'], $agentesAsignados) ? 'checked' : '';
                            ?>
                                <label class="flex items-center text-sm text-slate-700 cursor-pointer hover:bg-slate-50 p-1 rounded transition">
                                    <input type="checkbox" name="agentes[]" value="<?= $ag['id'] ?>" <?= $checked ?> class="mr-2 text-indigo-600 focus:ring-indigo-500 rounded h-4 w-4">
                                    <div class="leading-tight">
                                        <span class="font-bold block"><?= htmlspecialchars($ag['nombre']) ?></span> 
                                        <span class="text-[10px] bg-slate-200 px-1.5 py-0.5 rounded text-slate-600"><?= htmlspecialchars($ag['cargo']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DEVOPS -->
            <div id="tab-devops" class="tab-content hidden space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label class="block text-sm font-medium text-slate-700 mb-1">Git Repo</label><input type="text" name="git_repo" value="<?= htmlspecialchars($sistema['git_repo'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700 mb-1">Jenkins URL</label><input type="text" name="jenkins_url" value="<?= htmlspecialchars($sistema['jenkins_url'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700 mb-1">Monitoreo Server</label><input type="text" name="monitoreo_server" value="<?= htmlspecialchars($sistema['monitoreo_server'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium text-slate-700 mb-1">Glowroot (URL/Host)</label><input type="text" name="monitoreo_glowroot" value="<?= htmlspecialchars($sistema['monitoreo_glowroot'] ?? '') ?>" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm"></div>
                </div>
            </div>

            <!-- 3. INTEGRACIONES GLOBALES -->
            <div id="tab-integraciones" class="tab-content hidden space-y-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-md font-semibold text-slate-800">Sistemas Integrados Directamente</h3>
                    <button type="button" onclick="addIntegracionSistema()" class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded text-sm font-medium hover:bg-indigo-100">➕ Agregar Integración</button>
                </div>
                <div id="integraciones-sis-container" class="space-y-3">
                    <?php foreach ($intSysRaw as $i => $intSys): ?>
                        <div class="flex gap-3 items-end bg-slate-50 p-3 rounded border border-slate-200" id="intg-sis-<?= $i ?>">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Sistema Destino</label>
                                <select name="integraciones_sis[<?= $i ?>][sistema_destino_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm bg-white" required>
                                    <option value="">Seleccione...</option>
                                    <?php foreach($todosSistemas as $ts): ?>
                                        <option value="<?= $ts['id'] ?>" <?= $ts['id'] == ($intSys['sistema_destino_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($ts['nombre'] ?? '') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="w-48">
                                <label class="block text-xs font-medium text-slate-600 mb-1">Tipo</label>
                                <select name="integraciones_sis[<?= $i ?>][tipo_integracion]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm bg-white">
                                    <option value="REST" <?= ($intSys['tipo_integracion'] ?? '') == 'REST' ? 'selected' : '' ?>>REST</option>
                                    <option value="SOAP" <?= ($intSys['tipo_integracion'] ?? '') == 'SOAP' ? 'selected' : '' ?>>SOAP</option>
                                    <option value="Vista Directa" <?= ($intSys['tipo_integracion'] ?? '') == 'Vista Directa' ? 'selected' : '' ?>>Vista Directa</option>
                                    <option value="API" <?= ($intSys['tipo_integracion'] ?? '') == 'API' ? 'selected' : '' ?>>API</option>
                                </select>
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-slate-600 mb-1">URL Conexión</label>
                                <input type="text" name="integraciones_sis[<?= $i ?>][url_conexion]" value="<?= htmlspecialchars($intSys['url_conexion'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                            </div>
                            <button type="button" onclick="document.getElementById('intg-sis-<?= $i ?>').remove()" class="bg-red-100 text-red-600 px-3 py-1 mb-[2px] rounded text-sm">X</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 4. AMBIENTES (Bucle) -->
            <?php foreach ($listaAmbientes as $envKey => $envName): 
                $a = $ambientes[$envKey] ?? []; 
                $b = $respaldos[$envKey] ?? []; 
                $dbList = $dbs[$envKey] ?? [];
                $artefactosList = $artefactos[$envKey] ?? [];
            ?>
                <div id="tab-env-<?= $envKey ?>" class="tab-content hidden space-y-8">
                    <!-- Arquitectura -->
                    <div>
                        <h3 class="text-md font-semibold text-slate-800 border-b border-slate-200 pb-2 mb-4">Arquitectura (<?= $envName ?>)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="block text-xs mb-1">URL (Acceso)</label><input type="text" name="ambientes[<?= $envKey ?>][url_acceso]" value="<?= htmlspecialchars($a['url_acceso'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                            <div><label class="block text-xs mb-1">Auth (CAS, LDAP)</label><input type="text" name="ambientes[<?= $envKey ?>][tipo_auth]" value="<?= htmlspecialchars($a['tipo_auth'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                            <div class="flex items-end mb-2"><label class="flex items-center text-sm"><input type="checkbox" name="ambientes[<?= $envKey ?>][es_publico]" value="1" <?= !empty($a['es_publico']) ? 'checked' : '' ?> class="mr-2 h-4 w-4 text-indigo-600 rounded"> Acceso Público</label></div>
                            
                            <div><label class="block text-xs mb-1">Servidor de App</label><input type="text" name="ambientes[<?= $envKey ?>][servidor_app]" value="<?= htmlspecialchars($a['servidor_app'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                            <div>
                                <label class="block text-xs mb-1">Tipo Despliegue</label>
                                <select name="ambientes[<?= $envKey ?>][tipo_despliegue]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm bg-white">
                                    <option value="contenedor" <?= ($a['tipo_despliegue'] ?? '') === 'contenedor' ? 'selected' : '' ?>>Contenedor</option>
                                    <option value="standalone" <?= ($a['tipo_despliegue'] ?? '') === 'standalone' ? 'selected' : '' ?>>Standalone</option>
                                    <option value="compartido" <?= ($a['tipo_despliegue'] ?? '') === 'compartido' ? 'selected' : '' ?>>Compartido</option>
                                </select>
                            </div>
                            <div><label class="block text-xs mb-1">Archivos/Dist</label><textarea name="ambientes[<?= $envKey ?>][artefactos]" rows="1" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"><?= htmlspecialchars($a['artefactos'] ?? '') ?></textarea></div>
                            <div class="md:col-span-1"><label class="block text-xs mb-1">Variables Entorno</label><textarea name="ambientes[<?= $envKey ?>][variables_entorno]" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"><?= htmlspecialchars($a['variables_entorno'] ?? '') ?></textarea></div>
                            <div class="md:col-span-2"><label class="block text-xs mb-1">Datasources / DBLinks</label><textarea name="ambientes[<?= $envKey ?>][datasources_dblinks]" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"><?= htmlspecialchars($a['datasources_dblinks'] ?? '') ?></textarea></div>
                        </div>
                    </div>

                    <!-- Componentes / Artefactos -->
                    <div class="bg-indigo-50/50 p-4 rounded-lg border border-indigo-100 mb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-indigo-900">📦 Artefactos y URLs (<?= $envName ?>)</h3>
                            <button type="button" onclick="addArtefacto('<?= $envKey ?>')" class="bg-white border border-indigo-300 text-indigo-700 px-3 py-1 rounded text-xs font-medium hover:bg-indigo-100 shadow-sm">➕ Añadir Artefacto</button>
                        </div>
                        
                        <div id="artefactos-container-<?= $envKey ?>" class="space-y-4">
                            <?php foreach ($artefactosList as $artIdx => $art): ?>
                                <div class="bg-white p-4 rounded border border-slate-200 relative shadow-sm" id="art-<?= $envKey ?>-<?= $artIdx ?>">
                                    <button type="button" onclick="document.getElementById('art-<?= $envKey ?>-<?= $artIdx ?>').remove()" class="absolute top-2 right-2 text-red-500 font-bold bg-red-50 px-2 rounded hover:bg-red-100">X</button>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                                        <div><label class="block text-xs mb-1 text-slate-500">Código</label><input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][codigo]" value="<?= htmlspecialchars($art['codigo'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                        <div><label class="block text-xs mb-1 text-slate-500">Nombre *</label><input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][nombre]" value="<?= htmlspecialchars($art['nombre'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs" required></div>
                                        <div><label class="block text-xs mb-1 text-slate-500">Auth</label><input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][tipo_auth]" value="<?= htmlspecialchars($art['tipo_auth'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                        <div>
                                            <label class="block text-xs mb-1 text-slate-500">Alojamiento</label>
                                            <select name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][ubicacion_alojamiento]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                                                <option value="">Seleccione...</option>
                                                <option value="Primaria" <?= ($art['ubicacion_alojamiento'] ?? '') == 'Primaria' ? 'selected' : '' ?>>Primaria</option>
                                                <option value="Codicen" <?= ($art['ubicacion_alojamiento'] ?? '') == 'Codicen' ? 'selected' : '' ?>>Codicen</option>
                                                <option value="HG" <?= ($art['ubicacion_alojamiento'] ?? '') == 'HG' ? 'selected' : '' ?>>HG</option>
                                                <option value="AWS" <?= ($art['ubicacion_alojamiento'] ?? '') == 'AWS' ? 'selected' : '' ?>>AWS</option>
                                                <option value="Antel" <?= ($art['ubicacion_alojamiento'] ?? '') == 'Antel' ? 'selected' : '' ?>>Antel</option>
                                                <option value="Otro" <?= ($art['ubicacion_alojamiento'] ?? '') == 'Otro' ? 'selected' : '' ?>>Otro</option>
                                            </select>
                                        </div>
                                        <div class="md:col-span-2"><label class="block text-xs mb-1 text-slate-500">Tecnologías</label><input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][tecnologias]" value="<?= htmlspecialchars($art['tecnologias'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs" placeholder="Ej: Genexus, Angular, Spring..."></div>
                                        <div><label class="block text-xs mb-1 text-slate-500">URL Privada</label><input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][url_privada]" value="<?= htmlspecialchars($art['url_privada'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                        <div><label class="block text-xs mb-1 text-slate-500">URL Pública</label><input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][url_publica]" value="<?= htmlspecialchars($art['url_publica'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    </div>
                                    <div class="flex gap-4 mb-3 border-t border-slate-100 pt-3">
                                        <label class="flex items-center text-xs text-slate-700 font-medium"><input type="checkbox" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][en_docker]" value="1" <?= !empty($art['en_docker']) ? 'checked' : '' ?> class="mr-1 h-3 w-3 text-indigo-600 rounded"> ¿En Docker?</label>
                                        <label class="flex items-center text-xs text-slate-700 font-medium"><input type="checkbox" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][tiene_mantenimiento]" value="1" <?= !empty($art['tiene_mantenimiento']) ? 'checked' : '' ?> class="mr-1 h-3 w-3 text-indigo-600 rounded"> Tiene Mantenimiento</label>
                                    </div>

                                    <!-- Integraciones de este componente -->
                                    <div class="bg-slate-50 border border-slate-200 rounded p-3 mt-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-xs font-bold text-slate-600">Integraciones de este componente</span>
                                            <button type="button" onclick="addIntegracionComponente('<?= $envKey ?>', <?= $artIdx ?>)" class="bg-white border border-slate-300 text-slate-700 px-2 py-0.5 rounded text-[10px] font-medium hover:bg-slate-100">➕ Añadir Enlace</button>
                                        </div>
                                        <div id="intg-art-container-<?= $envKey ?>-<?= $artIdx ?>" class="space-y-2">
                                            <?php foreach ($art['integraciones'] as $intIdx => $intArt): ?>
                                                <div class="flex flex-wrap md:flex-nowrap gap-2 items-end bg-white p-2 rounded border border-slate-200" id="intg-art-<?= $envKey ?>-<?= $artIdx ?>-<?= $intIdx ?>">
                                                    <div class="w-full md:w-1/4">
                                                        <label class="block text-[10px] font-medium text-slate-500 mb-1">Sistema Filtro</label>
                                                        <select onchange="cargarComponentesDestino(this, '<?= $envKey ?>', <?= $artIdx ?>, <?= $intIdx ?>)" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                                                            <option value="">Seleccione...</option>
                                                            <?php foreach($todosSistemas as $ts): ?>
                                                                <option value="<?= $ts['id'] ?>" <?= $ts['id'] == ($intArt['sistema_destino_id'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars($ts['nombre'] ?? '') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="w-full md:w-1/4">
                                                        <label class="block text-[10px] font-medium text-slate-500 mb-1">Componente Destino</label>
                                                        <select id="select-destino-<?= $envKey ?>-<?= $artIdx ?>-<?= $intIdx ?>" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][integraciones][<?= $intIdx ?>][artefacto_destino_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white" required>
                                                            <?php 
                                                                $opts = $catalogoArtefactos[$intArt['sistema_destino_id'] ?? ''] ?? [];
                                                                foreach($opts as $op):
                                                                    $sel = $op['id'] == ($intArt['artefacto_destino_id'] ?? '') ? 'selected' : '';
                                                            ?>
                                                                <option value="<?= $op['id'] ?>" <?= $sel ?>><?= htmlspecialchars(($op['nombre'] ?? '') . ' (' . ($op['ambiente'] ?? '') . ')') ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="w-full md:w-1/6">
                                                        <label class="block text-[10px] font-medium text-slate-500 mb-1">Tipo</label>
                                                        <select name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][integraciones][<?= $intIdx ?>][tipo_integracion]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                                                            <option value="REST" <?= ($intArt['tipo_integracion'] ?? '') == 'REST' ? 'selected' : '' ?>>REST</option>
                                                            <option value="SOAP" <?= ($intArt['tipo_integracion'] ?? '') == 'SOAP' ? 'selected' : '' ?>>SOAP</option>
                                                            <option value="Vista Directa" <?= ($intArt['tipo_integracion'] ?? '') == 'Vista Directa' ? 'selected' : '' ?>>Vista</option>
                                                            <option value="API" <?= ($intArt['tipo_integracion'] ?? '') == 'API' ? 'selected' : '' ?>>API</option>
                                                        </select>
                                                    </div>
                                                    <div class="flex-1">
                                                        <label class="block text-[10px] font-medium text-slate-500 mb-1">URL Conexión</label>
                                                        <input type="text" name="artefactos[<?= $envKey ?>][<?= $artIdx ?>][integraciones][<?= $intIdx ?>][url_conexion]" value="<?= htmlspecialchars($intArt['url_conexion'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                                    </div>
                                                    <button type="button" onclick="document.getElementById('intg-art-<?= $envKey ?>-<?= $artIdx ?>-<?= $intIdx ?>').remove()" class="text-red-500 bg-red-50 px-2 py-1 rounded text-xs">X</button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- DBs -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 mb-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-slate-800">Bases de Datos (<?= $envName ?>)</h3>
                            <button type="button" onclick="addDb('<?= $envKey ?>')" class="bg-white border border-slate-300 text-slate-700 px-3 py-1 rounded text-xs font-medium">➕ Añadir BD</button>
                        </div>
                        <div id="dbs-container-<?= $envKey ?>" class="space-y-4">
                            <?php foreach ($dbList as $i => $db): ?>
                                <!-- [El código de BD se mantiene igual...] -->
                                <div class="bg-white p-3 rounded border border-slate-200 grid grid-cols-2 md:grid-cols-4 gap-3 relative" id="db-<?= $envKey ?>-<?= $i ?>">
                                    <button type="button" onclick="document.getElementById('db-<?= $envKey ?>-<?= $i ?>').remove()" class="absolute top-2 right-2 text-red-500 font-bold bg-red-50 px-2 rounded">X</button>
                                    <div class="col-span-2 md:col-span-1"><label class="block text-xs mb-1">Nombre BD</label><input type="text" name="dbs[<?= $envKey ?>][<?= $i ?>][nombre]" value="<?= htmlspecialchars($db['nombre'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    <div><label class="block text-xs mb-1">IP</label><input type="text" name="dbs[<?= $envKey ?>][<?= $i ?>][ip]" value="<?= htmlspecialchars($db['ip'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    <div><label class="block text-xs mb-1">Puerto</label><input type="text" name="dbs[<?= $envKey ?>][<?= $i ?>][puerto]" value="<?= htmlspecialchars($db['puerto'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    <div><label class="block text-xs mb-1">Owner</label><input type="text" name="dbs[<?= $envKey ?>][<?= $i ?>][owner_db]" value="<?= htmlspecialchars($db['owner_db'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    <div>
                                        <label class="block text-xs mb-1">Tipo Servidor</label>
                                        <select name="dbs[<?= $envKey ?>][<?= $i ?>][tipo_servidor]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                                            <option value="Contenedor" <?= ($db['tipo_servidor'] ?? '') == 'Contenedor' ? 'selected' : '' ?>>Contenedor</option><option value="Standalone" <?= ($db['tipo_servidor'] ?? '') == 'Standalone' ? 'selected' : '' ?>>Standalone</option><option value="Cluster" <?= ($db['tipo_servidor'] ?? '') == 'Cluster' ? 'selected' : '' ?>>Cluster</option>
                                        </select>
                                    </div>
                                    <div><label class="block text-xs mb-1">Grupo Lectura</label><input type="text" name="dbs[<?= $envKey ?>][<?= $i ?>][grupo_lectura]" value="<?= htmlspecialchars($db['grupo_lectura'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    <div><label class="block text-xs mb-1">Grupo Escritura</label><input type="text" name="dbs[<?= $envKey ?>][<?= $i ?>][grupo_escritura]" value="<?= htmlspecialchars($db['grupo_escritura'] ?? '') ?>" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                                    <div class="col-span-2 md:col-span-4 flex gap-4 mt-2 border-t border-slate-100 pt-2">
                                        <label class="flex items-center text-xs text-slate-700"><input type="checkbox" name="dbs[<?= $envKey ?>][<?= $i ?>][tiene_passbolt]" value="1" <?= !empty($db['tiene_passbolt']) ? 'checked' : '' ?> class="mr-1"> Passbolt</label>
                                        <label class="flex items-center text-xs text-slate-700"><input type="checkbox" name="dbs[<?= $envKey ?>][<?= $i ?>][es_historica]" value="1" <?= !empty($db['es_historica']) ? 'checked' : '' ?> class="mr-1"> Es Histórica</label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Backups -->
                    <!-- [Se mantiene idéntico, acortado visualmente para no saturar] -->
                    <div>
                        <h3 class="text-md font-semibold text-slate-800 border-b border-slate-200 pb-2 mb-4">Respaldos (<?= $envName ?>)</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <div class="border border-slate-200 p-3 rounded bg-white"><div class="text-xs font-bold text-slate-500 uppercase mb-2">💾 Nivel VM</div><div class="space-y-2"><input type="text" name="respaldos[<?= $envKey ?>][pbs_job_ids]" value="<?= htmlspecialchars($b['pbs_job_ids'] ?? '') ?>" placeholder="PBS Job IDs" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"><input type="text" name="respaldos[<?= $envKey ?>][pbs_cronograma]" value="<?= htmlspecialchars($b['pbs_cronograma'] ?? '') ?>" placeholder="Cronograma" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div></div>
                            <div class="border border-slate-200 p-3 rounded bg-white"><div class="text-xs font-bold text-slate-500 uppercase mb-2">🗄️ Nivel BD</div><div class="space-y-2"><input type="text" name="respaldos[<?= $envKey ?>][bd_backup_nombres]" value="<?= htmlspecialchars($b['bd_backup_nombres'] ?? '') ?>" placeholder="Bases respaldadas" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"><input type="text" name="respaldos[<?= $envKey ?>][bd_backup_cronograma]" value="<?= htmlspecialchars($b['bd_backup_cronograma'] ?? '') ?>" placeholder="Cronograma" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div></div>
                            <div class="border border-slate-200 p-3 rounded bg-white"><div class="text-xs font-bold text-slate-500 uppercase mb-2">📁 Nivel Archivos</div><div class="space-y-2"><input type="text" name="respaldos[<?= $envKey ?>][archivos_origen]" value="<?= htmlspecialchars($b['archivos_origen'] ?? '') ?>" placeholder="Carpeta Origen" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"><input type="text" name="respaldos[<?= $envKey ?>][archivos_destino]" value="<?= htmlspecialchars($b['archivos_destino'] ?? '') ?>" placeholder="Destino (NFS)" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div></div>
                            <div class="border border-slate-200 p-3 rounded bg-white"><div class="text-xs font-bold text-slate-500 uppercase mb-2">⚙️ Nivel Configs</div><div class="space-y-2"><input type="text" name="respaldos[<?= $envKey ?>][config_archivos]" value="<?= htmlspecialchars($b['config_archivos'] ?? '') ?>" placeholder="Archivos a respaldar" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"><input type="text" name="respaldos[<?= $envKey ?>][config_tipo_backup]" value="<?= htmlspecialchars($b['config_tipo_backup'] ?? '') ?>" placeholder="Destino / Tipo" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="mt-8 pt-5 border-t border-slate-200 flex justify-end gap-3">
                <a href="/index.php?page=sistemas" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-md hover:bg-slate-50 text-sm font-medium">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 shadow-sm text-sm font-medium">🔄 Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    const catalogoSistemas = <?= $jsonSistemas ?>;
    const catalogoArtefactos = <?= $jsonArtefactos ?>;
    
    let optionsSistemas = '<option value="">Seleccione...</option>';
    catalogoSistemas.forEach(sis => { optionsSistemas += `<option value="${sis.id}">${sis.nombre}</option>`; });

    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(el => {
            el.classList.remove('text-indigo-600', 'border-indigo-600');
            el.classList.add('text-slate-500', 'border-transparent');
        });
        document.getElementById(tabId).classList.remove('hidden');
        const btn = document.getElementById('btn-' + tabId);
        btn.classList.remove('text-slate-500', 'border-transparent');
        btn.classList.add('text-indigo-600', 'border-indigo-600');
    }

    function addIntegracionSistema() {
        const container = document.getElementById('integraciones-sis-container');
        const i = container.children.length;
        const html = `
            <div class="flex gap-3 items-end bg-slate-50 p-3 rounded border border-slate-200" id="intg-sis-${i}">
                <div class="flex-1"><label class="block text-xs mb-1">Sistema Destino</label><select name="integraciones_sis[${i}][sistema_destino_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm bg-white" required>${optionsSistemas}</select></div>
                <div class="w-48"><label class="block text-xs mb-1">Tipo</label><select name="integraciones_sis[${i}][tipo_integracion]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm bg-white"><option value="REST">REST</option><option value="SOAP">SOAP</option><option value="Vista Directa">Vista Directa</option><option value="API">API</option></select></div>
                <div class="flex-1"><label class="block text-xs mb-1">URL Conexión</label><input type="text" name="integraciones_sis[${i}][url_conexion]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></div>
                <button type="button" onclick="document.getElementById('intg-sis-${i}').remove()" class="bg-red-100 text-red-600 px-3 py-1 mb-[2px] rounded text-sm">X</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function addArtefacto(envKey) {
        const container = document.getElementById(`artefactos-container-${envKey}`);
        const artIndex = container.children.length;
        const html = `
            <div class="bg-white p-4 rounded border border-slate-200 relative shadow-sm mb-4" id="art-${envKey}-${artIndex}">
                <button type="button" onclick="document.getElementById('art-${envKey}-${artIndex}').remove()" class="absolute top-2 right-2 text-red-500 font-bold bg-red-50 px-2 rounded hover:bg-red-100">X</button>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3">
                    <div><label class="block text-xs mb-1 text-slate-500">Código</label><input type="text" name="artefactos[${envKey}][${artIndex}][codigo]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                    <div><label class="block text-xs mb-1 text-slate-500">Nombre *</label><input type="text" name="artefactos[${envKey}][${artIndex}][nombre]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs" required></div>
                    <div><label class="block text-xs mb-1 text-slate-500">Auth</label><input type="text" name="artefactos[${envKey}][${artIndex}][tipo_auth]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                    <div>
                        <label class="block text-xs mb-1 text-slate-500">Alojamiento</label>
                        <select name="artefactos[${envKey}][${artIndex}][ubicacion_alojamiento]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                            <option value="">Seleccione...</option><option value="Primaria">Primaria</option><option value="Codicen">Codicen</option><option value="HG">HG</option><option value="AWS">AWS</option><option value="Antel">Antel</option><option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="md:col-span-2"><label class="block text-xs mb-1 text-slate-500">Tecnologías</label><input type="text" name="artefactos[${envKey}][${artIndex}][tecnologias]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs" placeholder="Ej: Genexus, Spring..."></div>
                    <div><label class="block text-xs mb-1 text-slate-500">URL Privada</label><input type="text" name="artefactos[${envKey}][${artIndex}][url_privada]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                    <div><label class="block text-xs mb-1 text-slate-500">URL Pública</label><input type="text" name="artefactos[${envKey}][${artIndex}][url_publica]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                </div>
                <div class="flex gap-4 mb-3 border-t border-slate-100 pt-3">
                    <label class="flex items-center text-xs text-slate-700 font-medium"><input type="checkbox" name="artefactos[${envKey}][${artIndex}][en_docker]" value="1" class="mr-1 h-3 w-3 text-indigo-600 rounded"> ¿En Docker?</label>
                    <label class="flex items-center text-xs text-slate-700 font-medium"><input type="checkbox" name="artefactos[${envKey}][${artIndex}][tiene_mantenimiento]" value="1" class="mr-1 h-3 w-3 text-indigo-600 rounded"> Tiene Mantenimiento</label>
                </div>
                <div class="bg-slate-50 border border-slate-200 rounded p-3 mt-3">
                    <div class="flex justify-between items-center mb-2"><span class="text-xs font-bold text-slate-600">Integraciones de este componente</span><button type="button" onclick="addIntegracionComponente('${envKey}', ${artIndex})" class="bg-white border border-slate-300 text-slate-700 px-2 py-0.5 rounded text-[10px] font-medium hover:bg-slate-100">➕ Añadir Enlace</button></div>
                    <div id="intg-art-container-${envKey}-${artIndex}" class="space-y-2"></div>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function addIntegracionComponente(envKey, artIndex) {
        const container = document.getElementById(`intg-art-container-${envKey}-${artIndex}`);
        const intIndex = container.children.length;
        const selectSistema = `<select onchange="cargarComponentesDestino(this, '${envKey}', ${artIndex}, ${intIndex})" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">${optionsSistemas}</select>`;

        const html = `
            <div class="flex flex-wrap md:flex-nowrap gap-2 items-end bg-white p-2 rounded border border-slate-200" id="intg-art-${envKey}-${artIndex}-${intIndex}">
                <div class="w-full md:w-1/4"><label class="block text-[10px] text-slate-500 mb-1">Sistema Filtro</label>${selectSistema}</div>
                <div class="w-full md:w-1/4">
                    <label class="block text-[10px] text-slate-500 mb-1">Componente Destino</label>
                    <select id="select-destino-${envKey}-${artIndex}-${intIndex}" name="artefactos[${envKey}][${artIndex}][integraciones][${intIndex}][artefacto_destino_id]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white" required>
                        <option value="">Seleccione sistema...</option>
                    </select>
                </div>
                <div class="w-full md:w-1/6">
                    <label class="block text-[10px] text-slate-500 mb-1">Tipo</label>
                    <select name="artefactos[${envKey}][${artIndex}][integraciones][${intIndex}][tipo_integracion]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                        <option value="REST">REST</option><option value="SOAP">SOAP</option><option value="Vista Directa">Vista</option><option value="API">API</option>
                    </select>
                </div>
                <div class="flex-1"><label class="block text-[10px] text-slate-500 mb-1">URL Conexión</label><input type="text" name="artefactos[${envKey}][${artIndex}][integraciones][${intIndex}][url_conexion]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div>
                <button type="button" onclick="document.getElementById('intg-art-${envKey}-${artIndex}-${intIndex}').remove()" class="text-red-500 bg-red-50 px-2 py-1 rounded text-xs">X</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function cargarComponentesDestino(selectElement, envKey, artIndex, intIndex) {
        const sistemaId = selectElement.value;
        const targetSelect = document.getElementById(`select-destino-${envKey}-${artIndex}-${intIndex}`);
        targetSelect.innerHTML = '<option value="">Seleccione componente...</option>';
        if (sistemaId && catalogoArtefactos[sistemaId]) {
            catalogoArtefactos[sistemaId].forEach(art => {
                targetSelect.innerHTML += `<option value="${art.id}">${art.nombre} (${art.ambiente})</option>`;
            });
        }
    }

    function addDb(envKey) {
        // [Función idéntica a la actual, la resumo visualmente aquí para mantener limpieza]
        const container = document.getElementById(`dbs-container-${envKey}`);
        const i = container.children.length;
        const html = `<div class="bg-white p-3 rounded border border-slate-200 grid grid-cols-2 md:grid-cols-4 gap-3 relative" id="db-${envKey}-${i}"><button type="button" onclick="document.getElementById('db-${envKey}-${i}').remove()" class="absolute top-2 right-2 text-red-500 font-bold bg-red-50 px-2 rounded">X</button><div class="col-span-2 md:col-span-1"><label class="block text-xs mb-1">Nombre BD</label><input type="text" name="dbs[${envKey}][${i}][nombre]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div><div><label class="block text-xs mb-1">IP</label><input type="text" name="dbs[${envKey}][${i}][ip]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div><div><label class="block text-xs mb-1">Puerto</label><input type="text" name="dbs[${envKey}][${i}][puerto]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div><div><label class="block text-xs mb-1">Owner</label><input type="text" name="dbs[${envKey}][${i}][owner_db]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div><div><label class="block text-xs mb-1">Tipo Servidor</label><select name="dbs[${envKey}][${i}][tipo_servidor]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white"><option value="Contenedor">Contenedor</option><option value="Standalone">Standalone</option><option value="Cluster">Cluster</option></select></div><div><label class="block text-xs mb-1">Grupo Lectura</label><input type="text" name="dbs[${envKey}][${i}][grupo_lectura]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div><div><label class="block text-xs mb-1">Grupo Escritura</label><input type="text" name="dbs[${envKey}][${i}][grupo_escritura]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs"></div><div class="col-span-2 md:col-span-4 flex gap-4 mt-2 border-t border-slate-100 pt-2"><label class="flex items-center text-xs text-slate-700"><input type="checkbox" name="dbs[${envKey}][${i}][tiene_passbolt]" value="1" class="mr-1"> Passbolt</label><label class="flex items-center text-xs text-slate-700"><input type="checkbox" name="dbs[${envKey}][${i}][es_historica]" value="1" class="mr-1"> Es Histórica</label></div></div>`;
        container.insertAdjacentHTML('beforeend', html);
    }
</script>