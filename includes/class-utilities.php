<?php
class Airalo_Utilities {
    private static $instance;
    private $logger;

    private function __construct($logger) {
        $this->logger = $logger;
    }

    public static function get_instance($logger = null) {
        if (!isset(self::$instance)) {
            self::$instance = new self($logger);
        }
        return self::$instance;
    }

    /**
     * Sanitiza datos para uso en la API
     */
    public function sanitize_api_data($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize_api_data'], $data);
        }

        if (is_string($data)) {
            return sanitize_text_field($data);
        }

        return $data;
    }

    /**
     * Valida un ICCID
     */
    public function validate_iccid($iccid) {
        $valid = preg_match('/^[0-9]{19,20}$/', $iccid);
        
        if (!$valid) {
            $this->logger->log('ICCID inválido detectado', ['iccid' => $iccid]);
        }
        
        return $valid;
    }

    /**
     * Formatea bytes a unidades legibles
     */
    public function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Obtiene la IP del cliente
     */
    public function get_client_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (isset($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        
        return 'UNKNOWN';
    }

    /**
     * Genera un nonce para formularios
     */
    public function generate_nonce($action = 'airalo_api_action') {
        return wp_create_nonce($action);
    }

    /**
     * Verifica un nonce
     */
    public function verify_nonce($nonce, $action = 'airalo_api_action') {
        return wp_verify_nonce($nonce, $action);
    }
}