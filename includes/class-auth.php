<?php
class Airalo_Auth {
    private $protected_pages = [
        'airalo-api',
        'airalo-api-order-statuses',
        'airalo-api-esims',
        'airalo-api-logs',
        'airalo-api-settings'
    ];

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
}