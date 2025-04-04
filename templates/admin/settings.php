<?php
if (!defined('ABSPATH')) exit; // Salir si se accede directamente

$settings = Airalo_Settings::get_instance();
$utils = Airalo_Utilities::get_instance();
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Configuración guardada correctamente.', 'airalo-api'); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="airalo-settings-container">
        <div class="airalo-settings-main">
            <form method="post" action="options.php">
                <?php
                settings_fields('airalo_api_options');
                do_settings_sections('airalo-api-settings');
                submit_button(__('Guardar Configuración', 'airalo-api'));
                ?>
            </form>
        </div>
        
        <div class="airalo-settings-sidebar">
            <div class="airalo-card">
                <h3><?php _e('Información de Conexión', 'airalo-api'); ?></h3>
                <p><?php _e('Estado actual:', 'airalo-api'); ?> 
                    <strong><?php echo esc_html(get_option('airalo_api_mode', 'sandbox')); ?></strong>
                </p>
                <p><?php _e('Última verificación:', 'airalo-api'); ?> 
                    <strong><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), current_time('timestamp')); ?></strong>
                </p>
            </div>
            
            <div class="airalo-card">
                <h3><?php _e('Requisitos del Sistema', 'airalo-api'); ?></h3>
                <ul>
                    <li>WordPress 5.6+</li>
                    <li>PHP 7.4+</li>
                    <li>cURL habilitado</li>
                    <li>OpenSSL 1.1.1+</li>
                </ul>
            </div>
        </div>
    </div>
</div>