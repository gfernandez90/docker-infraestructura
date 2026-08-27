<?php
// src/controllers/actualizar_sistema.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /index.php?page=sistemas');
    exit;
}

$sistemaId = filter_input(INPUT_POST, 'sistema_id', FILTER_VALIDATE_INT);
if (!$sistemaId) {
    $_SESSION['flash_error'] = 'ID inválido.';
    header('Location: /index.php?page=sistemas');
    exit;
}

$nombre           = trim($_POST['nombre'] ?? '');
$estado           = trim($_POST['estado'] ?? 'desarrollo');
$resumen          = trim($_POST['resumen'] ?? '');
$responsable      = trim($_POST['responsable'] ?? '');
$desarrolladores  = trim($_POST['desarrolladores'] ?? '');

$gitRepo          = trim($_POST['git_repo'] ?? '');
$jenkinsUrl       = trim($_POST['jenkins_url'] ?? '');
$elkPortainerUrl  = trim($_POST['elk_portainer_url'] ?? '');
$monitoreoWebUrl  = trim($_POST['monitoreo_web_url'] ?? '');
$monitoreoServer  = trim($_POST['monitoreo_server'] ?? '');
$monitoreoGlowroot= trim($_POST['monitoreo_glowroot'] ?? '');

if (empty($nombre)) {
    $_SESSION['flash_error'] = 'El nombre del sistema es obligatorio.';
    header("Location: /index.php?page=editar_sistema&id={$sistemaId}");
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Actualizar entidad principal
    $sqlSistema = "UPDATE sistemas SET 
        nombre = :nombre, estado = :estado, resumen = :resumen, responsable = :responsable, desarrolladores = :desarrolladores,
        git_repo = :git_repo, jenkins_url = :jenkins_url, elk_portainer_url = :elk_portainer_url, 
        monitoreo_web_url = :monitoreo_web_url, monitoreo_server = :monitoreo_server, monitoreo_glowroot = :monitoreo_glowroot
        WHERE id = :id";
    
    $stmtSistema = $pdo->prepare($sqlSistema);
    $stmtSistema->execute([
        ':nombre'            => $nombre,
        ':estado'            => $estado,
        ':resumen'           => $resumen,
        ':responsable'       => $responsable,
        ':desarrolladores'   => $desarrolladores,
        ':git_repo'          => $gitRepo,
        ':jenkins_url'       => $jenkinsUrl,
        ':elk_portainer_url' => $elkPortainerUrl,
        ':monitoreo_web_url' => $monitoreoWebUrl,
        ':monitoreo_server'  => $monitoreoServer,
        ':monitoreo_glowroot'=> $monitoreoGlowroot,
        ':id'                => $sistemaId
    ]);

    // 2. Limpiar tablas relacionadas
    $pdo->prepare("DELETE FROM sistema_integraciones WHERE sistema_id = ?")->execute([$sistemaId]);
    $pdo->prepare("DELETE FROM sistema_ambientes WHERE sistema_id = ?")->execute([$sistemaId]);
    $pdo->prepare("DELETE FROM sistema_respaldos WHERE sistema_id = ?")->execute([$sistemaId]);
    $pdo->prepare("DELETE FROM sistema_bases_datos WHERE sistema_id = ?")->execute([$sistemaId]);
    $pdo->prepare("DELETE FROM sistema_artefactos WHERE sistema_id = ?")->execute([$sistemaId]);

    // 3. Reinsertar Integraciones
    if (!empty($_POST['integraciones']) && is_array($_POST['integraciones'])) {
        $stmtInt = $pdo->prepare("INSERT INTO sistema_integraciones (sistema_id, nombre_sistema, tipo_integracion) VALUES (?, ?, ?)");
        foreach ($_POST['integraciones'] as $intg) {
            if (!empty(trim($intg['nombre_sistema']))) {
                $stmtInt->execute([$sistemaId, trim($intg['nombre_sistema']), trim($intg['tipo_integracion'])]);
            }
        }
    }

    // Preparar sentencias
    $stmtAmb = $pdo->prepare("INSERT INTO sistema_ambientes (sistema_id, ambiente, url_acceso, tipo_auth, es_publico, servidor_app, tipo_despliegue, artefactos, variables_entorno, datasources_dblinks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtBk = $pdo->prepare("INSERT INTO sistema_respaldos (sistema_id, ambiente, pbs_job_ids, pbs_cronograma, pbs_alerta_monitoreo, bd_backup_nombres, bd_backup_cronograma, archivos_origen, archivos_destino, config_archivos, config_tipo_backup) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtDb = $pdo->prepare("INSERT INTO sistema_bases_datos (sistema_id, ambiente, nombre, ip, puerto, owner_db, tipo_servidor, tiene_passbolt, grupo_lectura, grupo_escritura, es_historica) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtArt = $pdo->prepare("INSERT INTO sistema_artefactos (sistema_id, ambiente, nombre, url_privada, url_publica, tipo_auth) VALUES (?, ?, ?, ?, ?, ?)");

    $ambientesPermitidos = ['desarrollo', 'test', 'produccion', 'capacitacion', 'herramientas'];

    // 4. Reinsertar Ambientes, Backups y BDs
    foreach ($ambientesPermitidos as $env) {
        // App / Arquitectura
        $app = $_POST['ambientes'][$env] ?? [];
        // Ignoramos si no se completó ni URL ni servidor en este ambiente
        if (empty(trim($app['servidor_app'] ?? '')) && empty(trim($app['url_acceso'] ?? ''))) {
            continue;
        }

        $stmtAmb->execute([
            $sistemaId, $env,
            trim($app['url_acceso'] ?? ''),
            trim($app['tipo_auth'] ?? ''),
            isset($app['es_publico']) ? 1 : 0,
            trim($app['servidor_app'] ?? ''),
            trim($app['tipo_despliegue'] ?? 'contenedor'),
            trim($app['artefactos'] ?? ''),
            trim($app['variables_entorno'] ?? ''),
            trim($app['datasources_dblinks'] ?? '')
        ]);

        // Respaldos
        $bk = $_POST['respaldos'][$env] ?? [];
        $stmtBk->execute([
            $sistemaId, $env,
            trim($bk['pbs_job_ids'] ?? ''),
            trim($bk['pbs_cronograma'] ?? ''),
            isset($bk['pbs_alerta_monitoreo']) ? 1 : 0,
            trim($bk['bd_backup_nombres'] ?? ''),
            trim($bk['bd_backup_cronograma'] ?? ''),
            trim($bk['archivos_origen'] ?? ''),
            trim($bk['archivos_destino'] ?? ''),
            trim($bk['config_archivos'] ?? ''),
            trim($bk['config_tipo_backup'] ?? '')
        ]);

        // BDs
        if (!empty($_POST['dbs'][$env]) && is_array($_POST['dbs'][$env])) {
            foreach ($_POST['dbs'][$env] as $db) {
                if (!empty(trim($db['nombre'] ?? ''))) {
                    $stmtDb->execute([
                        $sistemaId, $env,
                        trim($db['nombre']),
                        trim($db['ip'] ?? ''),
                        trim($db['puerto'] ?? ''),
                        trim($db['owner_db'] ?? ''),
                        trim($db['tipo_servidor'] ?? 'Contenedor'),
                        isset($db['tiene_passbolt']) ? 1 : 0,
                        trim($db['grupo_lectura'] ?? ''),
                        trim($db['grupo_escritura'] ?? ''),
                        isset($db['es_historica']) ? 1 : 0
                    ]);
                }
            }
        }
        // Datos de los Artefactos y URLs
	if (!empty($_POST['artefactos'][$env]) && is_array($_POST['artefactos'][$env])) {
	    foreach ($_POST['artefactos'][$env] as $art) {
	        if (!empty(trim($art['nombre'] ?? ''))) {
	            $stmtArt->execute([
	                $sistemaId, $env,
	                trim($art['nombre']),
	                trim($art['url_privada'] ?? ''),
	                trim($art['url_publica'] ?? ''),
	                trim($art['tipo_auth'] ?? '')
	            ]);
	        }
	    }
	}
    }

    $pdo->commit();
    $_SESSION['flash_success'] = "Sistema '{$nombre}' actualizado correctamente.";
    header('Location: /index.php?page=sistemas');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error actualizando sistema: " . $e->getMessage());
    $_SESSION['flash_error'] = "Error al actualizar: " . $e->getMessage();
    header("Location: /index.php?page=editar_sistema&id={$sistemaId}");
    exit;
}
