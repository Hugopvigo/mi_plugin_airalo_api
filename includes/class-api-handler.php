<?php
class Airalo_API_Handler {
    private $sandbox_url = 'https://sandbox-partners-api.airalo.com';
    private $production_url = 'https://partners-api.airalo.com';
    private $api_mode;
    private $access_token;
    private $token_expiry;
    private $cache;
    private $logger;

    public function __construct($cache, $logger) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->api_mode = getenv('AIRALO_API_MODE') ?: get_option('airalo_api_mode', 'sandbox');
        $this->refresh_token();
    }

    private function refresh_token() {
        $token_data = $this->cache->get('airalo_api_token');
        
        if ($token_data && $token_data['expiry'] > time()) {
            $this->access_token = $token_data['token'];
            $this->token_expiry = $token_data['expiry'];
            return;
        }

        // Obtener nuevo token (simulado - en realidad deberías llamar al endpoint de autenticación de Airalo)
        $new_token = $this->request_new_token();
        
        if ($new_token) {
            $this->access_token = $new_token['token'];
            $this->token_expiry = time() + $new_token['expires_in'];
            
            $this->cache->set('airalo_api_token', [
                'token' => $this->access_token,
                'expiry' => $this->token_expiry
            ], $new_token['expires_in']);
        }
    }

    private function request_new_token() {
        $client_id = getenv('AIRALO_CLIENT_ID');
        $client_secret = getenv('AIRALO_CLIENT_SECRET');
        
        if (!$client_id || !$client_secret) {
            $this->logger->log('Error: Falta CLIENT_ID o CLIENT_SECRET en .env');
            return false;
        }

        // Simulación de obtención de token (adaptar a la API real de Airalo)
        return [
            'token' => 'nuevo_token_simulado_' . bin2hex(random_bytes(16)),
            'expires_in' => 43200 // 12 horas en segundos
        ];
    }

    private function get_base_url() {
        return $this->api_mode === 'production' ? $this->production_url : $this->sandbox_url;
    }

    public function make_request($endpoint, $method = 'GET', $params = array(), $cache_ttl = 0) {
        $cache_key = 'api_req_' . md5($endpoint . serialize($params));
        
        if ($cache_ttl > 0 && $cached = $this->cache->get($cache_key)) {
            return $cached;
        }

        $this->refresh_token();
        
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
            $url = add_query_arg(array_map('urlencode', $params), $url);
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
}