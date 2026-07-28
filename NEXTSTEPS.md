## 📋 Lista de Pendientes

### 1. Módulo de Triage (Triaje / Recepción)

* **Objetivo**: Detectar, clasificar y validar rápidamente el flujo de entrada de tickets.
* **Criterio de Pendientes**: Ticket con estado *"Nuevo"*, *"Sin Categoría"*, *"Sin Asignar"* o *"Sin Tiempo Estimado"*.
* **Ubicación propuesta**:
* **Badge/Alerta flotante** en la barra superior o en el header de `gestion_inbox.php` (ej: `⚠️ 5 sin Triage`).
* **Vista/Filtro rápido en el Inbox** o un modal deslizante/pantalla dedicada `triage.php` estilo "tinder/workflow rápido" para procesar tickets uno a uno sin perder tiempo.


* **Flujo de Acción**:
* ❌ *Rechazar* $\rightarrow$ Cambia estado a "Rechazado".
* 🟢 *Aprobar/Clasificar* $\rightarrow$ Cambia estado a "Pendiente", asigna Categoría, asigna Funcionario y define posición inicial en Cola.



---

### 2. Gestión de Colas y Carga por Funcionario (Priorización & Visibilidad)

* **Objetivo**: Garantizar el flujo de trabajo FIFO con flexibilidad para reordenar por urgencias, y dar visibilidad de la carga de trabajo del equipo.
* **Jerarquía predeterminada por tipo**:
1. Incidentes
2. Consulta Rápida
3. Tarea Rápida
4. Proyecto


* **Reglas de Ejecución**:
* Cada funcionario maneja su cola FIFO.
* Se puede reordenar manualmente (*Drag & Drop* o inputs de posición/prioridad).
* Estado **"En progreso"**: Identifica el ticket/tarea única en la que el funcionario está trabajando en ese momento exacto.


* **Panel de Funcionarios (Dashboard de Infra)**:
* Vista consolidada por Funcionario.
* Desglose por Categoría (cantidad de tickets activos).
* Indicador destacado del **ticket actual "En progreso"**.


* **Ubicación propuesta**:
* Vista dedicada `colas.php` / `panel_equipo.php` en el menú principal.



---

### 3. Agenda & Calendario de Proyectos (Planificación)

* **Objetivo**: Visión temporal e hito de proyectos/tareas en formato de calendario de trabajo.
* **Regla de Negocio Temporal**: **$1 \text{ día hábil} = 6 \text{ horas de trabajo}$**.
* **Cálculo de fechas**:
* $\text{Fecha Fin} = \text{Fecha Inicio} + \left( \frac{\text{Tiempo Estimado (hs)}}{6} \right) \text{ días hábiles}$.


* **Ubicación propuesta**:
* Vista `agenda.php` incorporando una librería liviana como **FullCalendar** o un diagrama de Gantt simple.
