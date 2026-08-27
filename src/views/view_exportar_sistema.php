<?php
// src/views/view_exportar_sistema.php

$sistemaId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$sistemaId) { header('Location: /index.php?page=sistemas'); exit; }

// 1. Traer todos los datos del sistema
$sistema = $pdo->query("SELECT * FROM sistemas WHERE id = $sistemaId")->fetch(PDO::FETCH_ASSOC);
if (!$sistema) { header('Location: /index.php?page=sistemas'); exit; }

$integraciones = $pdo->query("SELECT * FROM sistema_integraciones WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);

// Traer y agrupar por ambiente
$ambRaw = $pdo->query("SELECT * FROM sistema_ambientes WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$ambientes = []; foreach($ambRaw as $a) $ambientes[$a['ambiente']] = $a;

$artRaw = $pdo->query("SELECT * FROM sistema_artefactos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$artefactos = []; foreach($artRaw as $ar) $artefactos[$ar['ambiente']][] = $ar;

$dbRaw = $pdo->query("SELECT * FROM sistema_bases_datos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$dbs = []; foreach($dbRaw as $d) $dbs[$d['ambiente']][] = $d;

$bkRaw = $pdo->query("SELECT * FROM sistema_respaldos WHERE sistema_id = $sistemaId")->fetchAll(PDO::FETCH_ASSOC);
$respaldos = []; foreach($bkRaw as $r) $respaldos[$r['ambiente']] = $r;

// 2. CONSTRUIR EL MARKDOWN
$md = "# 🖥️ Sistema: " . trim($sistema['nombre']) . "\n\n";

$md .= "> **Resumen:** " . (trim($sistema['resumen']) ?: 'Sin descripción provista.') . "\n\n";

$md .= "## 📋 Información General\n";
$md .= "* **Estado:** `" . strtoupper($sistema['estado']) . "`\n";
$md .= "* **PM / Responsable:** " . (trim($sistema['responsable']) ?: 'N/A') . "\n";
$md .= "* **Desarrolladores:** " . (trim($sistema['desarrolladores']) ?: 'N/A') . "\n\n";

$md .= "## 🛠️ DevOps & Monitoreo\n";
$md .= "* **Repositorio Git:** " . (trim($sistema['git_repo']) ? "[Link]({$sistema['git_repo']})" : 'N/A') . "\n";
$md .= "* **Pipeline (Jenkins):** " . (trim($sistema['jenkins_url']) ? "[Link]({$sistema['jenkins_url']})" : 'N/A') . "\n";
$md .= "* **Monitoreo Server:** " . (trim($sistema['monitoreo_server']) ?: 'N/A') . "\n";
$md .= "* **Monitoreo APM (Glowroot):** " . (trim($sistema['monitoreo_glowroot']) ?: 'N/A') . "\n\n";

if (!empty($integraciones)) {
    $md .= "## 🔗 Integraciones\n";
    $md .= "| Sistema Integrado | Tipo de Integración |\n";
    $md .= "|---|---|\n";
    foreach ($integraciones as $intg) {
        $md .= "| " . trim($intg['nombre_sistema']) . " | " . trim($intg['tipo_integracion']) . " |\n";
    }
    $md .= "\n";
}

$md .= "---\n\n";
$md .= "## 🌍 Ambientes e Infraestructura\n\n";

$listaAmbientes = ['produccion' => 'Producción', 'capacitacion' => 'Capacitación', 'test' => 'Test', 'desarrollo' => 'Desarrollo', 'herramientas' => 'Herramientas'];

foreach ($listaAmbientes as $envKey => $envName) {
    $a = $ambientes[$envKey] ?? [];
    $artList = $artefactos[$envKey] ?? [];
    $dbList = $dbs[$envKey] ?? [];
    $bk = $respaldos[$envKey] ?? [];

    // Si el ambiente no tiene servidor de app, ni artefactos, ni DBs, lo saltamos
    if (empty(trim($a['servidor_app'] ?? '')) && empty($artList) && empty($dbList)) {
        continue;
    }

    $md .= "### ▶️ Ambiente: {$envName}\n\n";
    
    // Arquitectura del ambiente
    $md .= "**Arquitectura Base:**\n";
    $md .= "* **Servidor de Aplicación:** `" . (trim($a['servidor_app'] ?? '') ?: 'N/A') . "`\n";
    $md .= "* **Tipo Despliegue:** " . (trim($a['tipo_despliegue'] ?? '') ?: 'N/A') . "\n";
    if (!empty(trim($a['artefactos'] ?? ''))) {
        $md .= "* **Artefactos (Ruta/Nombres):** " . trim($a['artefactos']) . "\n";
    }
    if (!empty(trim($a['variables_entorno'] ?? ''))) {
        $md .= "* **Variables Entorno:** `" . trim($a['variables_entorno']) . "`\n";
    }
    if (!empty(trim($a['datasources_dblinks'] ?? ''))) {
        $md .= "* **Datasources/DBLinks:** " . trim($a['datasources_dblinks']) . "\n";
    }
    $md .= "\n";

    // Artefactos y URLs
    if (!empty($artList)) {
        $md .= "**📦 Componentes y Accesos (URLs):**\n";
        $md .= "| Artefacto/Módulo | Auth | URL Privada | URL Pública |\n";
        $md .= "|---|---|---|---|\n";
        foreach ($artList as $art) {
            $md .= "| **" . trim($art['nombre']) . "** | " . (trim($art['tipo_auth']) ?: '-') . " | " . (trim($art['url_privada']) ?: '-') . " | " . (trim($art['url_publica']) ?: '-') . " |\n";
        }
        $md .= "\n";
    }

    // Bases de Datos
    if (!empty($dbList)) {
        $md .= "**🗄️ Bases de Datos:**\n";
        $md .= "| Nombre BD | IP | Puerto | Tipo | Passbolt | Histórica | Owner | G. Lectura | G. Escritura |\n";
        $md .= "|---|---|---|---|:---:|:---:|---|---|---|\n";
        foreach ($dbList as $db) {
            $pb = $db['tiene_passbolt'] ? '✅' : '-';
            $hi = $db['es_historica'] ? '✅' : '-';
            $md .= "| **" . trim($db['nombre']) . "** | " . trim($db['ip']) . " | " . trim($db['puerto']) . " | " . trim($db['tipo_servidor']) . " | $pb | $hi | " . trim($db['owner_db']) . " | " . trim($db['grupo_lectura']) . " | " . trim($db['grupo_escritura']) . " |\n";
        }
        $md .= "\n";
    }

    // Respaldos
    if (!empty(array_filter($bk))) {
        $md .= "**💾 Políticas de Respaldo:**\n";
        $md .= "* **Nivel VM (PBS):** " . (trim($bk['pbs_job_ids']) ?: 'N/A') . " *(Cronograma: " . (trim($bk['pbs_cronograma']) ?: '-') . ")*\n";
        $md .= "* **Nivel Base de Datos:** " . (trim($bk['bd_backup_nombres']) ?: 'N/A') . " *(Cronograma: " . (trim($bk['bd_backup_cronograma']) ?: '-') . ")*\n";
        $md .= "* **Nivel Archivos:** Origen: `" . (trim($bk['archivos_origen']) ?: 'N/A') . "` -> Destino: `" . (trim($bk['archivos_destino']) ?: 'N/A') . "`\n";
        $md .= "* **Nivel Configuraciones:** `" . (trim($bk['config_archivos']) ?: 'N/A') . "` *(Medio: " . (trim($bk['config_tipo_backup']) ?: '-') . ")*\n";
        $md .= "\n";
    }
    
    $md .= "---\n\n";
}

$md .= "*Documentación generada automáticamente desde la Plataforma de Infraestructura DGEIP.*";
?>

<div class="max-w-5xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">📄 Exportar a Wiki.js: <?= htmlspecialchars($sistema['nombre']) ?></h2>
        <a href="/index.php?page=sistemas" class="text-slate-500 hover:text-slate-800 font-medium text-sm">
            ← Volver al listado
        </a>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden flex flex-col">
        <div class="bg-slate-50 border-b border-slate-200 p-4 flex justify-between items-center">
            <p class="text-sm text-slate-600">Copia el siguiente texto y pégalo directamente en el editor Markdown de tu Wiki.</p>
            <button onclick="copiarMarkdown()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow-sm text-sm font-bold transition flex items-center gap-2">
                📋 Copiar al Portapapeles
            </button>
        </div>
        
        <div class="p-0">
            <textarea id="markdownContent" readonly class="w-full h-[600px] p-4 font-mono text-sm bg-slate-900 text-emerald-400 focus:outline-none resize-y"><?= htmlspecialchars($md) ?></textarea>
        </div>
    </div>
</div>

<script>
function copiarMarkdown() {
    const textarea = document.getElementById("markdownContent");
    textarea.select();
    textarea.setSelectionRange(0, 99999); // Para móviles
    
    navigator.clipboard.writeText(textarea.value).then(() => {
        alert("¡Markdown copiado al portapapeles exitosamente!");
    }).catch(err => {
        alert("Error al copiar: " + err);
    });
}
</script>
