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

$codigo           = trim($_POST['codigo'] ?? '');
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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    // 1. Actualizar entidad principal
    $sqlSistema = "UPDATE sistemas SET 
        codigo = :codigo, nombre = :nombre, estado = :estado, resumen = :resumen,
        git_repo = :git_repo, jenkins_url = :jenkins_url, elk_portainer_url = :elk_portainer_url, 
        monitoreo_web_url = :monitoreo_web_url, monitoreo_server = :monitoreo_server, monitoreo_glowroot = :monitoreo_glowroot
        WHERE id = :id";
    
    $stmtSistema = $pdo->prepare($sqlSistema);
    $stmtSistema->execute([
        ':codigo'            => $codigo,
        ':nombre'            => $nombre,
        ':estado'            => $estado,
        ':resumen'           => $resumen,
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
    // En actualizar_sistema.php borramos los viejos primero:
    $pdo->prepare("DELETE FROM sistema_agentes WHERE sistema_id = ?")->execute([$sistemaId]);

    // En AMBOS controladores, insertamos los marcados:
    if (!empty($_POST['agentes']) && is_array($_POST['agentes'])) {
        $stmtAg = $pdo->prepare("INSERT INTO sistema_agentes (sistema_id, agente_id) VALUES (?, ?)");
        foreach ($_POST['agentes'] as $agId) {
            $stmtAg->execute([$sistemaId, (int)$agId]);
        }
    }
    // Preparar sentencias
    $stmtAmb = $pdo->prepare("INSERT INTO sistema_ambientes (sistema_id, ambiente, url_acceso, tipo_auth, es_publico, servidor_app, tipo_despliegue, artefactos, variables_entorno, datasources_dblinks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtBk = $pdo->prepare("INSERT INTO sistema_respaldos (sistema_id, ambiente, pbs_job_ids, pbs_cronograma, pbs_alerta_monitoreo, bd_backup_nombres, bd_backup_cronograma, archivos_origen, archivos_destino, config_archivos, config_tipo_backup) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtDb = $pdo->prepare("INSERT INTO sistema_bases_datos (sistema_id, ambiente, nombre, ip, puerto, owner_db, tipo_servidor, tiene_passbolt, grupo_lectura, grupo_escritura, es_historica) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtIntSys = $pdo->prepare("INSERT INTO sistema_integraciones (sistema_id, sistema_destino_id, tipo_integracion, url_conexion) VALUES (?, ?, ?, ?)");
    
    // NUEVA SENTENCIA PARA ARTEFACTOS
    $stmtArt = $pdo->prepare("INSERT INTO sistema_artefactos (sistema_id, ambiente, codigo, nombre, url_privada, url_publica, tipo_auth, en_docker, ubicacion_alojamiento, tiene_mantenimiento, tecnologias) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtIntArt = $pdo->prepare("INSERT INTO artefacto_integraciones (artefacto_origen_id, artefacto_destino_id, tipo_integracion, url_conexion) VALUES (?, ?, ?, ?)");

    // 3. Reinsertar Integraciones de Sistema (Globales)
    if (!empty($_POST['integraciones_sis']) && is_array($_POST['integraciones_sis'])) {
        foreach ($_POST['integraciones_sis'] as $intSys) {
            if (!empty($intSys['sistema_destino_id'])) {
                $stmtIntSys->execute([
                    $sistemaId, 
                    (int)$intSys['sistema_destino_id'], 
                    trim($intSys['tipo_integracion'] ?? ''), 
                    trim($intSys['url_conexion'] ?? '')
                ]);
            }
        }
    }
    
    $ambientesPermitidos = ['desarrollo', 'test', 'produccion', 'capacitacion', 'herramientas'];

    // 4. Reinsertar Ambientes, Backups, BDs y Artefactos
    foreach ($ambientesPermitidos as $env) {
        
        $app = $_POST['ambientes'][$env] ?? [];
        $bk = $_POST['respaldos'][$env] ?? [];
        
        $tieneApp = !empty(trim($app['servidor_app'] ?? '')) || !empty(trim($app['url_acceso'] ?? ''));
        $tieneArtefactos = !empty($_POST['artefactos'][$env]);
        $tieneDbs = !empty($_POST['dbs'][$env]);
        $tieneBkps = count(array_filter($bk, function($val) { return $val !== '' && $val !== null; })) > 0;

        if (!$tieneApp && !$tieneArtefactos && !$tieneDbs && !$tieneBkps) {
            continue;
        }

        // Insertar Arquitectura
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

        // Insertar Respaldos
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

        // Insertar BDs
        if ($tieneDbs) {
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
        
        // Insertar Artefactos (con campos nuevos)
        if ($tieneArtefactos) {
            foreach ($_POST['artefactos'][$env] as $art) {
                if (!empty(trim($art['nombre'] ?? ''))) {
                    $stmtArt->execute([
                        $sistemaId, 
                        $env,
                        trim($art['codigo'] ?? ''),
                        trim($art['nombre']),
                        trim($art['url_privada'] ?? ''),
                        trim($art['url_publica'] ?? ''),
                        trim($art['tipo_auth'] ?? ''),
                        isset($art['en_docker']) ? 1 : 0,
                        trim($art['ubicacion_alojamiento'] ?? ''),
                        isset($art['tiene_mantenimiento']) ? 1 : 0,
                        trim($art['tecnologias'] ?? '')
                    ]);
                    
                    $artefactoOrigenId = (int) $pdo->lastInsertId();

                    if (!empty($art['integraciones']) && is_array($art['integraciones'])) {
                        foreach ($art['integraciones'] as $intArt) {
                            if (!empty($intArt['artefacto_destino_id'])) {
                                $stmtIntArt->execute([
                                    $artefactoOrigenId,
                                    (int)$intArt['artefacto_destino_id'],
                                    trim($intArt['tipo_integracion'] ?? ''),
                                    trim($intArt['url_conexion'] ?? '')
                                ]);
                            }
                        }
                    }
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
    
    die("<div style='padding:20px;background:#fee2e2;color:#991b1b;border:1px solid #ef4444;font-family:sans-serif;'>
        <strong>¡Error en la Base de Datos al intentar guardar!</strong><br><br>
        " . $e->getMessage() . "
        </div>");
}