<?php
/**
 * Páginas del admin del plugin.
 *
 * Registra el menú "Airalo API" y sus subpáginas (dashboard, eSIMs, estados de
 * orden y logs) y se encarga de recoger y sanear lo que llega por la URL
 * antes de dárselo a las plantillas.
 */
class Airalo_Admin_Pages {
    private $api_handler;
    private $logger;
    private $per_page = 50;
    private static $instance = null;

    /**
     * Instancia compartida de las páginas del admin.
     *
     * @param Airalo_API_Handler|null $api_handler Cliente de la API.
     * @param Airalo_Logger|null      $logger      Logger.
     * @return Airalo_Admin_Pages
     */
    public static function get_instance($api_handler = null, $logger = null) {
        if (self::$instance === null) {
            if ($logger === null) {
                $logger = Airalo_Logger::get_instance();
            }

            if ($api_handler === null) {
                $api_handler = Airalo_API_Handler::get_instance(null, $logger);
            }

            self::$instance = new self($api_handler, $logger);
        }

        return self::$instance;
    }

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
            'eSIMs',
            'eSIMs',
            'manage_options',
            'airalo-api-esims',
            array($this, 'render_esims_page')
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

    /**
     * Elementos por página, respetando la opción de pantalla y el límite de la
     * API (50 por página).
     *
     * @return int
     */
    private function get_per_page() {
        $per_page = (int) get_user_option('airalo_items_per_page');

        if ($per_page < 1 || $per_page > $this->per_page) {
            $per_page = $this->per_page;
        }

        return $per_page;
    }

    public function render_dashboard_page() {
        Airalo_Security_Checks::require_capability();

        include AIRALO_API_PLUGIN_DIR . 'templates/admin/main.php';
    }

    /**
     * Consulta de eSIM por ICCID.
     */
    public function render_esims_page() {
        Airalo_Security_Checks::require_capability();

        $iccid = '';
        $esim_data = array();

        $requested = Airalo_Security_Checks::get_request_value('iccid', 'text', 'get');

        if ($requested !== '') {
            $iccid = $requested;
            $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

            if (!Airalo_Security_Checks::verify_nonce($nonce, 'airalo_get_esim')) {
                $esim_data = [
                    'success' => false,
                    'error'   => 'La comprobación de seguridad ha fallado. Vuelve a abrir la página e inténtalo de nuevo.',
                ];
            } else {
                $esim_data = $this->api_handler->get_esim_by_iccid($iccid);
            }
        }

        include AIRALO_API_PLUGIN_DIR . 'templates/admin/esims.php';
    }

    public function render_order_statuses_page() {
        Airalo_Security_Checks::require_capability();

        $per_page = $this->get_per_page();
        $current_page = max(1, (int) Airalo_Security_Checks::get_request_value('paged', 'int', 'get'));
        $search_term = Airalo_Security_Checks::get_request_value('s', 'text', 'get');
        $action = Airalo_Security_Checks::get_request_value('action', 'text', 'get');
        $slug = Airalo_Security_Checks::get_request_value('slug', 'text', 'get');
        
        $params = [
            'filter[name]' => $search_term,
            'limit' => $per_page,
            'page' => $current_page
        ];
        
        $response = $this->api_handler->get_order_statuses($params, $current_page);
        
        if ($action === 'view-status' && $slug !== '') {
            $status_response = $this->api_handler->get_order_status_name($slug);
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/order-status-single.php';
        } else {
            $total_items = isset($response['meta']['total']) ? (int) $response['meta']['total'] : 0;
            $total_pages = $total_items > 0 ? (int) ceil($total_items / $per_page) : 1;
            
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/order-statuses.php';
        }
    }

    public function render_logs_page() {
        Airalo_Security_Checks::require_capability();

        $logs = $this->logger->get_logs();
        $current_log = Airalo_Security_Checks::get_request_value('log_file', 'file', 'get');
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        $download = Airalo_Security_Checks::get_request_value('download', 'file', 'get');
        $download_nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';

        if ($download !== '' && Airalo_Security_Checks::verify_nonce($download_nonce, 'download_log')) {
            $this->download_log($download);

            return;
        }

        if ($current_log !== '' && Airalo_Security_Checks::verify_nonce($nonce, 'view_log')) {
            $log_content = $this->logger->get_log_content($current_log);
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/log-viewer.php';
        } else {
            include AIRALO_API_PLUGIN_DIR . 'templates/admin/logs.php';
        }
    }

    /**
     * Envía el fichero de log como descarga.
     *
     * @param string $filename Nombre del fichero dentro del directorio de logs.
     * @return void
     */
    private function download_log($filename) {
        $content = $this->logger->get_log_content($filename);

        if ($content === false) {
            wp_die(
                __('No se ha podido leer el fichero de log.', 'airalo-api'),
                404,
                array('response' => 404, 'back_link' => true)
            );
        }

        $name = sanitize_file_name($filename);

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . strlen($content));

        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Es una descarga binaria.
        exit;
    }
}
