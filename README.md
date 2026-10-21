<meta property="og:title" content="MI PLUGIN AIRALO API">
<meta property="og:description" content="Panel de gestión de la API de Airalo para WordPress.">
<meta property="og:image" content="https://raw.githubusercontent.com/Hugopvigo/mi_plugin_airalo_api/main/docs/capturas/og.png">

<p align="center">
  <img src="docs/capturas/00-cabecera.png" alt="Mi Plugin Airalo API — panel de gestión de la API de Airalo para WordPress" width="100%">
</p>

<p align="center">
  <b>Panel de gestión de la API de Airalo para WordPress.</b><br>
  Borrador en construcción: dashboard, consulta de eSIMs por ICCID, estados de orden,
  visor de logs y configuración del modo sandbox/producción.
</p>

<p align="center">
  <img alt="licencia" src="https://img.shields.io/badge/licencia-CC%20BY--NC--SA%204.0-8E8E93?style=flat-square&labelColor=1a1a1e">
  <img alt="php" src="https://img.shields.io/badge/PHP-8E8E93?style=flat-square&labelColor=1a1a1e">
  <img alt="wordpress" src="https://img.shields.io/badge/WordPress-8E8E93?style=flat-square&labelColor=1a1a1e">
  <img alt="estado" src="https://img.shields.io/badge/estado-prototipo-3DDC84?style=flat-square&labelColor=1a1a1e">
  <img alt="version" src="https://img.shields.io/badge/versi%C3%B3n-1.0-8E8E93?style=flat-square&labelColor=1a1a1e">
</p>

---

## Qué es esto

Un plugin de WordPress para manejar la **Airalo Partner API** desde el panel de administración,
en lugar de llamar a la API a mano:

- **Dashboard** con el estado de la conexión, el modo activo y accesos rápidos.
- **Consulta de eSIM** por ICCID: estado, país, fechas, datos restantes y paquete contratado.
- **Estados de orden** de Airalo, con buscador por nombre y paginación.
- **Visor de logs** en el admin, con rotación y purga de ficheros antiguos.
- **Página de ajustes** para elegir entre el entorno `sandbox` y `production`.

## Estado del proyecto

**Arranca.** El plugin se carga, registra su menú en el panel y sus páginas
responden. Lo que sigue sin estar hecho:

- **Faltan credenciales de Airalo.** Sin `AIRALO_CLIENT_ID` y
  `AIRALO_CLIENT_SECRET` en el entorno, el dashboard muestra `DESCONECTADO` y
  cualquier llamada a la API devuelve el error
  «Faltan credenciales de Airalo». No se inventan tokens ni se llaman a
  endpoints inventados: sin credenciales, no hay peticiones.
- **El modo `sandbox` no está verificado contra la API real.** El host
  `sandbox-partners-api.airalo.com` viene del diseño original de este plugin;
  el cliente que sí funciona (el plugin hermano `plugin_airalo_wordpress`) solo
  usa `https://partners-api.airalo.com/v2`. Si el host de sandbox no existe,
  hay que cambiar `Airalo_API_Handler::$sandbox_url`.
- **Las respuestas de la API no están validadas contra la documentación de
  Airalo.** Las plantillas pintan los campos con `?? 'N/A'`, así que un cambio
  en el formato de la API se ve como campos en blanco, no como un error.
- **Solo hay dos endpoints implementados** más el de autenticación: estados de
  orden (`/v2/orders/statuses`) y consulta de eSIM (`/v2/sims/{iccid}`). No hay
  pedidos, ni top-ups, ni refunds, ni conciliación.
- **El JavaScript es una mejora progresiva.** `assets/js/admin.js` copia al
  portapapeles, filtra tablas y pide confirmación; no hay ningún endpoint AJAX
  (`admin-ajax.php` todavía no lo usa nadie).
- **No se ha probado con credenciales reales ni contra el API de Airalo.** Lo
  único verificado es que el plugin carga, registra sus páginas, pide el token
  al endpoint correcto y lo usa como `Bearer`.

Lo que sí está hecho:

- `api-airalo.php` carga `includes/security-checks.php` (ya existe) y las siete
  clases de `includes/`, en orden.
- El menú del admin es el real: `Airalo_Admin_Pages` registra Dashboard, eSIMs,
  Estados de Orden y Logs. Ya no quedan las subpáginas de ejemplo
  (`Submit Order`, `Get Order`, `Update eSIM`) ni sus funciones comentadas.
- `Airalo_Auth::request_new_token()` pide el token de verdad a
  `{base}/v2/token` con `client_id`, `client_secret` y
  `grant_type=client_credentials`, y lo cachea hasta su caducidad.
- Las cuatro constantes que faltaban (`AIRALO_API_PLUGIN_DIR`,
  `AIRALO_API_PLUGIN_URL`, `AIRALO_API_VERSION`, `AIRALO_API_LOG_DIR`) están
  definidas, y el directorio de logs se crea solo.
- `templates/admin/logs.php` ya no tiene el error de sintaxis, y existen
  `templates/admin/log-viewer.php`, `templates/admin/main.php`,
  `templates/admin/order-statuses.php`, `templates/admin/order-status-single.php`
  y `assets/js/admin.js`.

El repositorio sigue siendo un prototipo: úsalo con el modo `sandbox` y
credenciales de prueba, no contra producción.

## Requisitos

| Componente | Para qué |
|---|---|
| WordPress | Admin con `manage_options` |
| PHP 7.4+ | El plugin usa `??`, arrays cortos y comprobaciones de versión al arrancar |
| Extensión `json` | Para hablar con la API de Airalo |

No hace falta Composer: este plugin no tiene `composer.json` ni dependencias.

