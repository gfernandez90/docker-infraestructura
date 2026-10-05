# ⚙️ docker-infraestructura - Sistema de Gestión de Infraestructura DGEIP

Plataforma web desarrollada en PHP para la gestión, seguimiento y triage de incidentes y proyectos de la infraestructura tecnológica de la DGEIP. El sistema actúa como un cliente sincronizado con Redmine, permitiendo trabajar de forma local con mayor velocidad y realizar actualizaciones en caliente hacia la API de Redmine.

## ✨ Características Principales

*   **Arquitectura MVC Limpia:** Separación estricta entre Modelos (Base de datos), Controladores (Lógica de negocio) y Vistas (Presentación HTML/Tailwind).
*   **Sincronización Bidireccional con Redmine:**
    *   Clonado masivo de tickets a la base de datos local.
    *   Sincronización de tickets individuales.
    *   Actualización en caliente (Hot Sync) desde la app local hacia Redmine.
*   **Sistema de Depuración Integrado (Debug Helper):** Soporta 3 niveles de depuración (0: Apagado, 1: UI HTML no bloqueante, 2: Bloqueante/Exit) para facilitar el desarrollo.
*   **Autenticación Centralizada:** Integración con CAS (Jasig phpCAS) para el inicio de sesión unificado.
*   **Entorno Contenerizado:** Infraestructura lista para desarrollo y producción utilizando Docker y Docker Compose (PHP + PostgreSQL).
*   **Gestión Detallada de Sistemas:** Permite registrar y administrar sistemas, incluyendo su infraestructura en diferentes ambientes (desarrollo, producción, etc.), bases de datos, artefactos, respaldos y dependencias.
*   **Mapeo Visual de Integraciones:** Herramienta para visualizar las interconexiones entre sistemas y componentes.
*   **Mesa de Triage:** Interfaz dedicada para clasificar, asignar y estimar tickets entrantes de Redmine.

## 🚀 Tecnologías Utilizadas

