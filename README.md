# 🌉 BI Bridge Enterprise v1.0.0 🚀

> **Panel Gerencial de Control, Analítica de Datos Comercial y Verificación de Inventario**

---

## 📌 Descripción General

**BI Bridge** es una solución de inteligencia empresarial (BI) y analítica visual de alto rendimiento, diseñada exclusivamente para abstraer, unificar y procesar la información neurálgica operativa y comercial de la organización.

A diferencia de los paneles rígidos tradicionales, esta plataforma ofrece un ecosistema ágil y desacoplado que unifica el control gerencial. Permite auditar en tiempo real desde la facturación acumulada, el rendimiento individual de la fuerza de ventas y la antigüedad de las cuentas por cobrar, hasta la fluctuación de los márgenes comerciales brutos, la rotación analítica de inventarios y la verificación interactiva de cara al cliente mediante terminales de autogestión (Kioscos).

---

## 🌟 Filosofía Core: Desacoplamiento Absoluto

Toda la lógica de negocio se encuentra orquestada a través de interfaces rigurosas y contratos de software en **Laravel**. Esto asegura que la capa de visualización, auditoría e informes gerenciales permanezca intacta e independiente del driver de base de datos o el proveedor del ERP central (**Profit Plus / SQL Server**), garantizando una escalabilidad ilimitada y un mantenimiento limpio.

---

## 📦 Módulos y Alcance del Sistema

### 📊 1. Módulo de Cuentas por Cobrar (CxC)

- **Analítica de Cartera:** Análisis profundo de saldos vencidos y estructuración de antigüedad de deuda.
- **Conciliación Eficiente:** Conciliación implícita de cobros mensuales contra montos facturados en tiempo real.
- **Proyecciones Financieras:** Modelado matemático y proyección de la cartera corriente para la toma de decisiones.

### 📈 2. Módulo de Márgenes, Ventas e Indicadores

- **Auditoría de Rentabilidad:** Control de rentabilidad bruta por artículo utilizando costos promedio unitarios reales.
- **Semáforos de Alerta:** Monitoreo visual automático mediante alertas por colores para detectar desviaciones en los márgenes mínimos aceptables.
- **Comisiones Dinámicas:** Cálculo automatizado de bonificaciones y rendimiento para la fuerza de ventas.

### 📦 3. Módulo de Inventario Consolidado y Catálogo Digital

- **Trazabilidad de Almacenes:** Auditoría analítica de stock crítico, mínimos, máximos y alertas de reposición.
- **Flujos de Auditoría:** Monitoreo selectivo de entradas físicas y control estricto de salidas no comerciales.
- **Catálogo para Asesores:** Interfaz optimizada con búsqueda inteligente para vendedores en piso de venta.

### 🖥️ 4. Módulo de Autogestión / Kiosco Interactivos (Verificador de Precios)

- **Experiencia de Cliente:** Interfaz limpia, optimizada para pantallas táctiles y tablets de atención al público.
- **Branding Dinámico Inyectado:** Adaptación visual automática de colores de marca, logotipos y RIF del cliente directamente desde variables de entorno CSS.
- **Mapeo de Categorías Visuales:** Implementación de placeholders e íconos dinámicos basados en emojis según la categoría del ERP.

---

## 🛠️ Stack Tecnológico Corporativo

Construido bajo estándares industriales rígidos, herramientas robustas de código abierto y procesamiento nativo empresarial:

- **Backend & Core:** Laravel 10.x | PHP 8.2 (Tipado estricto e Inyección de Dependencias)
- **Bases de Datos & ERP:** Microsoft SQL Server | Profit Plus ERP | MySQL (Caché local/Auditoría)
- **Procesamiento de Datos:** Python 3 (`openpyxl` para pipelines avanzados de reportes)
- **Frontend:** Bootstrap 4/5 (SB Admin 2 Modificado) | jQuery | Axios AJAX | DataTables JS
- **Infraestructura & Seguridad:** Docker Containers | Spatie Permissions (Control de acceso granular por roles)

---

## 📈 Historial de Versiones y Changelog Reciente

### 🚀 v1.0.0 — Producción Estable (Versión Actual)

- **Soporte de Hardware Láser (BarcodeLookupService):** Migración completa del motor de búsqueda de artículos hacia un controlador polimórfico optimizado para el campo `REF` (Códigos de barra nativos del ERP).
- **Arquitectura de Kiosco basada en Estados:** Reescritura total del módulo Kiosco utilizando capas de estados independientes en el DOM, mitigando al 100% los conflictos de renderizado concurrente.
- **Control del Watchdog de Tiempos:** Corrección de bug crítico por colisión de temporizadores asíncronos en la pantalla de _"Artículo no encontrado"_.
- **Mecanismo Anti-Vencimiento de Sesiones (Heartbeat):** Implementación de un ping asíncrono ultra-ligero (`HTTP HEAD`) cada 15 minutos para mantener el token CSRF y la sesión activa de forma indefinida en terminales fijas.
- **Capa de Caché de Alto Rendimiento:** Inclusión de una capa de memoria intermedia de 30 minutos (1800s) para mitigar consultas repetitivas al servidor de base de datos durante lecturas consecutivas.

### 🧪 v0.1.0-beta1 — Inicial

- Concepción inicial de la arquitectura, diseño relacional y modelado de contratos de software.
- Estructuración de scripts automatizados e implementación punta a punta de los módulos de CxC, Ventas e Inventario.

---

## 🧑‍💻 Autoría y Arquitectura

- **Desarrollador Principal:** Ing. Luis Rojas (`Senior Full-Stack & Systems Engineer`)
- **Rol:** Arquitecto de software encargado de la concepción de la arquitectura, diseño relacional, modelado de contratos de software, scripts automatizados e implementación punta a punta de LR-Indicadores / BI Bridge.
- **Soporte & Repositorio:** [GitHub - Luis Rojas](https://github.com/luisrojas69/)

---

_Propiedad Intelectual de Enterprise. Todos los derechos reservados._
