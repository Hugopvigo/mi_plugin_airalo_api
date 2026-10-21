<?php
/**
 * Visor del contenido de un fichero de log.
 *
 * La incluye Airalo_Admin_Pages::render_logs_page() cuando la URL lleva un
 * nonce `view_log` válido. Variables disponibles: $log_content y $current_log.
 */

defined('ABSPATH') or exit;

if (!isset($current_log)) {
    $current_log = '';
}

if (!isset($log_content) || $log_content === false) {
    $log_content = '';
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Visor de Logs', 'airalo-api'); ?></h1>

    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=airalo-api-logs')); ?>" class="button">
            &larr; <?php _e('Volver a los logs', 'airalo-api'); ?>
        </a>
    </p>

    <div class="airalo-logs-container">
        <div class="airalo-log-viewer">
            <?php if ($current_log !== ''): ?>
                <h2>
                    <?php _e('Contenido del Log:', 'airalo-api'); ?>
                    <?php echo esc_html($current_log); ?>
                </h2>

                <?php if ($log_content !== ''): ?>
                    <div class="airalo-log-content">
                        <pre><?php echo esc_html($log_content); ?></pre>
                    </div>

                    <p>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=airalo-api-logs&download=' . rawurlencode($current_log)), 'download_log')); ?>"
                           class="button button-primary">
                            <?php _e('Descargar Log', 'airalo-api'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <p><?php _e('El fichero de log está vacío.', 'airalo-api'); ?></p>
                <?php endif; ?>
            <?php else: ?>
                <p><?php _e('Selecciona un archivo de log para ver su contenido.', 'airalo-api'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
