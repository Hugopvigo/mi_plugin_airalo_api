<?php
class Airalo_Auth {
    public function __construct() {
        add_action('admin_init', array($this, 'check_admin_access'));
        add_filter('wp_headers', array($this, 'security_headers'));
    }

    public function check_admin_access() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        
        if (strpos($current_page, 'airalo-api') === 0 && !current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'airalo-api'), 403);
        }
    }

    public function security_headers($headers) {
        if (is_admin()) {
            $headers['X-Content-Type-Options'] = 'nosniff';
            $headers['X-Frame-Options'] = 'SAMEORIGIN';
            $headers['X-XSS-Protection'] = '1; mode=block';
        }
        return $headers;
    }
}