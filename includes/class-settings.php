<?php
class Airalo_Settings {
    private $logger;

    public function __construct($logger) {
        $this->logger = $logger;
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
    }

    public function activate() {
        if (!get_option('airalo_api_mode')) {
            update_option('airalo_api_mode', 'sandbox');
        }

        $this->logger->log('Plugin activated');
    }

    public static function uninstall() {
        delete_option('airalo_api_mode');
        // No borramos logs para mantener historial
    }

    public function add_settings_page() {
        add_options_page(
            'Configuración Airalo API',
            'Airalo API',
            'manage_options',
            'airalo-api-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('airalo_api_options', 'airalo_api_mode', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_mode'),
            'default' => 'sandbox'
        ));

        add_settings_section(
            'airalo_api_main',
            'Configuración de la API',
            array($this, 'settings_section_callback'),
            'airalo-api-settings'
        );

        add_settings_field(
            'airalo_api_mode',
            'Modo de API',
            array($this, 'api_mode_callback'),
            'airalo-api-settings',
            'airalo_api_main'
        );
    }

    public function sanitize_mode($input) {
        $valid = array('sandbox', 'production');
        if (!in_array($input, $valid)) {
            add_settings_error('airalo_api_mode', 'invalid_mode', 'Modo de API no válido');
            $this->logger->log('Intento de configuración de modo inválido: ' . $input);
            return 'sandbox';
        }
        return $input;
    }

    public function settings_section_callback() {
        echo '<p>Configura los parámetros básicos de conexión con la API de Airalo.</p>';
        echo '<p>Para credenciales seguras, configura las variables en tu archivo <code>.env</code>:</p>';
        echo '<pre>AIRALO_API_MODE=sandbox
AIRALO_CLIENT_ID=tu_client_id
AIRALO_CLIENT_SECRET=tu_client_secret</pre>';
    }

    public function api_mode_callback() {
        $mode = get_option('airalo_api_mode', 'sandbox');
        ?>
        <select name="airalo_api_mode" id="airalo_api_mode">
            <option value="sandbox" <?php selected($mode, 'sandbox'); ?>>Sandbox</option>
            <option value="production" <?php selected($mode, 'production'); ?>>Producción</option>
        </select>
        <?php
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.'));
        }
        ?>
        <div class="wrap">
            <h1>Configuración de Airalo API</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('airalo_api_options');
                do_settings_sections('airalo-api-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}