## Instalación

```bash
cd /ruta/al/wp-content/plugins/
git clone https://github.com/Hugopvigo/mi_plugin_airalo_api.git
```

Actívalo desde *Plugins*. Al activarlo se crea el directorio de logs
(`wp-content/uploads/airalo-api-logs/`) y se deja el modo de API en `sandbox`.

## Configuración

**Variables de entorno** (nombres, en `.env` o en el entorno del servidor):

| Variable | Para qué |
|---|---|
| `AIRALO_API_MODE` | `sandbox` o `production` |
| `AIRALO_CLIENT_ID` | Credencial de la API de Airalo |
| `AIRALO_CLIENT_SECRET` | Credencial de la API de Airalo |

`AIRALO_API_MODE` tiene prioridad sobre la opción de WordPress `airalo_api_mode`, que es lo que
guarda la página de ajustes. `.env.example` trae los tres nombres con valores de ejemplo.

Ojo: **nada en este plugin carga el fichero `.env`**. No hay `vlucas/phpdotenv` ni ningún
`require` que lo lea, así que las variables tienen que estar exportadas en el entorno que lanza
PHP (por ejemplo, en el `www-data` del servicio) para que `getenv()` las devuelva. El plugin
busca `AIRALO_CLIENT_ID` y `AIRALO_CLIENT_SECRET` en `getenv()`, `$_ENV` y `$_SERVER`, en ese
orden. Si no las encuentra, no llama a la API: lo dice con un mensaje claro.

## Cómo está hecho

Siete clases en `includes/`, que son el diseño real del plugin:

| Clase | Qué hace |
|---|---|
| `Airalo_API_Handler` | Cliente REST de la API de Airalo: token, caché de respuestas y paginación de órdenes |
| `Airalo_Cache` | Envoltorio sobre transients, con prefijo `airalo_api_` y volcado masivo |
| `Airalo_Logger` | Log en fichero diario, rotación a 5 MB y purga de lo que tenga más de 30 días |
| `Airalo_Settings` | Opción `airalo_api_mode` (sandbox/production) y su página en *Ajustes* |
| `Airalo_Auth` | Token de portador contra `{base}/v2/token`, control de acceso y cabeceras de seguridad |
| `Airalo_Admin_Pages` | Menú del admin: dashboard, eSIMs, estados de orden y logs |
| `Airalo_Utilities` | Utilidades: sanitizar datos, validar ICCID, formatear bytes, nonces |

Encima de ellas, `includes/security-checks.php` define `Airalo_Security_Checks`:
comprueba versión de PHP, funciones de WordPress y constantes del plugin antes
de dejar que el plugin arranque, y centraliza los helpers de capacidades
(`require_capability()`), nonces (`verify_nonce()`) y sanitización de entrada
(`sanitize_input()`), que usan las páginas del admin.

Endpoints que llama hoy el cliente:

| Método | Endpoint | Caché |
|---|---|---|
| `POST` | `/v2/token` | Hasta la caducidad que indica la API (menos 5 minutos de margen) |
| `GET` | `/v2/orders/statuses` | 1 hora |
| `GET` | `/v2/orders/statuses/{slug}` | 1 hora |
| `GET` | `/v2/sims/{iccid}` | 1 hora |

`Airalo_API_Handler::make_request()` admite `GET`, `POST`, `PUT` y `PATCH` sobre cualquier
endpoint, así que el resto está por escribir: pedidos, top-ups, refunds y
conciliación.

El token se pide como en el plugin hermano `plugin_airalo_wordpress`:
`POST {base}/v2/token` con el cuerpo `client_id`, `client_secret` y
`grant_type=client_credentials`; la respuesta trae `data.access_token` y
`data.expires_in`. El token se guarda en un transient (`airalo_api_airalo_api_token`)
con la caducidad que manda la API, menos 5 minutos de margen, y además se
comprueba la fecha de caducidad antes de usarlo.

Las bases de la API son `https://sandbox-partners-api.airalo.com` y
`https://partners-api.airalo.com`, según el modo activo (los endpoints llevan
el prefijo `/v2`).

## Estructura

```
mi_plugin_airalo_api/
├── api-airalo.php              bootstrap: constantes, comprobaciones de seguridad y arranque
├── .env.example                nombres de las variables de entorno
├── assets/
│   ├── css/admin.css            estilos del dashboard
│   └── js/admin.js              mejoras progresivas del panel
├── includes/                   las siete clases del diseño
│   ├── class-admin-pages.php     menú y páginas del admin
│   ├── class-api-handler.php     cliente REST + caché
│   ├── class-auth.php            token de Airalo, control de acceso y cabeceras
│   ├── class-cache.php           caché sobre transients
│   ├── class-logger.php          log en fichero con rotación
│   ├── class-settings.php        opción del modo de API
│   ├── class-utilities.php       utilidades
│   └── security-checks.php       comprobaciones de seguridad y helpers (nonces, capacidades)
└── templates/admin/            dashboard, eSIMs, logs, ajustes y estados de orden
```

## Licencia

El [`LICENSE`](LICENSE) del repositorio es **CC BY-NC-SA 4.0** (Creative Commons
Attribution-NonCommercial-ShareAlike 4.0 International): se puede compartir y adaptar, con
atribución, **sin uso comercial** y manteniendo la misma licencia en las obras derivadas.

Todo está alineado con ese fichero: la cabecera del plugin (`api-airalo.php`) indica
`License: CC BY-NC-SA 4.0`. Este repositorio no usa Composer, así que no hay `composer.json` que
declarar.

© 2025-2026 SUOP Mobile SL — Hugo Pérez-Vigo ([@hugopvigo](https://twitter.com/hugopvigo)).
