<?php
/**
 * Detalle de un estado de orden.
 *
 * Variables disponibles: $status_response, $slug, $response, $current_page,
 * $search_term y $per_page.
 */

defined('ABSPATH') or exit;

if (!isset($status_response)) {
    $status_response = array();
}

if (!isset($slug)) {
    $slug = '';
}

$status = isset($status_response['data']) && is_array($status_response['data']) ? $status_response['data'] : array();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Estado de Orden', 'airalo-api'); ?></h1>

    <p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=airalo-api-order-statuses')); ?>" class="button">
            &larr; <?php _e('Volver a los estados', 'airalo-api'); ?>
        </a>
    </p>

    <?php if (isset($status_response['success']) && false === $status_response['success']): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($status_response['error'] ?? __('No se ha podido consultar la API de Airalo.', 'airalo-api')); ?></p>
        </div>
    <?php endif; ?>

    <div class="airalo-card">
        <h2><?php echo esc_html($slug); ?></h2>

        <?php if (empty($status)): ?>
            <p><?php _e('Airalo no ha devuelto datos para este estado.', 'airalo-api'); ?></p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <?php foreach ($status as $key => $value): ?>
                        <?php if (is_array($value)) { continue; } ?>
                        <tr>
                            <th><?php echo esc_html((string) $key); ?></th>
                            <td><?php echo esc_html(is_scalar($value) ? (string) $value : wp_json_encode($value)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
