# ⚙️ DGEIP - Sistema de Gestión de Infraestructura y Triage

Plataforma web desarrollada en PHP para la gestión, seguimiento y triage de incidentes y proyectos de la infraestructura tecnológica de la DGEIP. El sistema actúa como un cliente sincronizado con Redmine, permitiendo trabajar de forma local con mayor velocidad y realizar actualizaciones en caliente hacia la API de Redmine.

## 🚀 Características Principales

*   **Arquitectura MVC Limpia:** Separación estricta entre Modelos (Base de datos), Controladores (Lógica de negocio) y Vistas (Presentación HTML/Tailwind).
*   **Sincronización Bidireccional con Redmine:** 
    *   Clonado masivo de tickets a la base de datos local.
    *   Sincronización de tickets individuales.
    *   Actualización en caliente (Hot Sync) desde la app local hacia Redmine.
*   **Sistema de Depuración Integrado (Debug Helper):** Soporta 3 niveles de depuración (0: Apagado, 1: UI HTML no bloqueante, 2: Bloqueante/Exit) para facilitar el desarrollo.
*   **Autenticación Centralizada:** Integración con CAS (Jasig phpCAS) para el inicio de sesión unificado.
*   **Entorno Contenerizado:** Infraestructura lista para desarrollo y producción utilizando Docker y Docker Compose (PHP + PostgreSQL).

## 📁 Estructura del Proyecto

El código fuente (`src/`) está organizado bajo el patrón Controlador Frontal (Front Controller) y MVC:

```text
docker-infraestructura/
├── docker/                 # Archivos de configuración de contenedores (PHP, Postgres)
├── docker-compose.yml      # Orquestación de servicios
├── src/
│   ├── config/             # Configuración de base de datos (db.php) y autenticación (cas.php)
│   ├── controllers/        # Lógica que procesa las peticiones y llama a los modelos
│   ├── helpers/            # Utilidades globales (ej. debug.php)
│   ├── models/             # Clases encargadas de las consultas SQL a PostgreSQL
│   ├── public/             # Document Root. Contiene el enrutador principal (index.php)
│   ├── services/           # Lógica de negocio externa (ej. RedmineService.php)
│   ├── vendor/             # Dependencias de Composer (phpcas, psr, etc.)
│   └── views/              # Archivos de presentación pura (HTML + TailwindCSS)
└── README.md

```

## 🛠️ Requisitos Previos

* [Docker](https://docs.docker.com/get-docker/)
* [Docker Compose](https://docs.docker.com/compose/install/)

<<<<<<< HEAD
## ⚙️ Instalación y Despliegue
=======
### 1. Requisitos Previos

* [Docker](https://www.docker.com/) y [Docker Compose](https://docs.docker.com/compose/) instalados.
* Acceso de red y Token API válido a la instancia de **Redmine SITA**.
* Instancia/Contenedor de **PostgreSQL** con la estructura de tablas (`redmine_tareas`, etc.).

### 2. Configuración de Variables de Entorno

Copia o configura las credenciales de conexión en tu archivo de configuración o entorno (`src/config/database.php` / `RedmineService.php`):

```php
// Ejemplo de configuración de API Redmine
define('REDMINE_URL', 'url)');
define('REDMINE_API_KEY', 'TU_API_KEY_AQUI');

// Ejemplo de configuración PostgreSQL
define('DB_HOST', 'postgres'); // o IP correspondiente
define('DB_NAME', 'tu_base_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_password');

```

### 3. Levantar el Contenedor
>>>>>>> 4819e14772d4ed7cd1dfc6543a521b77b7c1c667

1. **Clonar el repositorio:**
```bash
git clone <url-del-repositorio> docker-infraestructura
cd docker-infraestructura

```


2. **Configurar credenciales:**
* Edita los archivos dentro de `src/config/` (`db.php`, `cas.php`) para incluir las credenciales correctas de la base de datos PostgreSQL y la URL del servidor CAS.
* Asegúrate de configurar tu API Key de Redmine en los controladores/servicios correspondientes.


3. **Levantar el entorno Docker:**
```bash
docker-compose up -d

```


Esto levantará los contenedores definidos (Servidor web con PHP y la base de datos PostgreSQL).
4. **Inicializar la Base de Datos:**
El contenedor de PostgreSQL ejecutará automáticamente los scripts ubicados en `docker/postgres/init.sql` y `schema.sql` durante su primer arranque para construir la estructura de tablas.

## 🧭 Guía de Desarrollo (Patrón MVC)

Si necesitas agregar una nueva funcionalidad o página al sistema, sigue este flujo:

1. **Modelo:** Si necesitas interactuar con la base de datos, crea o edita una clase en `src/models/` (ej. `EquipoModel.php`). **Nunca** escribas sentencias SQL fuera de esta carpeta.
2. **Controlador:** Crea un controlador en `src/controllers/` (ej. `EquipoController.php`). Aquí instancias el modelo, recoges los datos vía `$_GET` o `$_POST` y armas las variables.
3. **Vista:** Crea el archivo visual en `src/views/` (ej. `view_equipos.php`). Solo utiliza PHP para hacer `echo` de las variables o iterar bucles (`foreach`).
4. **Enrutador:** Registra tu nueva ruta en el `switch` del archivo `src/public/index.php`.

## 🐛 Depuración (Debug Mode)

Puedes activar el modo debug global seteando la variable de sesión. Esto capturará excepciones y mostrará volcados de variables de forma estilizada:

* **Nivel 0:** Sin depuración (Producción).
* **Nivel 1:** Muestra cajas rojas con errores o variables en la interfaz, pero permite que la página siga cargando.
* **Nivel 2:** Muestra el error y detiene la ejecución inmediatamente (`exit()`).

Para volcar una variable rápidamente en el código, utiliza la función global:

```php
dd($miVariable, 'Etiqueta opcional');

```

```

```