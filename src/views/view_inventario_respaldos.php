<?php
// views/view_inventario_respaldos.php

function formatBytes($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = array('B', 'KB', 'MB', 'GB', 'TB');
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

$host_db = '192.168.200.142';
$port_db = '5432';
$db_name = 'infra_control';
$user_db = 'infra_control';
$pass_db = 'infra_control';
$error = null;

$fechas = [];
$all_hosts = [];
$all_grupos = [];
$hosts_filtered = [];
$matriz_datos = [];
$detalles_por_fecha = [];

$f_grupo = $_GET['f_grupo'] ?? '';
$f_host = $_GET['f_host'] ?? '';
$f_desde = $_GET['f_desde'] ?? '';
$f_hasta = $_GET['f_hasta'] ?? '';
$f_tipo = $_GET['f_tipo'] ?? ''; 
$f_vacios = isset($_GET['f_vacios']); 

try {
    $dsn = "pgsql:host=$host_db;port=$port_db;dbname=$db_name";
    $pdo = new PDO($dsn, $user_db, $pass_db, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Obtener listas completas para los filtros
    $all_grupos = $pdo->query("SELECT DISTINCT grupo FROM control_respaldos_archivos WHERE grupo IS NOT NULL ORDER BY grupo ASC")->fetchAll(PDO::FETCH_COLUMN);
    $all_hosts = $pdo->query("SELECT DISTINCT host_sistema FROM control_respaldos_archivos ORDER BY host_sistema ASC")->fetchAll(PDO::FETCH_COLUMN);

    $where = ["1=1"];
    $params = [];

    if ($f_grupo) {
        $where[] = "grupo = ?";
        $params[] = $f_grupo;
    }
    if ($f_host) {
        $where[] = "host_sistema = ?";
        $params[] = $f_host;
    }
    if ($f_desde) {
        $where[] = "fecha_respaldo >= ?";
        $params[] = $f_desde;
    }
    if ($f_hasta) {
        $where[] = "fecha_respaldo <= ?";
        $params[] = $f_hasta;
    }
    if ($f_tipo && $f_tipo !== 'mixto') {
        $where[] = "tipo_respaldo ILIKE ?";
        $params[] = $f_tipo;
    }

    $whereSql = implode(' AND ', $where);

    $stmtDias = $pdo->prepare("SELECT DISTINCT fecha_respaldo FROM control_respaldos_archivos WHERE $whereSql ORDER BY fecha_respaldo DESC LIMIT 15");
    $stmtDias->execute($params);
    $fechas_db = $stmtDias->fetchAll(PDO::FETCH_COLUMN);
    $fechas_db_asc = array_reverse($fechas_db); 

    if ($f_vacios && (!empty($f_desde) || !empty($f_hasta) || !empty($fechas_db_asc))) {
        $start_date = $f_desde ?: ($fechas_db_asc[0] ?? date('Y-m-d', strtotime('-14 days')));
        $end_date = $f_hasta ?: (end($fechas_db_asc) ?: date('Y-m-d'));
        
        $current = strtotime($start_date);
        $last = strtotime($end_date);
        
        $days_diff = round(($last - $current) / (60 * 60 * 24));
        if ($days_diff > 35) {
            $current = strtotime('-35 days', $last);
        }
        
        while ($current <= $last) {
            $fechas[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }
    } else {
        $fechas = $fechas_db_asc;
    }

    $stmtHosts = $pdo->prepare("SELECT DISTINCT host_sistema FROM control_respaldos_archivos WHERE $whereSql ORDER BY host_sistema ASC");
    $stmtHosts->execute($params);
    $hosts_filtered = $stmtHosts->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($fechas_db_asc)) {
        $stmtDatos = $pdo->prepare("SELECT host_sistema, fecha_respaldo, tipo_respaldo, detalle_carpeta, peso_bytes, grupo FROM control_respaldos_archivos WHERE $whereSql");
        $stmtDatos->execute($params);
        
        while ($row = $stmtDatos->fetch(PDO::FETCH_ASSOC)) {
            $h = $row['host_sistema'];
            $f = $row['fecha_respaldo'];
            $t = strtolower($row['tipo_respaldo']);
            $det = $row['detalle_carpeta'];
            $peso = formatBytes((float)($row['peso_bytes'] ?? 0));
            $g = $row['grupo'] ?? 'Sin Grupo';
            
            $matriz_datos[$h][$f][] = ['tipo' => $t, 'detalle' => $det, 'peso' => $peso];
            $detalles_por_fecha[$f][] = ['host' => $h, 'tipo' => $t, 'detalle' => $det, 'peso' => $peso, 'grupo' => $g];
        }
    }

    if ($f_tipo === 'mixto') {
        foreach ($hosts_filtered as $key => $h) {
            $tiene_mixto = false;
            foreach ($fechas as $f) {
                if (isset($matriz_datos[$h][$f])) {
                    $tipos_unicos = array_unique(array_column($matriz_datos[$h][$f], 'tipo'));
                    if (count($tipos_unicos) > 1) {
                        $tiene_mixto = true;
                        break;
                    }
                }
            }
            if (!$tiene_mixto) {
                unset($hosts_filtered[$key]);
            }
        }
    }

} catch (PDOException $e) {
    $error = "Error de conexión a la base de datos de control: " . $e->getMessage();
}
?>

<div class="space-y-6 w-full relative">

    <div class="bg-slate-800 p-6 rounded-xl border border-slate-700 shadow-lg flex flex-col gap-4">
        <div>
            <h1 class="text-xl font-bold text-white flex items-center gap-2">🗄️ Inventario de Respaldos</h1>
            <p class="text-xs text-slate-400 mt-0.5">Matriz de control. Clickeá en los estados o en las fechas para ver los detalles.</p>
        </div>

        <form method="GET" action="/index.php" class="flex flex-wrap items-end gap-4 w-full">
            <input type="hidden" name="page" value="view_inventario_respaldos">
            
            <div class="flex-1 min-w-[150px]">
                <label class="text-xs text-slate-400 block mb-1">Grupo</label>
                <select name="f_grupo" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-purple-500">
                    <option value="">Todos los grupos</option>
                    <?php foreach ($all_grupos as $g): ?>
                        <option value="<?= htmlspecialchars($g) ?>" <?= $f_grupo === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-slate-400 block mb-1">Origen / Sistema</label>
                <select name="f_host" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-purple-500">
                    <option value="">Todos los orígenes</option>
                    <?php foreach ($all_hosts as $h): ?>
                        <option value="<?= htmlspecialchars($h) ?>" <?= $f_host === $h ? 'selected' : '' ?>><?= htmlspecialchars($h) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="w-32">
                <label class="text-xs text-slate-400 block mb-1">Desde</label>
                <input type="date" name="f_desde" value="<?= htmlspecialchars($f_desde) ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-purple-500">
            </div>

            <div class="w-32">
                <label class="text-xs text-slate-400 block mb-1">Hasta</label>
                <input type="date" name="f_hasta" value="<?= htmlspecialchars($f_hasta) ?>" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-purple-500">
            </div>

            <div class="w-32">
                <label class="text-xs text-slate-400 block mb-1">Tipo</label>
                <select name="f_tipo" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-slate-100 focus:outline-none focus:border-purple-500">
                    <option value="">Todos</option>
                    <option value="full" <?= $f_tipo === 'full' ? 'selected' : '' ?>>FULL</option>
                    <option value="snap" <?= $f_tipo === 'snap' ? 'selected' : '' ?>>SNAP</option>
                    <option value="mixto" <?= $f_tipo === 'mixto' ? 'selected' : '' ?>>MIXTO</option>
                </select>
            </div>

            <div class="flex items-center gap-2 mb-2 ml-2">
                <input type="checkbox" name="f_vacios" id="f_vacios" <?= $f_vacios ? 'checked' : '' ?> class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-purple-600 focus:ring-purple-500 focus:ring-offset-slate-800 cursor-pointer">
                <label for="f_vacios" class="text-xs font-semibold text-slate-300 cursor-pointer">Mostrar vacíos</label>
            </div>

            <div class="flex gap-2 ml-auto">
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Filtrar</button>
                <a href="?page=view_inventario_respaldos" class="bg-slate-700 hover:bg-slate-600 text-slate-200 font-semibold px-4 py-2 rounded-lg text-sm transition flex items-center">Limpiar</a>
            </div>
        </form>
    </div>

    <?php if ($error): ?>
        <div class="bg-amber-500/10 border border-amber-500/30 text-amber-400 p-4 rounded-xl flex items-center gap-3">
            <span class="text-xl">⚠️</span>
            <p class="text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$error): ?>
        <div class="bg-slate-800 rounded-xl border border-slate-700 shadow-lg overflow-hidden">
            <div class="p-6">
                <?php if (empty($hosts_filtered) || empty($fechas)): ?>
                    <div class="p-6 bg-slate-900/50 border border-slate-700/60 rounded-xl text-center text-slate-400 text-sm">
                        No se encontraron registros que coincidan con los filtros aplicados.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto rounded-xl border border-slate-700">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-900 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-700">
                                    <th class="p-4 sticky left-0 bg-slate-900 border-r border-slate-700 z-10 w-64 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.3)]">
                                        Origen / Sistema
                                    </th>
                                    <?php foreach ($fechas as $fecha): 
                                        $tieneDatos = isset($detalles_por_fecha[$fecha]);
                                        $jsonDia = $tieneDatos ? htmlspecialchars(json_encode($detalles_por_fecha[$fecha]), ENT_QUOTES, 'UTF-8') : '[]';
                                        $cursorClass = $tieneDatos ? 'cursor-pointer hover:bg-slate-800 hover:text-purple-300' : 'opacity-60 cursor-not-allowed';
                                    ?>
                                        <th <?= $tieneDatos ? "onclick=\"abrirModal('Respaldos del " . date('d/m/Y', strtotime($fecha)) . "', $jsonDia)\"" : "" ?> 
                                            class="p-4 text-center whitespace-nowrap transition-colors <?= $cursorClass ?>"
                                            title="<?= $tieneDatos ? 'Click para ver todos los respaldos de este día' : 'Sin actividad registrada' ?>">
                                            <?= htmlspecialchars(date('d/m', strtotime($fecha))) ?> <?= $tieneDatos ? '🔍' : '' ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-700/60 text-sm">
                                <?php foreach ($hosts_filtered as $host): ?>
                                    <tr class="hover:bg-slate-700/30 transition-colors group">
                                        <td class="p-4 font-bold text-slate-300 whitespace-nowrap sticky left-0 bg-slate-800 border-r border-slate-700/60 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] group-hover:bg-slate-700 transition-colors">
                                            <?= htmlspecialchars($host) ?>
                                        </td>
                                        <?php foreach ($fechas as $fecha): ?>
                                            <td class="p-4 text-center whitespace-nowrap">
                                                <?php 
                                                if (isset($matriz_datos[$host][$fecha])) {
                                                    $tipos_unicos = array_unique(array_column($matriz_datos[$host][$fecha], 'tipo'));
                                                    $jsonCelda = htmlspecialchars(json_encode($matriz_datos[$host][$fecha]), ENT_QUOTES, 'UTF-8');
                                                    $onClick = "onclick=\"abrirModal('Detalles de $host ($fecha)', $jsonCelda)\"";
                                                    
                                                    if (count($tipos_unicos) > 1) {
                                                        echo "<button $onClick class='inline-flex items-center justify-center text-[10px] font-bold text-purple-300 bg-purple-950/80 border border-purple-800/60 px-2 py-1 rounded w-16 cursor-pointer hover:scale-110 hover:bg-purple-900 transform transition shadow-md'>MIXTO</button>";
                                                    } elseif (in_array('full', $tipos_unicos)) {
                                                        echo "<button $onClick class='inline-flex items-center justify-center text-[10px] font-bold text-emerald-300 bg-emerald-950/80 border border-emerald-800/60 px-2 py-1 rounded w-16 cursor-pointer hover:scale-110 hover:bg-emerald-900 transform transition shadow-md'>FULL</button>";
                                                    } elseif (in_array('snap', $tipos_unicos) || in_array('incremental', $tipos_unicos)) {
                                                        echo "<button $onClick class='inline-flex items-center justify-center text-[10px] font-bold text-sky-300 bg-sky-950/50 border border-sky-800/50 px-2 py-1 rounded w-16 cursor-pointer hover:scale-110 hover:bg-sky-900 transform transition shadow-md'>SNAP</button>";
                                                    } else {
                                                        echo "<button $onClick class='inline-flex items-center justify-center text-[10px] font-bold text-slate-300 bg-slate-900 border border-slate-700 px-2 py-1 rounded w-16 cursor-pointer hover:scale-110 hover:bg-slate-800 transform transition shadow-md'>" . htmlspecialchars(strtoupper($tipos_unicos[0])) . "</button>";
                                                    }
                                                } else {
                                                    if ($f_vacios) {
                                                        echo '<span class="text-rose-500/80 font-bold text-sm select-none" title="Sin respaldo">✘</span>';
                                                    } else {
                                                        echo '<span class="text-slate-600 font-bold opacity-50 select-none">-</span>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="infoModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-800 border border-slate-700 rounded-xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[85vh]">
            
            <div class="p-4 bg-slate-900 border-b border-slate-700 flex justify-between items-center">
                <h3 id="modalTitle" class="text-lg font-bold text-white flex items-center gap-2">📂 Detalles</h3>
                <button onclick="cerrarModal()" class="text-slate-400 hover:text-white transition text-xl font-bold px-2">&times;</button>
            </div>
            
            <div class="p-0 overflow-y-auto bg-slate-800">
                <table class="w-full text-left border-collapse">
                    <thead class="sticky top-0 bg-slate-900 shadow">
                        <tr class="text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-700">
                            <th id="colGrupo" class="p-3">Grupo</th>
                            <th id="colHost" class="p-3">Sistema / Origen</th>
                            <th class="p-3">Tipo</th>
                            <th class="p-3 text-right">Tamaño</th>
                            <th class="p-3">Ruta / Archivo en TrueNAS</th>
                        </tr>
                    </thead>
                    <tbody id="modalBody" class="divide-y divide-slate-700/60 text-sm">
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 bg-slate-900 border-t border-slate-700 text-right">
                <button onclick="cerrarModal()" class="bg-slate-700 hover:bg-slate-600 text-white font-semibold px-4 py-2 rounded-lg text-sm transition">Cerrar</button>
            </div>
        </div>
    </div>

</div>

<script>
    function abrirModal(titulo, datosJson) {
        document.getElementById('modalTitle').innerText = '📂 ' + titulo;
        const tbody = document.getElementById('modalBody');
        tbody.innerHTML = '';
        
        let showHostColumn = false;
        let showGrupoColumn = false;

        if(datosJson.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="p-6 text-center text-slate-400 text-sm">No hay registros para mostrar.</td></tr>`;
        } else {
            datosJson.forEach(item => {
                if (item.host) showHostColumn = true;
                if (item.grupo) showGrupoColumn = true;
                
                let grupoHtml = item.grupo ? `<td class="p-3 text-slate-400 text-xs">${item.grupo}</td>` : '<td class="p-3 text-slate-500 italic">N/A</td>';
                let hostHtml = item.host ? `<td class="p-3 text-slate-200 font-medium">${item.host}</td>` : '<td class="p-3 text-slate-500 italic">Mismo Origen</td>';
                
                let typeBadge = '';
                if(item.tipo === 'full') {
                    typeBadge = '<span class="text-emerald-400 font-bold bg-emerald-950/50 px-2 py-0.5 rounded border border-emerald-800/50">FULL</span>';
                } else if(item.tipo === 'snap' || item.tipo === 'incremental') {
                    typeBadge = '<span class="text-sky-400 font-bold bg-sky-950/50 px-2 py-0.5 rounded border border-sky-800/50">SNAP</span>';
                } else {
                    typeBadge = `<span class="text-slate-300 font-bold bg-slate-700 px-2 py-0.5 rounded border border-slate-600">${item.tipo.toUpperCase()}</span>`;
                }

                tbody.innerHTML += `
                    <tr class="hover:bg-slate-700/30 transition-colors">
                        ${grupoHtml}
                        ${hostHtml}
                        <td class="p-3 whitespace-nowrap">${typeBadge}</td>
                        <td class="p-3 text-right text-purple-300 font-medium whitespace-nowrap">${item.peso || '0 B'}</td>
                        <td class="p-3 text-slate-400 text-xs font-mono break-all">${item.detalle}</td>
                    </tr>
                `;
            });
        }

        document.getElementById('colHost').style.display = showHostColumn ? 'table-cell' : 'none';
        document.getElementById('colGrupo').style.display = showGrupoColumn ? 'table-cell' : 'none';
        
        Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
            if(tr.children.length > 2) {
                tr.children[0].style.display = showGrupoColumn ? 'table-cell' : 'none';
                tr.children[1].style.display = showHostColumn ? 'table-cell' : 'none';
            }
        });

        document.getElementById('infoModal').classList.remove('hidden');
    }

    function cerrarModal() {
        document.getElementById('infoModal').classList.add('hidden');
    }

    document.getElementById('infoModal').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });
</script>