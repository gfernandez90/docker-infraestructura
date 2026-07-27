# 🚀 Docker Infraestructura - Gestión & Sincronización SITA (Redmine)

Este proyecto provee un entorno containerizado en **PHP / Nginx** diseñado para la gestión, automatización y visualización de proyectos e incidentes del área de Infraestructura. Se integra directamente con la API de **Redmine SITA** (ANEP) y una base de datos **PostgreSQL** local para la sincronización de datos y ejecución de flujos de trabajo internos.

---

## 🛠️ Tecnologías & Componentes

- **Servidor Web / Runtime**: PHP 8.x + Nginx (Dockerized)
- **Base de Datos**: PostgreSQL
- **Integración Externa**: API REST de Redmine SITA
- **Librerías Frontend**: Bootstrap 5, FontAwesome, jQuery, Chart.js / DataTables (para dashboards y reportes)

---

## 📋 Funcionalidades Principales

1. **Sincronización Automática (`sync_redmine.php`)**:
   - Descarga e ingresa a PostgreSQL proyectos, peticiones, estados, prioridades, seguidores y categorías desde Redmine.
   - Preserva relaciones de tareas padre/hijo y metadatos clave.

2. **Gestión y Creación de Proyectos (`crear_proyecto.php`)**:
   - Generación estructurada de tareas padre de tipo *Proyecto* (Categoría `108`).
   - Mapeo automático de asignaciones (ej. *DGEIP Infraestructura* - ID `7`), PM Responsable (Campo Personalizado `71`) y subtareas hijo con estimación de horas e integrantes.

3. **Visualización y Triage (`ver_incidente.php` / `ver_proyecto.php`)**:
   - Vistas detalladas combinando datos locales persistidos en PostgreSQL con consultas en vivo a la API REST de Redmine.

4. **Automatización de Mantenimiento (`cron_close_resolved.php`)**:
   - Scripts de fondo para actualización masiva y cierre de tareas resueltas.

---

## ⚙️ Estructura del Proyecto

```text
.
├── docker/                 # Archivos de configuración de Docker (Dockerfile, Nginx, PHP-FPM)
├── docker-compose.yml      # Definición de servicios para despliegue local/producción
├── src/
│   ├── config/             # Conexiones a BD y configuraciones generales (`database.php`)
│   ├── services/           # Clases de servicio (`RedmineService.php`)
│   ├── views/              # Vistas PHP (Crear proyectos, visualizadores, dashboards)
│   └── scripts/            # Scripts de sincronización e integración cron (`sync_redmine.php`)
└── README.md

```

---

## 🚀 Despliegue Rápido

### 1. Requisitos Previos

* [Docker](https://www.docker.com/) y [Docker Compose](https://docs.docker.com/compose/) instalados.
* Acceso de red y Token API válido a la instancia de **Redmine SITA**.
* Instancia/Contenedor de **PostgreSQL** con la estructura de tablas (`redmine_tareas`, etc.).

### 2. Configuración de Variables de Entorno

Copia o configura las credenciales de conexión en tu archivo de configuración o entorno (`src/config/database.php` / `RedmineService.php`):

```php
// Ejemplo de configuración de API Redmine
define('REDMINE_URL', '[https://sita.anep.edu.uy](https://sita.anep.edu.uy)');
define('REDMINE_API_KEY', 'TU_API_KEY_AQUI');

// Ejemplo de configuración PostgreSQL
define('DB_HOST', 'postgres'); // o IP correspondiente
define('DB_NAME', 'tu_base_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');

```

### 3. Levantar el Contenedor

```bash
# Clonar el repositorio
git clone [https://github.com/gfernandez90/docker-infraestructura.git](https://github.com/gfernandez90/docker-infraestructura.git)
cd docker-infraestructura

# Construir y levantar los servicios
docker-compose up -d --build

```

El servicio estará disponible en el puerto expuesto en el `docker-compose.yml` (ej. `http://localhost:8080`).

---

## 🔄 Ejecución de Sincronización

Para ejecutar manualmente la sincronización de datos con Redmine:

```bash
docker exec -it <nombre_contenedor_app> php /var/www/html/src/scripts/sync_redmine.php

```
