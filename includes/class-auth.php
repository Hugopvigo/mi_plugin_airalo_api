<?php
class Airalo_Auth {
    /**
     * Endpoint de autenticación de Airalo (API v2).
     *
     * Se pide un token de portador con las credenciales de partner:
     * POST {base}/v2/token  con client_id, client_secret y grant_type.
     */
    const TOKEN_ENDPOINT = '/v2/token';

    /** Caducidad por defecto del token si la API no la indica (1 hora). */
    const DEFAULT_TOKEN_TTL = 3600;

    /** Margen de seguridad (segundos) que se resta a la caducidad indicada. */
    const TOKEN_SAFETY_MARGIN = 300;

    private $protected_pages = [
        'airalo-api',
        'airalo-api-order-statuses',
        'airalo-api-esims',
        'airalo-api-logs',
        'airalo-api-settings'
    ];

    /**
     * Instancia compartida (control de acceso y cabeceras).
     *
     * @return Airalo_Auth
     */
    public static function get_instance() {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }

    public function __construct() {
        add_action('admin_init', array($this, 'check_admin_access'));
        add_filter('wp_headers', array($this, 'security_headers'));
    }

    /**
     * Verifica el acceso a las páginas administrativas del plugin
     */
    public function check_admin_access() {
        // Permitir solicitudes AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        // Verificar si es una página protegida del plugin
        $is_airalo_page = in_array($current_page, $this->protected_pages);
        
        // Verificar usando ambos métodos para mayor seguridad
        $is_plugin_page = $is_airalo_page || (strpos($current_page, 'airalo-api') === 0);
        
        if ($is_plugin_page && !current_user_can('manage_options')) {
            $this->log_access_attempt($current_page);
            wp_die(
                __('No tienes permisos para acceder a esta página.', 'airalo-api'),
                403,
                array('response' => 403, 'back_link' => true)
            );
        }
    }

    /**
     * Agrega headers de seguridad
     */
    public function security_headers($headers) {
        if (is_admin()) {
            $headers = array_merge($headers, [
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin'
            ]);
        }
        return $headers;
    }

    /**
     * Registra intentos de acceso no autorizados
     */
    private function log_access_attempt($page) {
        $user = wp_get_current_user();
        $log_message = sprintf(
            'Intento de acceso no autorizado a %s por el usuario %s (ID: %d)',
            $page,
            $user->user_login,
            $user->ID
        );
        
        if (function_exists('wp_get_current_connection')) {
            $connection = wp_get_current_connection();
            $log_message .= sprintf(
                ' desde %s (IP: %s)',
                $connection['hostname'] ?? 'desconocido',
                $connection['ip'] ?? 'desconocida'
            );
        }
        
        error_log('[Airalo API Security] ' . $log_message);
    }

    /**
     * Método para agregar páginas protegidas dinámicamente
     */
    public function add_protected_page($page_slug) {
        if (!in_array($page_slug, $this->protected_pages)) {
            $this->protected_pages[] = sanitize_key($page_slug);
        }
    }

    // -------------------------------------------------------------------------
    // Autenticación contra la API de Airalo
    // -------------------------------------------------------------------------

    /**
     * Credenciales de partner de Airalo.
     *
     * Se leen del entorno del proceso (variables exportadas, `$_ENV` o
     * `$_SERVER`). El plugin no carga ningún fichero `.env`: las variables
     * tienen que estar exportadas en el entorno que lanza PHP.
     *
     * @return array{client_id:string,client_secret:string}
     */
    public static function get_credentials() {
        return array(
            'client_id'     => self::read_env('AIRALO_CLIENT_ID'),
            'client_secret' => self::read_env('AIRALO_CLIENT_SECRET'),
        );
    }

    /**
     * ¿Hay credenciales configuradas?
     *
     * @return bool
     */
    public static function is_configured() {
        $credentials = self::get_credentials();

        return '' !== $credentials['client_id'] && '' !== $credentials['client_secret'];
    }

    /**
     * Pide un token de portador a Airalo.
     *
     * Endpoint: POST {base}/v2/token con client_id, client_secret y
     * grant_type=client_credentials. Respuesta: data.access_token y
     * data.expires_in.
     *
     * @param string                $base_url      Base de la API (sin `/v2`).
     * @param string|null           $client_id     Credencial de partner.
     * @param string|null           $client_secret Secreto de partner.
     * @param Airalo_Logger|null    $logger        Logger para los avisos.
     * @return array{token:string,expires_in:int}|false
     */
    public static function request_new_token($base_url, $client_id = null, $client_secret = null, $logger = null) {
        if (null === $client_id || null === $client_secret) {
            $credentials = self::get_credentials();
            $client_id     = $credentials['client_id'];
            $client_secret = $credentials['client_secret'];
        }

        if ('' === $client_id || '' === $client_secret) {
            self::log($logger, 'Faltan credenciales de Airalo: define AIRALO_CLIENT_ID y AIRALO_CLIENT_SECRET en el entorno del servidor.');

            return false;
        }

        $url = rtrim((string) $base_url, '/') . self::TOKEN_ENDPOINT;

        $response = wp_remote_post($url, [
            'headers'   => [
                'Accept' => 'application/json',
            ],
            'body'      => [
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'client_credentials',
            ],
            'timeout'   => 30,
            'sslverify' => true,
        ]);

        if (is_wp_error($response)) {
            self::log($logger, 'No se pudo autenticar contra Airalo: ' . $response->get_error_message());

            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($body)) {
            $body = array();
        }

        if ($status_code >= 400) {
            $message = $body['meta']['message'] ?? ('HTTP ' . $status_code);
            self::log($logger, 'Airalo rechazó las credenciales (' . $status_code . '): ' . $message);

            return false;
        }

        $token = $body['data']['access_token'] ?? '';

        if ('' === $token || !is_string($token)) {
            self::log($logger, 'Airalo no devolvió un access_token válido.');

            return false;
        }

        $expires_in = isset($body['data']['expires_in']) ? (int) $body['data']['expires_in'] : self::DEFAULT_TOKEN_TTL;

        return array(
            'token'      => $token,
            'expires_in' => $expires_in > 0 ? $expires_in : self::DEFAULT_TOKEN_TTL,
        );
    }

    /**
     * Tiempo que se guarda el token en caché.
     *
     * Se resta un margen de seguridad al tiempo de caducidad que indica la API
     * para no usar un token a punto de caducar.
     *
     * @param int $expires_in Caducidad indicada por Airalo, en segundos.
     * @return int
     */
    public static function get_token_ttl($expires_in) {
        $ttl = (int) $expires_in - self::TOKEN_SAFETY_MARGIN;

        return $ttl > 0 ? $ttl : 60;
    }

    /**
     * Lee una variable del entorno.
     *
     * @param string $key Nombre de la variable.
     * @return string
     */
    private static function read_env($key) {
        $value = '';

        if (isset($_ENV[$key])) {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key])) {
            $value = $_SERVER[$key];
        } else {
            $value = getenv($key);
        }

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Escribe en el logger si lo hay.
     *
     * @param Airalo_Logger|null $logger  Logger.
     * @param string              $message Mensaje.
     * @return void
     */
    private static function log($logger, $message) {
        if ($logger instanceof Airalo_Logger) {
            $logger->log($message);

            return;
        }

        error_log('[Airalo API] ' . $message);
    }
}