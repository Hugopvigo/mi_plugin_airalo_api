<?php
if (!defined('ABSPATH')) exit;

$logger = Airalo_Logger::get_instance();
$log_files = $logger->get_logs();
$current_log_content = '';
$current_log_file = '';

if (isset($_GET['log_file']) {
    check_admin_referer('view_log');
    $current_log_file = sanitize_text_field($_GET['log_file']);
    $current_log_content = $logger->get_log_content($current_log_file);
}
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
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=airalo-api-logs&log_file=' . $log['file']), 'view_log'); ?>"
                               class="<?php echo ($current_log_file === $log['file']) ? 'active' : ''; ?>">
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
            <?php if ($current_log_content): ?>
                <h2><?php _e('Contenido del Log:', 'airalo-api'); ?> <?php echo esc_html($current_log_file); ?></h2>
                <div class="airalo-log-content">
                    <pre><?php echo esc_html($current_log_content); ?></pre>
                </div>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=airalo-api-logs&download=' . $current_log_file), 'download_log'); ?>" 
                   class="button button-primary">
                    <?php _e('Descargar Log', 'airalo-api'); ?>
                </a>
            <?php else: ?>
                <p><?php _e('Selecciona un archivo de log para ver su contenido.', 'airalo-api'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>