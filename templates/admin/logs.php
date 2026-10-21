<?php
/**
 * Listado de ficheros de log del plugin.
 *
 * El contenido de un log se ve en log-viewer.php, que es lo que se incluye
 * cuando la URL lleva un nonce válido.
 */

defined('ABSPATH') or exit;

$logger = Airalo_Logger::get_instance();
$log_files = $logger->get_logs();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Registros del Sistema', 'airalo-api'); ?></h1>
    
    <div class="airalo-logs-container">
        <div class="airalo-logs-list">
            <h2><?php _e('Archivos de Log', 'airalo-api'); ?></h2>
            
            <?php if (!empty($log_files)): ?>
                <ul>
                    <?php foreach ($log_files as $log): ?>
                        <li>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=airalo-api-logs&log_file=' . rawurlencode($log['file'])), 'view_log')); ?>"
                               class="airalo-log-link">
                                <?php echo esc_html($log['date']); ?> (<?php echo esc_html($log['size']); ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('No hay archivos de log disponibles.', 'airalo-api'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="airalo-log-viewer">
            <p><?php _e('Selecciona un archivo de log para ver su contenido.', 'airalo-api'); ?></p>
        </div>
    </div>
</div>
