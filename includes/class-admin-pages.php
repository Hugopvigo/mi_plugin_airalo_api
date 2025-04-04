<?php
class Airalo_Admin_Pages {
    private $api_handler;
    private $logger;
    private $per_page = 50;

    public function __construct($api_handler, $logger) {
        $this->api_handler = $api_handler;
        $this->logger = $logger;
        
        add_action('admin_menu', array($this, 'add_admin_pages'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'airalo-api') !== false) {
            wp_enqueue_style(
                'airalo-admin-css',
                AIRALO_API_PLUGIN_URL . 'assets/css/admin.css',
                [],
                AIRALO_API_VERSION
            );
            
            wp_enqueue_script(
                'airalo-admin-js',
                AIRALO_API_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AIRALO_API_VERSION,
                true
            );
            
            wp_localize_script('airalo-admin-js', 'airaloApi', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('airalo-api-nonce')
            ]);
        }
    }

    public function add_admin_pages() {
        $menu_hook = add_menu_page(
            'Airalo API',
            'Airalo API',
            'manage_options',
            'airalo-api',
            array($this, 'render_dashboard_page'),
            'dashicons-sim',
            80
        );
        
        add_action("load-$menu_hook", array($this, 'screen_options'));
        
        add_submenu_page(
            'airalo-api',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'airalo-api',
            array($this, 'render_dashboard_page')
        );
        
        add_submenu_page(
            'airalo-api',
            'Estados de Orden',
            'Estados de Orden',
            'manage_options',
            'airalo-api-order-statuses',
            array($this, 'render_order_statuses_page')
        );
        
        add_submenu_page(
            'airalo-api',
            'Logs del Sistema',
            'Logs',
            'manage_options',
            'airalo-api-logs',
            array($this, 'render_logs_page')
        );
    }

    public function screen_options() {
        $option = 'per_page';
        $args = [
            'label' => 'Elementos por página',
            'default' => 50,
            'option' => 'airalo_items_per_page'
        ];
        
        add_screen_option($option, $args);
    }

    public function render_dashboard_page() {
        include AIRALO_API_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }

    public function render_order_statuses_page() {
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $params = [
            'filter[name]' => $search_term,
            'limit' => $this->per_page,
            'page' => $current_page
        ];
        
        $response = $this->api_handler->get_order_statuses($params, $current_page);
        
        if (isset($_GET['action']) && $_GET['action'] === 'view-status' && isset($_GET['slug'])) {
            $status_response = $this->api_handler->get_order_status_name($_GET['slug']);
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/order-status-single.php';
        } else {
            $total_items = $response['meta']['total'] ?? 0;
            $total_pages = ceil($total_items / $this->per_page);
            
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/order-statuses.php';
        }
    }

    public function render_logs_page() {
        $logs = $this->logger->get_logs();
        $current_log = isset($_GET['log_file']) ? $_GET['log_file'] : '';
        
        if ($current_log && wp_verify_nonce($_GET['_wpnonce'], 'view_log')) {
            $log_content = $this->logger->get_log_content($current_log);
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/log-viewer.php';
        } else {
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/logs.php';
        }
    }
}