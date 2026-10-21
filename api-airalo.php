<?php
/*
Plugin Name: Mi Panel de Administración de AIRALO
Description: Plugin para gestionar la API de Airalo
Version: 1.0
Author: <a href="https://www.suop.es/" target="_blank">SUOP</a> - Hugo Perez-Vigo
*/

defined('ABSPATH') or die('Acceso directo no permitido.');

// -----------------------------------------------------------------------------
// Constantes del plugin
// -----------------------------------------------------------------------------

// Ruta del plugin en el disco, con barra final.
if (!defined('AIRALO_API_PLUGIN_DIR')) {
    define('AIRALO_API_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// URL pública de los recursos del plugin, con barra final.
if (!defined('AIRALO_API_PLUGIN_URL')) {
    define('AIRALO_API_PLUGIN_URL', plugin_dir_url(__FILE__));
}

// Versión del plugin. Tiene que coincidir con la cabecera de este fichero:
// la usan las plantillas para cachear los assets del admin.
if (!defined('AIRALO_API_VERSION')) {
    define('AIRALO_API_VERSION', '1.0');
}

// Directorio de logs del plugin, con barra final.
if (!defined('AIRALO_API_LOG_DIR')) {
    $airalo_api_uploads = defined('WP_CONTENT_DIR')
        ? WP_CONTENT_DIR . '/uploads'
        : ABSPATH . 'wp-content/uploads';

    define('AIRALO_API_LOG_DIR', rtrim($airalo_api_uploads, '/\\') . '/airalo-api-logs/');
}

// Clave secreta del plugin. No la usa nadie todavía, pero se deja definida
// para que ningún otro código tenga que generarla en caliente.
if (!defined('AIRALO_API_SECURE_KEY')) {
    define('AIRALO_API_SECURE_KEY', function_exists('wp_generate_password')
        ? wp_generate_password(64, true, true)
        : bin2hex(random_bytes(32)));
}

// -----------------------------------------------------------------------------
// Directorio de logs
// -----------------------------------------------------------------------------

if (!is_dir(AIRALO_API_LOG_DIR)) {
    if (function_exists('wp_mkdir_p')) {
        wp_mkdir_p(AIRALO_API_LOG_DIR);
    } else {
        @mkdir(AIRALO_API_LOG_DIR, 0755, true); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
    }
}

// -----------------------------------------------------------------------------
// Comprobaciones de seguridad
// -----------------------------------------------------------------------------

// Se carga antes que las clases: decide si el plugin puede seguir cargándose.
require_once AIRALO_API_PLUGIN_DIR . 'includes/security-checks.php';

if (!Airalo_Security_Checks::pass_all_checks()) {
    add_action('admin_notices', 'airalo_api_security_notice');

    return;
}

// -----------------------------------------------------------------------------
// Clases del plugin
// -----------------------------------------------------------------------------
//
// El orden importa: el logger no depende de nada, la caché usa el logger, el
// cliente de la API usa la caché y el logger, y las páginas del admin usan el
// cliente.

require_once AIRALO_API_PLUGIN_DIR . 'includes/class-logger.php';
require_once AIRALO_API_PLUGIN_DIR . 'includes/class-cache.php';
require_once AIRALO_API_PLUGIN_DIR . 'includes/class-auth.php';
require_once AIRALO_API_PLUGIN_DIR . 'includes/class-utilities.php';
require_once AIRALO_API_PLUGIN_DIR . 'includes/class-settings.php';
require_once AIRALO_API_PLUGIN_DIR . 'includes/class-api-handler.php';
require_once AIRALO_API_PLUGIN_DIR . 'includes/class-admin-pages.php';

// -----------------------------------------------------------------------------
// Arranque
// -----------------------------------------------------------------------------

$airalo_api_logger   = Airalo_Logger::get_instance();
$airalo_api_cache    = Airalo_Cache::get_instance($airalo_api_logger);
$airalo_api_handler  = Airalo_API_Handler::get_instance($airalo_api_cache, $airalo_api_logger);
$airalo_api_settings = Airalo_Settings::get_instance($airalo_api_logger);
$airalo_api_auth     = Airalo_Auth::get_instance();
$airalo_api_pages    = Airalo_Admin_Pages::get_instance($airalo_api_handler, $airalo_api_logger);

// -----------------------------------------------------------------------------
// Activación
// -----------------------------------------------------------------------------

register_activation_hook(__FILE__, 'airalo_api_activate');

/**
 * Al activar el plugin: deja el modo de API por defecto y prepara el
 * directorio de logs.
 *
 * @return void
 */
function airalo_api_activate() {
    if (!is_dir(AIRALO_API_LOG_DIR)) {
        if (function_exists('wp_mkdir_p')) {
            wp_mkdir_p(AIRALO_API_LOG_DIR);
        } else {
            @mkdir(AIRALO_API_LOG_DIR, 0755, true); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }
    }

    Airalo_Settings::get_instance()->activate();
}