*   **Lenguajes:** PHP, YAML, SQL, Markdown
*   **Base de Datos:** PostgreSQL 17
*   **Frameworks/Librerías:**
    *   [PHP](https://www.php.net/) (versión 8.3+)
    *   [Composer](https://getcomposer.org/) para gestión de dependencias
    *   [phpCAS](https://github.com/Jasig/phpCAS) para autenticación CAS
    *   [Tailwind CSS](https://tailwindcss.com/) para estilos (integrado en las vistas)
    *   [Docker](https://www.docker.com/) y [Docker Compose](https://docs.docker.com/compose/) para orquestación
*   **Servicios Integrados:** [Redmine API](https://www.redmine.org/projects/redmine/wiki/Rest_Api)

## 📁 Estructura del Proyecto

El código fuente (`src/`) está organizado bajo el patrón Controlador Frontal (Front Controller) y MVC:

```text
docker-infraestructura/
├── docker/                 # Archivos de configuración de contenedores (PHP, Postgres)
│   └── php/                # Dockerfile para la imagen PHP
│   └── postgres/           # Scripts SQL para inicialización de DB
├── docker-compose.yml      # Orquestación de servicios Docker
├── src/
│   ├── config/             # Configuración de base de datos (db.php) y autenticación (cas.php)
│   ├── controllers/        # Lógica que procesa las peticiones y llama a los modelos
│   ├── helpers/            # Utilidades globales (ej. debug.php)
│   ├── models/             # Clases encargadas de las consultas SQL a PostgreSQL
│   ├── public/             # Document Root. Contiene el enrutador principal (index.php)
│   ├── services/           # Lógica de negocio externa (ej. RedmineService.php)
│   ├── vendor/             # Dependencias de Composer (phpcas, psr, etc.)
│   └── views/              # Archivos de presentación pura (HTML + TailwindCSS)
└── README.md               # Este archivo de documentación
```

## 🚀 Requisitos Previos

*   [Docker](https://docs.docker.com/get-docker/) instalado y en ejecución.
*   [Docker Compose](https://docs.docker.com/compose/install/) instalado.

## 🚀 Instalación y Despliegue

1.  **Clonar el repositorio:**
    ```bash
    git clone https://github.com/gfernandez90/docker-infraestructura.git
    cd docker-infraestructura
    ```

2.  **Configurar Variables de Entorno (Opcional pero recomendado):**
    Edita el archivo `docker-compose.yml` o configura variables de entorno para la conexión a la base de datos (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`), la configuración de CAS (`CAS_HOSTNAME`, `CAS_PORT`, `CAS_URI`) y la conexión a Redmine (`REDMINE_URL`, `REDMINE_API_KEY`). Por defecto, utiliza valores de ejemplo extraídos del código.

3.  **Levantar el entorno Docker:**
    ```bash
    docker-compose up -d
    ```
    Esto descargará las imágenes necesarias (PHP, PostgreSQL) y levantará los contenedores.

4.  **Acceder a la Aplicación:**
    Abre tu navegador y visita `http://localhost:62080` (o el puerto configurado en `docker-compose.yml`). Deberías ser redirigido al inicio de sesión CAS.

5.  **Inicializar la Base de Datos:**
    La primera vez que el contenedor de PostgreSQL se levanta, ejecutará automáticamente los scripts en `docker/postgres/init.sql` y `docker/postgres/schema.sql` para crear las tablas necesarias.

## 💡 Cómo Usar el Sistema

El sistema sigue un patrón MVC y un enrutador frontal ubicado en `src/public/index.php`.

### 1. Autenticación

*   El sistema utiliza CAS para la autenticación centralizada. Al acceder, serás redirigido al portal CAS para iniciar sesión.
*   Una vez autenticado, el sistema verifica tu usuario en la base de datos local (`redmine_tareas`, `usuarios`) y te asigna un rol (por defecto 'admin' si no existe).

### 2. Navegación Principal

La navegación se realiza a través de la barra lateral (`src/views/layouts/sidebar.php`):

*   **Dashboard:** Vista general de bienvenida.
*   **Configuraciones:** Permite modificar parámetros globales del sistema (URLs, etc.).
*   **Sistemas e Infra:**
    *   **Listado de Sistemas:** Muestra todos los sistemas registrados con su estado, ambientes, IPs, etc.
    *   **Nuevo Sistema:** Formulario para registrar un nuevo sistema.
    *   **Mapa de Integraciones:** Visualiza las interconexiones entre sistemas (nivel sistema) y entre artefactos (nivel componente).
    *   **Personal:** Gestión de los actores (usuarios) y sus roles/cargos.
*   **Gestión Redmine:** Interfaz para interactuar con la API de Redmine.
    *   **Mesa de Entrada:** Triage de tickets entrantes.
    *   **Leer Incidente / Ver Proyecto:** Visualización detallada de tickets y proyectos de Redmine.
    *   **Crear Incidente / Crear Proyecto:** Formularios para generar nuevos elementos en Redmine.
*   **Gestión Respaldos:** Inventario de respaldos configurados por sistema y ambiente.
*   **Sincronizador:** Herramienta para importar datos desde la API de Redmine a la base de datos local.
*   **Base de Datos:** Acceso a una consola SQL para ejecutar consultas directamente contra la base de datos PostgreSQL.

### 3. Flujo de Trabajo Típico (Ejemplo: Triage)

1.  Accede al sistema y autentícate vía CAS.
2.  Dirígete a la **Mesa de Entrada** (`/index.php?page=inbox`).
3.  Verás una lista de tickets pendientes de clasificación.
4.  Para cada ticket, selecciona la **Categoría** (Incidente, Proyecto, etc.), el **Asignado A**, las **Horas Estimadas** y el **Estado** deseado.
5.  Haz clic en **'Aprobar y Clasificar'** para guardar los cambios localmente y sincronizar con Redmine, o en **'Rechazar Ticket'** para marcarlo como cerrado.

### 4. Estructura de Controladores y Vistas

Las peticiones web son manejadas por `src/public/index.php` que enruta según el parámetro `page`:

*   **Controladores:** Ubicados en `src/controllers/`. Procesan la lógica de negocio y preparan los datos.
*   **Modelos:** Ubicados en `src/models/`. Se encargan de la interacción con la base de datos.
*   **Vistas:** Ubicados en `src/views/`. Responsables de la presentación de datos (HTML/Tailwind).

## 🚀 Dependencias

*   **PHP >= 8.3**
*   **Composer** para instalar dependencias:
    *   `jasig/phpcas` (^1.6)
*   **Docker y Docker Compose** para el entorno de desarrollo y producción.

## 🛠️ Instalación de Dependencias (con Composer)

Dentro del directorio raíz del proyecto (`docker-infraestructura/`), ejecuta:

```bash
composer install
```

Esto instalará las dependencias PHP listadas en `src/composer.json`.

## 🐞 Depuración

El sistema incluye un helper de depuración (`src/helpers/debug.php`) que permite:

*   **Activar niveles de depuración:** Modificando `$_SESSION['debug']` (0: Off, 1: UI, 2: Exit).
*   **Volcar variables:** Usando la función `dd($variable, 'etiqueta')`.

## 🤝 Contribución

Las contribuciones son bienvenidas. Por favor, sigue las guías de estilo y estructura del proyecto. Se recomienda abrir un *Issue* antes de enviar un *Pull Request* para discutir los cambios.

## 📄 Licencia

Este proyecto no especifica una licencia. Por defecto, el código fuente está protegido por derechos de autor.

## 🔗 Enlaces Importantes

*   **Repositorio:** [docker-infraestructura](https://github.com/gfernandez90/docker-infraestructura)
*   **Autor:** [gfernandez90](https://github.com/gfernandez90)

---

<sub>© 2024 DGEIP. Creado y mantenido por gfernandez90.</sub>


---
**<p align="center">Generated by [ReadmeCodeGen](https://www.readmecodegen.com/)</p>**
