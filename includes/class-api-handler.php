<?php
/**
 * Cliente REST de la API de Airalo.
 *
 * Base: {sandbox|production}-partners-api.airalo.com, con los endpoints de la
 * versión v2 (`/v2/...`). El token de portador se pide a `/v2/token` con las
 * credenciales de partner y se cachea hasta su caducidad.
 */
class Airalo_API_Handler {
    /**
     * Bases de la API de Airalo.
     *
     * El host de sandbox viene del diseño original de este plugin y no está
     * verificado contra la API real: el plugin hermano `plugin_airalo_wordpress`,
     * que sí funciona, solo usa `https://partners-api.airalo.com/v2`. Si
     * `sandbox-partners-api.airalo.com` no responde, hay que corregirlo aquí.
     */
    private $sandbox_url = 'https://sandbox-partners-api.airalo.com';
    private $production_url = 'https://partners-api.airalo.com';
    private $api_mode;
    private $access_token;
    private $token_expiry;
    private $cache;
    private $logger;
    private static $instance = null;

    /**
     * Instancia compartida del cliente.
     *
     * @param Airalo_Cache|null  $cache  Caché de respuestas y token.
     * @param Airalo_Logger|null $logger Logger.
     * @return Airalo_API_Handler
     */
    public static function get_instance($cache = null, $logger = null) {
        if (self::$instance === null) {
            if ($logger === null) {
                $logger = Airalo_Logger::get_instance();
            }

            if ($cache === null) {
                $cache = Airalo_Cache::get_instance($logger);
            }

            self::$instance = new self($cache, $logger);
        }

        return self::$instance;
    }

    public function __construct($cache, $logger) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->api_mode = getenv('AIRALO_API_MODE') ?: get_option('airalo_api_mode', 'sandbox');
    }

    /**
     * Comprueba si hay un token de portador válido (y lo pide si hace falta).
     *
     * @return bool
     */
    public function has_valid_token() {
        return $this->ensure_token();
    }

    /**
     * Token de portador en uso, o cadena vacía.
     *
     * @return string
     */
    public function get_access_token() {
        $this->ensure_token();

        return (string) $this->access_token;
    }

    /**
     * Modo de API activo: `sandbox` o `production`.
     *
     * @return string
     */
    public function get_api_mode() {
        return $this->api_mode;
    }

    /**
     * Pide un token nuevo a Airalo.
     *
     * El token se pide contra el endpoint real de autenticación
     * (`{base}/v2/token`) con las credenciales de partner. Si no hay
     * credenciales, se registra el motivo y se devuelve false.
     *
     * @return array{token:string,expires_in:int}|false
     */
    public function request_new_token() {
        return Airalo_Auth::request_new_token(
            $this->get_base_url(),
            null,
            null,
            $this->logger
        );
    }

    /**
     * Carga o renueva el token de portador.
     *
     * @return bool True si hay token válido.
     */
    private function ensure_token() {
        if (!empty($this->access_token) && $this->token_expiry > time()) {
            return true;
        }

        $token_data = $this->cache->get('airalo_api_token');

        if (is_array($token_data) && !empty($token_data['token']) && ($token_data['expiry'] ?? 0) > time()) {
            $this->access_token = $token_data['token'];
            $this->token_expiry = (int) $token_data['expiry'];

            return true;
        }

        $new_token = $this->request_new_token();

        if (!$new_token) {
            return false;
        }

        $this->access_token = $new_token['token'];
        $this->token_expiry = time() + $new_token['expires_in'];

        $ttl = Airalo_Auth::get_token_ttl($new_token['expires_in']);

        $this->cache->set('airalo_api_token', [
            'token'  => $this->access_token,
            'expiry' => $this->token_expiry,
        ], $ttl);

        return true;
    }

    private function get_base_url() {
        return $this->api_mode === 'production' ? $this->production_url : $this->sandbox_url;
    }

    public function make_request($endpoint, $method = 'GET', $params = array(), $cache_ttl = 0) {
        $cache_key = 'api_req_' . md5($endpoint . serialize($params));

        if ($cache_ttl > 0 && $cached = $this->cache->get($cache_key)) {
            return $cached;
        }

        if (!$this->ensure_token()) {
            return [
                'success' => false,
                'error'   => 'Faltan credenciales de Airalo: define AIRALO_CLIENT_ID y AIRALO_CLIENT_SECRET en el entorno del servidor.',
            ];
        }

        $url = $this->get_base_url() . $endpoint;
        $args = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token,
            ],
            'timeout' => 30,
            'sslverify' => true,
        ];

        if ($method === 'GET' && !empty($params)) {
            $query = http_build_query($params, '', '&');

            if ($query !== '') {
                $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
            }
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = json_encode($params);
            $args['headers']['Content-Type'] = 'application/json';
        }

        $args['method'] = $method;

        $response = wp_remote_request(esc_url_raw($url), $args);
        $log_data = [
            'endpoint' => $endpoint,
            'method' => $method,
            'params' => $params,
            'response' => $response
        ];

        if (is_wp_error($response)) {
            $this->logger->log('API Request Error', $log_data);
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 400) {
            $this->logger->log('API Request Failed', $log_data);
            return [
                'success' => false,
                'status' => $status_code,
                'error' => $data['meta']['message'] ?? 'Error desconocido',
                'data' => $data['data'] ?? [],
            ];
        }

        $result = [
            'success' => true,
            'status' => $status_code,
            'data' => $data['data'] ?? [],
            'meta' => $data['meta'] ?? [],
        ];

        if ($cache_ttl > 0) {
            $this->cache->set($cache_key, $result, $cache_ttl);
        }

        return $result;
    }

    public function get_order_statuses($params = array(), $page = 1) {
        $defaults = [
            'filter[name]' => '',
            'limit' => 50, // Máximo 50 por página
            'page' => $page,
        ];

        $params = wp_parse_args($params, $defaults);
        return $this->make_request('/v2/orders/statuses', 'GET', $params, HOUR_IN_SECONDS);
    }

    public function get_order_status_name($slug) {
        return $this->make_request('/v2/orders/statuses/' . sanitize_title($slug), 'GET', [], HOUR_IN_SECONDS);
    }

    /**
     * Datos de una eSIM a partir de su ICCID.
     *
     * @param string $iccid ICCID de la eSIM (19 o 20 dígitos).
     * @return array Respuesta con las claves success, status, data y meta.
     */
    public function get_esim_by_iccid($iccid) {
        $iccid = preg_replace('/[^0-9]/', '', (string) $iccid);

        if ($iccid === '') {
            return [
                'success' => false,
                'error'   => 'ICCID no válido.',
            ];
        }

        return $this->make_request('/v2/sims/' . rawurlencode($iccid), 'GET', [], HOUR_IN_SECONDS);
    }
}
