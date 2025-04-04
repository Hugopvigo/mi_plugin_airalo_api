<?php
if (!defined('ABSPATH')) exit;

$api_handler = Airalo_API_Handler::get_instance();
$utils = Airalo_Utilities::get_instance();
$logger = Airalo_Logger::get_instance();
?>

<div class="wrap">
    <h1 class="wp-heading-inline">API AIRALO MANAGEMENT</h1>
    
    <?php do_action('airalo_before_dashboard_content'); ?>
    
    <div class="airalo-dashboard">
        <!-- Sección de estado del sistema -->
        <div class="airalo-card">
            <h2>Estado del Sistema</h2>
            <div class="airalo-system-status">
                <div class="airalo-status-item">
                    <span class="status-label">Conexión API:</span>
                    <span class="status-value <?php echo $api_handler->has_valid_token() ? 'status-good' : 'status-bad'; ?>">
                        <?php echo $api_handler->has_valid_token() ? 'CONECTADO' : 'DESCONECTADO'; ?>
                    </span>
                </div>
                <div class="airalo-status-item">
                    <span class="status-label">Modo Actual:</span>
                    <span class="status-value"><?php echo strtoupper(get_option('airalo_api_mode', 'sandbox')); ?></span>
                </div>
                <div class="airalo-status-item">
                    <span class="status-label">Última Actualización:</span>
                    <span class="status-value"><?php echo current_time('mysql'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Sección de acciones rápidas -->
        <div class="airalo-card">
            <h2>Acciones Rápidas</h2>
            <div class="airalo-quick-actions">
                <a href="<?php echo admin_url('admin.php?page=airalo-api-esims'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-search"></span> Buscar eSIM
                </a>
                <a href="<?php echo admin_url('admin.php?page=airalo-api-order-statuses'); ?>" class="button">
                    <span class="dashicons dashicons-list-view"></span> Estados de Orden
                </a>
                <a href="<?php echo admin_url('options-general.php?page=airalo-api-settings'); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings"></span> Configuración
                </a>
            </div>
        </div>
        
        <!-- Sección de actividad reciente -->
        <div class="airalo-card">
            <h2>Actividad Reciente</h2>
            <?php $recent_logs = $logger->get_recent_logs(5); ?>
            <?php if (!empty($recent_logs)): ?>
                <ul class="airalo-activity-log">
                    <?php foreach ($recent_logs as $log): ?>
                        <li>
                            <span class="log-time">[<?php echo esc_html($log['time']); ?>]</span>
                            <span class="log-message"><?php echo esc_html($log['message']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="<?php echo admin_url('admin.php?page=airalo-api-logs'); ?>" class="button">
                    Ver logs completos
                </a>
            <?php else: ?>
                <p>No hay actividad reciente registrada.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php do_action('airalo_after_dashboard_content'); ?>
</div>