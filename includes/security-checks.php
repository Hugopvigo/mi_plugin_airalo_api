<?php
/**
 * Comprobaciones de seguridad del plugin.
 *
 * Este fichero se carga el primero, antes que las clases del plugin, porque
 * `api-airalo.php` usa `Airalo_Security_Checks::pass_all_checks()` para decidir
 * si el plugin puede seguir cargándose.
 *
 * Qué comprueba:
 *
 *  - Versión de PHP: el plugin usa `??`, arrays cortos y `::class`, así que
 *    necesita PHP 7.4 o superior. Es una comprobación bloqueante: con una
 *    versión antigua el plugin no debe arrancar.
 *  - Funciones de WordPress imprescindibles (`add_action`, `wp_remote_request`,
 *    `get_option`, `sanitize_text_field`...). Si faltan, el plugin no puede
 *    funcionar: comprobación bloqueante.
 *  - Constantes propias del plugin (`AIRALO_API_PLUGIN_DIR`, `AIRALO_API_PLUGIN_URL`,
 *    `AIRALO_API_VERSION`, `AIRALO_API_LOG_DIR`): bloqueante, porque el resto de
 *    las clases las usan al construirse.
 *  - HTTPS en el panel: aviso, no bloqueante (un panel de desarrollo en HTTP
 *    debe seguir funcionando).
 *  - Directorio de logs escribible: aviso, no bloqueante. El logger degrada
 *    solo si el directorio no existe.
 *
 * Además expone los helpers de seguridad que usa el resto del plugin
 * (capacidades, nonces y sanitización de entrada), para que el control de
 * acceso esté en un único sitio.
 *
 * @package Airalo_API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Airalo_Security_Checks {

    /** Versión mínima de PHP soportada por el plugin. */
    const MIN_PHP_VERSION = '7.4';

    /** Capacidad exigida para ver las páginas del plugin. */
    const REQUIRED_CAPABILITY = 'manage_options';

    /** Funciones de WordPress sin las cuales el plugin no puede funcionar. */
    const REQUIRED_WP_FUNCTIONS = array(
        'add_action',
        'add_filter',
        'add_menu_page',
        'add_submenu_page',
        'get_option',
        'get_transient',
        'set_transient',
        'wp_remote_request',
        'wp_remote_retrieve_body',
        'wp_remote_retrieve_response_code',
        'is_wp_error',
        'sanitize_text_field',
        'wp_create_nonce',
        'wp_verify_nonce',
    );

    /** Constantes que el resto del plugin da por hechas. */
    const REQUIRED_CONSTANTS = array(
        'AIRALO_API_PLUGIN_DIR',
        'AIRALO_API_PLUGIN_URL',
        'AIRALO_API_VERSION',
        'AIRALO_API_LOG_DIR',
    );

    /**
     * Devuelve todas las comprobaciones con su resultado.
     *
     * @return array<string,array{passed:bool,severity:string,message:string}>
     */
    public static function get_checks() {
        $checks = array(
            'php_version'   => self::check_php_version(),
            'wp_functions'  => self::check_wp_functions(),
            'constants'     => self::check_constants(),
            'https'         => self::check_https(),
            'log_dir'       => self::check_log_dir(),
        );

        return $checks;
    }

    /**
     * ¿Se puede cargar el plugin?
     *
     * Solo las comprobaciones bloqueantes (severity `blocking`) impiden arrancar.
     *
     * @return bool
     */
    public static function pass_all_checks() {
        foreach (self::get_checks() as $check) {
            if ('blocking' === $check['severity'] && !$check['passed']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Comprobaciones bloqueantes que han fallado.
     *
     * @return array<int,string> Mensajes legibles.
     */
    public static function get_failed_checks() {
        $failed = array();

        foreach (self::get_checks() as $name => $check) {
            if ('blocking' === $check['severity'] && !$check['passed']) {
                $failed[] = $check['message'];
            }
        }

        return $failed;
    }

    /**
     * Comprobaciones no bloqueantes que han fallado (avisos).
     *
     * @return array<int,string>
     */
    public static function get_warnings() {
        $warnings = array();

        foreach (self::get_checks() as $name => $check) {
            if ('warning' === $check['severity'] && !$check['passed']) {
                $warnings[] = $check['message'];
            }
        }

        return $warnings;
    }

    // -------------------------------------------------------------------------
    // Comprobaciones
    // -------------------------------------------------------------------------

    private static function check_php_version() {
        $passed = version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=');

        return array(
            'passed'  => $passed,
            'severity' => 'blocking',
            'message' => $passed
                ? ''
                : sprintf(
                    'Airalo API necesita PHP %s o superior (esta máquina usa PHP %s).',
                    self::MIN_PHP_VERSION,
                    PHP_VERSION
                ),
        );
    }

    private static function check_wp_functions() {
        $missing = array();

        foreach (self::REQUIRED_WP_FUNCTIONS as $function) {
            if (!function_exists($function)) {
                $missing[] = $function;
            }
        }

        $passed = empty($missing);

        return array(
            'passed'  => $passed,
            'severity' => 'blocking',
            'message' => $passed
                ? ''
                : sprintf(
                    'Faltan funciones de WordPress necesarias para el plugin: %s.',
                    implode(', ', $missing)
                ),
        );
    }

    private static function check_constants() {
        $missing = array();

        foreach (self::REQUIRED_CONSTANTS as $constant) {
            if (!defined($constant)) {
                $missing[] = $constant;
            }
        }

        $passed = empty($missing);

        return array(
            'passed'  => $passed,
            'severity' => 'blocking',
            'message' => $passed
                ? ''
                : sprintf(
                    'Faltan las constantes del plugin: %s.',
                    implode(', ', $missing)
                ),
        );
    }

    private static function check_https() {
        $is_https = function_exists('is_ssl') ? is_ssl() : true;

        return array(
            'passed'  => $is_https,
            'severity' => 'warning',
            'message' => $is_https
                ? ''
                : 'El panel se está usando sin HTTPS: las credenciales viajan sin cifrar.',
        );
    }

    private static function check_log_dir() {
        $dir = defined('AIRALO_API_LOG_DIR') ? AIRALO_API_LOG_DIR : '';

        if ('' === $dir) {
            return array(
                'passed'  => false,
                'severity' => 'warning',
                'message' => 'No se ha podido determinar el directorio de logs.',
            );
        }

        $writable = is_dir($dir) ? is_writable($dir) : is_writable(dirname(rtrim($dir, '/\\')));

        return array(
            'passed'  => $writable,
            'severity' => 'warning',
            'message' => $writable
                ? ''
                : sprintf('El directorio de logs %s no es escribible.', $dir),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers de seguridad usados por el resto del plugin
    // -------------------------------------------------------------------------

    /**
     * Comprueba la capacidad del usuario actual.
     *
     * @param string $capability Capacidad exigida.
     * @return bool
     */
    public static function current_user_can($capability = self::REQUIRED_CAPABILITY) {
        if (!function_exists('current_user_can')) {
            return false;
        }

        return (bool) current_user_can($capability);
    }

    /**
     * Corta la ejecución si el usuario no tiene la capacidad exigida.
     *
     * @param string $capability Capacidad exigida.
     * @return void
     */
    public static function require_capability($capability = self::REQUIRED_CAPABILITY) {
        if (self::current_user_can($capability)) {
            return;
        }

        if (function_exists('wp_die')) {
            wp_die(
                __('No tienes permisos para acceder a esta página.', 'airalo-api'),
                403,
                array('response' => 403, 'back_link' => true)
            );
        }

        // Sin WordPress no hay a dónde caer: se corta igualmente.
        throw new RuntimeException('Airalo API: falta la capacidad ' . $capability);
    }

    /**
     * Verifica un nonce.
     *
     * @param string|int|null $nonce  Nonce recibido.
     * @param string          $action Acción del nonce.
     * @return bool
     */
    public static function verify_nonce($nonce, $action) {
        if (empty($nonce) || !is_string($nonce)) {
            return false;
        }

        if (!function_exists('wp_verify_nonce')) {
            return false;
        }

        return (bool) wp_verify_nonce($nonce, $action);
    }

    /**
     * Sanitiza un valor según su tipo.
     *
     * @param mixed  $value Valor a sanitizar.
     * @param string $type  text|key|title|file|int|email|raw
     * @return mixed
     */
    public static function sanitize_input($value, $type = 'text') {
        if (is_array($value)) {
            $clean = array();
            foreach ($value as $key => $item) {
                $clean[$key] = self::sanitize_input($item, $type);
            }
            return $clean;
        }

        if (!is_scalar($value)) {
            return '';
        }

        switch ($type) {
            case 'key':
                return function_exists('sanitize_key') ? sanitize_key($value) : preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
            case 'title':
                return function_exists('sanitize_title') ? sanitize_title($value) : preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value));
            case 'file':
                return function_exists('sanitize_file_name') ? sanitize_file_name($value) : basename((string) $value);
            case 'int':
                return (int) $value;
            case 'email':
                return function_exists('sanitize_email') ? sanitize_email($value) : filter_var((string) $value, FILTER_SANITIZE_EMAIL);
            case 'raw':
                return $value;
            case 'text':
            default:
                return function_exists('sanitize_text_field') ? sanitize_text_field($value) : trim(strip_tags((string) $value));
        }
    }

    /**
     * Lee un parámetro de la petición y lo sanitiza.
     *
     * @param string $key   Nombre del parámetro.
     * @param string $type  Tipo de sanitización.
     * @param string $source `get`, `post` o `request`.
     * @return mixed Valor sanitizado o cadena vacía.
     */
    public static function get_request_value($key, $type = 'text', $source = 'request') {
        switch ($source) {
            case 'get':
                $data = $_GET;
                break;
            case 'post':
                $data = $_POST;
                break;
            default:
                $data = $_REQUEST;
                break;
        }

        if (!isset($data[$key])) {
            return '';
        }

        return self::sanitize_input($data[$key], $type);
    }
}

/**
 * Aviso del admin cuando el plugin no puede funcionar con normalidad.
 *
 * La función se registra en `api-airalo.php` antes de abortar la carga.
 *
 * @return void
 */
function airalo_api_security_notice() {
    $failed  = Airalo_Security_Checks::get_failed_checks();
    $warnings = Airalo_Security_Checks::get_warnings();

    if (empty($failed) && empty($warnings)) {
        return;
    }

    echo '<div class="notice notice-error"><p><strong>Airalo API:</strong> el plugin no ha podido cargarse.</p><ul>';

    foreach ($failed as $message) {
        echo '<li>' . esc_html($message) . '</li>';
    }

    echo '</ul></div>';

    if (!empty($warnings)) {
        echo '<div class="notice notice-warning"><p><strong>Airalo API:</strong> avisos de seguridad.</p><ul>';

        foreach ($warnings as $message) {
            echo '<li>' . esc_html($message) . '</li>';
        }

        echo '</ul></div>';
    }
}
