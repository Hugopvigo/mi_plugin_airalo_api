<?php
/*
Plugin Name: Mi Panel de Administración de AIRALO
Description: Plugin para gestionar la API de Airalo
Version: 1.0
Author: <a href="https://www.suop.es/" target="_blank">SUOP</a> - Hugo Perez-Vigo
*/

defined('ABSPATH') or die('Acceso directo no permitido.');

// Configuración de seguridad adicional
if (!defined('AIRALO_API_SECURE_KEY')) {
    define('AIRALO_API_SECURE_KEY', wp_generate_password(64, true, true));
}

// Cargar el núcleo seguro del plugin
require_once plugin_dir_path(__FILE__) . 'includes/security-checks.php';

// Verificar requisitos de seguridad antes de cargar
if (!Airalo_Security_Checks::pass_all_checks()) {
    add_action('admin_notices', 'airalo_api_security_notice');
    return;
}

// Añadir el menú principal al dashboard
function airalo_api_agregar_menu_admin() {
    add_menu_page(
        'API AIRALO MANAGEMENT',          // Título de la página
        'AIRALO API',                     // Texto del menú
        'manage_options',                 // Solo administradores
        'airalo-api',                     // Slug del menú
        'airalo_api_pagina_principal',    // Función que muestra el contenido
        'dashicons-rest-api',             // Icono de API (más adecuado)
        6                                 // Posición en el menú
    );
    
    // Añadir submenús
    add_submenu_page(
        'airalo-api',                     // Slug del menú padre
        'Submit Order',                   // Título de la página
        'Submit Order',                   // Texto del menú
        'manage_options',                 // Capacidad requerida
        'airalo-api-submit-order',        // Slug único
        'airalo_api_pagina_submit_order'  // Función callback
    );
    
    add_submenu_page(
        'airalo-api',
        'Get Order',
        'Get Order',
        'manage_options',
        'airalo-api-get-order',
        'airalo_api_pagina_get_order'
    );
    
    add_submenu_page(
        'airalo-api',
        'Update eSIM',
        'Update eSIM',
        'manage_options',
        'airalo-api-update-esim',
        'airalo_api_pagina_update_esim'
    );
}
add_action('admin_menu', 'airalo_api_agregar_menu_admin');

// Función para la página principal
function airalo_api_pagina_principal() {
    // Verificar permisos
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.'));
    }
    
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <div class="card">
            <h2 class="title">Bienvenido al panel de gestión de AIRALO API</h2>
            <p>Seleccione una opción del menú lateral para gestionar las operaciones con la API.</p>
        </div>
    </div>
    <?php
}

// Función para Submit Order
function airalo_api_pagina_submit_order() {
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para acceder a esta página.'));
    }
    
    ?>
    <div class="wrap">
        <h1>Submit Order - AIRALO API</h1>
        <!-- Aquí iría el formulario para submit order -->
        <form method="post" action="">
            <?php wp_nonce_field('airalo_submit_order_action', 'airalo_nonce'); ?>
            <!-- Campos del formulario -->
            <input type="submit" name="submit_order" class="button button-primary" value="Enviar Order">
        </form>
    </div>
    <?php
}

// Funciones para las otras páginas (similar a la anterior)
//function airalo_api_pagina_get_order() { /* ... */ }
//function airalo_api_pagina_update_esim() { /* ... */ }