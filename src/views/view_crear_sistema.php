<?php
// src/views/view_crear_sistema.php
$listaAmbientes = [
    'desarrollo' => 'Desarrollo',
    'test' => 'Test',
    'produccion' => 'Producción',
    'capacitacion' => 'Capacitación',
    'herramientas' => 'Herramientas'
];
?>
<div class="max-w-7xl mx-auto mb-10">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-slate-800">⚙️ Registrar Nuevo Sistema</h2>
        <a href="/index.php?page=sistemas" class="text-slate-500 hover:text-slate-800 font-medium text-sm">← Volver</a>
    </div>

    <div class="bg-white shadow-sm rounded-lg border border-slate-200 overflow-hidden">
        
        <!-- Pestañas (Navegación) -->
        <div class="border-b border-slate-200 bg-slate-50 flex overflow-x-auto">
            <button type="button" onclick="showTab('tab-general')" id="btn-tab-general" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-indigo-600 border-indigo-600">📑 General</button>
            <button type="button" onclick="showTab('tab-devops')" id="btn-tab-devops" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">🛠️ DevOps</button>
            <button type="button" onclick="showTab('tab-integraciones')" id="btn-tab-integraciones" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">🔗 Integraciones</button>
            
            <div class="border-l border-slate-300 mx-2 my-2"></div>
            
            <?php foreach ($listaAmbientes as $key => $name): ?>
                <button type="button" onclick="showTab('tab-env-<?= $key ?>')" id="btn-tab-env-<?= $key ?>" class="tab-btn whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm text-slate-500 border-transparent hover:text-slate-700">
                    🖥️ <?= $name ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Formulario -->
        <form action="/index.php?page=guardar_sistema" method="POST" class="p-6">
            
            <!-- 1. GENERAL -->
            <div id="tab-general" class="tab-content block space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Nombre del Sistema *</label>
                        <input type="text" name="nombre" required class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Estado</label>
                        <select name="estado" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm bg-white">
                            <option value="produccion">Producción</option>
                            <option value="desarrollo" selected>Desarrollo</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="obsoleto">Obsoleto / Histórico</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Resumen del Sistema</label>
                        <textarea name="resumen" rows="2" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">PM / Responsable</label>
                        <input type="text" name="responsable" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Desarrolladores</label>
                        <input type="text" name="desarrolladores" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <!-- 2. DEVOPS -->
            <div id="tab-devops" class="tab-content hidden space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Repositorio Git</label>
                        <input type="text" name="git_repo" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">URL de Jenkins</label>
                        <input type="text" name="jenkins_url" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Host Monitoreo Server (Zabbix/Prometheus)</label>
                        <input type="text" name="monitoreo_server" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Monitoreo Glowroot (Host/URL)</label>
                        <input type="text" name="monitoreo_glowroot" class="w-full border border-slate-300 rounded-md px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <!-- 3. INTEGRACIONES -->
            <div id="tab-integraciones" class="tab-content hidden space-y-4">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-md font-semibold text-slate-800">Sistemas Integrados</h3>
                    <button type="button" onclick="addIntegracion()" class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded text-sm font-medium hover:bg-indigo-100">➕ Agregar Integración</button>
                </div>
                <div id="integraciones-container" class="space-y-3">
                    <!-- Filas dinámicas -->
                </div>
            </div>

            <!-- 4. AMBIENTES (Generados con Bucle) -->
            <?php foreach ($listaAmbientes as $envKey => $envName): ?>
                <div id="tab-env-<?= $envKey ?>" class="tab-content hidden space-y-8">
                    
                    <!-- A. Arquitectura -->
                    <div>
                        <h3 class="text-md font-semibold text-slate-800 border-b border-slate-200 pb-2 mb-4">Arquitectura (<?= $envName ?>)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Servidor de Aplicación</label>
                                <input type="text" name="ambientes[<?= $envKey ?>][servidor_app]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Tipo de Despliegue</label>
                                <select name="ambientes[<?= $envKey ?>][tipo_despliegue]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm bg-white">
                                    <option value="contenedor">Contenedor</option>
                                    <option value="standalone">Standalone</option>
                                    <option value="compartido">Compartido</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Artefactos</label>
                                <textarea name="ambientes[<?= $envKey ?>][artefactos]" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Variables de Entorno</label>
                                <textarea name="ambientes[<?= $envKey ?>][variables_entorno]" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></textarea>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Datasources / DBLinks</label>
                                <textarea name="ambientes[<?= $envKey ?>][datasources_dblinks]" rows="2" class="w-full border border-slate-300 rounded px-2 py-1 text-sm"></textarea>
                            </div>
                        </div>
                    </div>
		<!-- Componentes / Artefactos -->
		<div class="bg-indigo-50/50 p-4 rounded-lg border border-indigo-100 mb-4">
		    <div class="flex justify-between items-center mb-4">
		        <h3 class="text-sm font-bold text-indigo-900">📦 Artefactos y URLs (<?= $envName ?>)</h3>
		        <button type="button" onclick="addArtefacto('<?= $envKey ?>')" class="bg-white border border-indigo-300 text-indigo-700 px-3 py-1 rounded text-xs font-medium hover:bg-indigo-100 shadow-sm">➕ Añadir Artefacto</button>
		    </div>
		    
		    <div id="artefactos-container-<?= $envKey ?>" class="space-y-4">
		        <!-- AQUÍ EN view_editar_sistema.php DEBERÁS HACER UN FOREACH DE $artefactosList COMO CON LAS DBs -->
		    </div>
		</div>
                    <!-- B. Bases de Datos -->
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-bold text-slate-800">Bases de Datos (<?= $envName ?>)</h3>
                            <button type="button" onclick="addDb('<?= $envKey ?>')" class="bg-white border border-slate-300 text-slate-700 px-3 py-1 rounded text-xs font-medium hover:bg-slate-100 shadow-sm">➕ Añadir Base de Datos</button>
                        </div>
                        <div id="dbs-container-<?= $envKey ?>" class="space-y-4">
                            <!-- Filas dinámicas BD -->
                        </div>
                    </div>

                    <!-- C. Respaldos del Ambiente -->
                    <div>
                        <h3 class="text-md font-semibold text-slate-800 border-b border-slate-200 pb-2 mb-4">Respaldos Configurados en <?= $envName ?></h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            
                            <div class="border border-slate-200 p-3 rounded bg-white">
                                <div class="text-xs font-bold text-slate-500 uppercase mb-2">💾 Nivel VM</div>
                                <div class="space-y-2">
                                    <input type="text" name="respaldos[<?= $envKey ?>][pbs_job_ids]" placeholder="PBS Job IDs" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                    <input type="text" name="respaldos[<?= $envKey ?>][pbs_cronograma]" placeholder="Cronograma (ej: 6+1)" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                    <label class="flex items-center text-xs text-slate-700">
                                        <input type="checkbox" name="respaldos[<?= $envKey ?>][pbs_alerta_monitoreo]" value="1" class="mr-2"> Alerta de Monitoreo
                                    </label>
                                </div>
                            </div>
                            
                            <div class="border border-slate-200 p-3 rounded bg-white">
                                <div class="text-xs font-bold text-slate-500 uppercase mb-2">🗄️ Nivel Base de Datos</div>
                                <div class="space-y-2">
                                    <input type="text" name="respaldos[<?= $envKey ?>][bd_backup_nombres]" placeholder="Bases respaldadas" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                    <input type="text" name="respaldos[<?= $envKey ?>][bd_backup_cronograma]" placeholder="Cronograma" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                </div>
                            </div>

                            <div class="border border-slate-200 p-3 rounded bg-white">
                                <div class="text-xs font-bold text-slate-500 uppercase mb-2">📁 Nivel Archivos</div>
                                <div class="space-y-2">
                                    <input type="text" name="respaldos[<?= $envKey ?>][archivos_origen]" placeholder="Carpeta Origen" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                    <input type="text" name="respaldos[<?= $envKey ?>][archivos_destino]" placeholder="Destino (NFS/Server)" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                </div>
                            </div>

                            <div class="border border-slate-200 p-3 rounded bg-white">
                                <div class="text-xs font-bold text-slate-500 uppercase mb-2">⚙️ Nivel Configs</div>
                                <div class="space-y-2">
                                    <input type="text" name="respaldos[<?= $envKey ?>][config_archivos]" placeholder="Archivos a respaldar" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                    <input type="text" name="respaldos[<?= $envKey ?>][config_tipo_backup]" placeholder="Destino / Tipo (Git, NFS)" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            <?php endforeach; ?>

            <!-- Botones de Acción -->
            <div class="mt-8 pt-5 border-t border-slate-200 flex justify-end gap-3">
                <a href="/index.php?page=sistemas" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-md hover:bg-slate-50 transition text-sm font-medium">Cancelar</a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition shadow-sm text-sm font-medium">
                    💾 Guardar Sistema y Arquitectura
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lógica JS para Pestañas y Campos Dinámicos -->
<script>
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

    // Funciones dinámicas
    function addIntegracion() {
        const container = document.getElementById('integraciones-container');
        const i = container.children.length;
        const html = `
            <div class="flex gap-3 items-end bg-slate-50 p-3 rounded border border-slate-200" id="intg-${i}">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nombre del Sistema</label>
                    <input type="text" name="integraciones[${i}][nombre_sistema]" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tipo de Integración</label>
                    <input type="text" name="integraciones[${i}][tipo_integracion]" placeholder="Ej: API REST, BD compartida" class="w-full border border-slate-300 rounded px-2 py-1 text-sm">
                </div>
                <button type="button" onclick="document.getElementById('intg-${i}').remove()" class="bg-red-100 text-red-600 px-3 py-1 mb-[2px] rounded text-sm hover:bg-red-200">X</button>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }

    function addDb(envKey) {
        const container = document.getElementById(`dbs-container-${envKey}`);
        const i = container.children.length;
        const html = `
            <div class="bg-white p-3 rounded border border-slate-200 grid grid-cols-2 md:grid-cols-4 gap-3 relative" id="db-${envKey}-${i}">
                <button type="button" onclick="document.getElementById('db-${envKey}-${i}').remove()" class="absolute top-2 right-2 text-red-500 font-bold bg-red-50 px-2 rounded hover:bg-red-100">X</button>
                <div class="col-span-2 md:col-span-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nombre BD</label>
                    <input type="text" name="dbs[${envKey}][${i}][nombre]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">IP</label>
                    <input type="text" name="dbs[${envKey}][${i}][ip]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Puerto</label>
                    <input type="text" name="dbs[${envKey}][${i}][puerto]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Owner</label>
                    <input type="text" name="dbs[${envKey}][${i}][owner_db]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tipo Servidor</label>
                    <select name="dbs[${envKey}][${i}][tipo_servidor]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs bg-white">
                        <option value="Contenedor">Contenedor</option>
                        <option value="Standalone">Standalone</option>
                        <option value="Cluster">Cluster</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Grupo Lectura</label>
                    <input type="text" name="dbs[${envKey}][${i}][grupo_lectura]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Grupo Escritura</label>
                    <input type="text" name="dbs[${envKey}][${i}][grupo_escritura]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
                </div>
                <div class="col-span-2 md:col-span-4 flex gap-4 mt-2 border-t border-slate-100 pt-2">
                    <label class="flex items-center text-xs text-slate-700">
                        <input type="checkbox" name="dbs[${envKey}][${i}][tiene_passbolt]" value="1" class="mr-1"> Credenciales Passbolt
                    </label>
                    <label class="flex items-center text-xs text-slate-700">
                        <input type="checkbox" name="dbs[${envKey}][${i}][es_historica]" value="1" class="mr-1"> Es Histórica
                    </label>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', html);
    }
function addArtefacto(envKey) {
    const container = document.getElementById(`artefactos-container-${envKey}`);
    const i = container.children.length;
    const html = `
        <div class="bg-white p-3 rounded border border-slate-200 grid grid-cols-1 md:grid-cols-4 gap-3 relative" id="art-${envKey}-${i}">
            <button type="button" onclick="document.getElementById('art-${envKey}-${i}').remove()" class="absolute top-2 right-2 text-red-500 font-bold bg-red-50 px-2 rounded hover:bg-red-100">X</button>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nombre (Ej: API, SIAP)</label>
                <input type="text" name="artefactos[${envKey}][${i}][nombre]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Auth (CAS, JWT, etc)</label>
                <input type="text" name="artefactos[${envKey}][${i}][tipo_auth]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">URL Privada (Interna)</label>
                <input type="text" name="artefactos[${envKey}][${i}][url_privada]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">URL Pública (Internet)</label>
                <input type="text" name="artefactos[${envKey}][${i}][url_publica]" class="w-full border border-slate-300 rounded px-2 py-1 text-xs">
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>
