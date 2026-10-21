<?php
/**
 * Listado de estados de orden de Airalo.
 *
 * Variables disponibles: $response, $current_page, $search_term, $total_items,
 * $total_pages y $per_page.
 */

defined('ABSPATH') or exit;

if (!isset($response)) {
    $response = array();
}

if (!isset($current_page)) {
    $current_page = 1;
}

if (!isset($search_term)) {
    $search_term = '';
}

if (!isset($total_items)) {
    $total_items = 0;
}

if (!isset($total_pages)) {
    $total_pages = 1;
}

if (!isset($per_page)) {
    $per_page = 50;
}

$statuses = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Estados de Orden', 'airalo-api'); ?></h1>

    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="airalo-form">
        <input type="hidden" name="page" value="airalo-api-order-statuses">
        <p>
            <label for="s"><?php _e('Buscar por nombre:', 'airalo-api'); ?></label>
            <input type="search" id="s" name="s" value="<?php echo esc_attr($search_term); ?>"
                   class="regular-text">
            <?php submit_button(__('Buscar', 'airalo-api'), 'secondary', 'submit', false); ?>
        </p>
    </form>

    <?php if (isset($response['success']) && false === $response['success']): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($response['error'] ?? __('No se ha podido consultar la API de Airalo.', 'airalo-api')); ?></p>
        </div>
    <?php endif; ?>

    <div class="airalo-card">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'airalo-api'); ?></th>
                    <th><?php _e('Nombre', 'airalo-api'); ?></th>
                    <th><?php _e('Slug', 'airalo-api'); ?></th>
                    <th><?php _e('Acciones', 'airalo-api'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($statuses)): ?>
                    <tr>
                        <td colspan="4"><?php _e('No hay estados de orden que mostrar.', 'airalo-api'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($statuses as $status): ?>
                        <?php
                        $status_slug = isset($status['slug']) ? (string) $status['slug'] : '';
                        $status_name = isset($status['name']) ? (string) $status['name'] : $status_slug;
                        $status_id   = isset($status['id']) ? (string) $status['id'] : '';
                        ?>
                        <tr>
                            <td><?php echo esc_html($status_id); ?></td>
                            <td><?php echo esc_html($status_name); ?></td>
                            <td><code><?php echo esc_html($status_slug); ?></code></td>
                            <td>
                                <?php if ($status_slug !== ''): ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=airalo-api-order-statuses&action=view-status&slug=' . rawurlencode($status_slug))); ?>">
                                        <?php _e('Ver detalle', 'airalo-api'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <p class="airalo-pagination">
            <?php
            printf(
                /* translators: 1: número de estados, 2: página actual, 3: número de páginas */
                esc_html__('Mostrando %1$d estados (página %2$d de %3$d).', 'airalo-api'),
                (int) $total_items,
                (int) $current_page,
                (int) $total_pages
            );
            ?>
        </p>

        <?php if ($total_pages > 1): ?>
            <p class="airalo-pagination-links">
                <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                    <?php
                    $args = array(
                        'page'  => 'airalo-api-order-statuses',
                        'paged' => $page,
                    );

                    if ($search_term !== '') {
                        $args['s'] = $search_term;
                    }

                    $url = add_query_arg($args, admin_url('admin.php'));
                    ?>
                    <?php if ($page === (int) $current_page): ?>
                        <span class="airalo-page current"><?php echo esc_html($page); ?></span>
                    <?php else: ?>
                        <a class="airalo-page" href="<?php echo esc_url($url); ?>"><?php echo esc_html($page); ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </p>
        <?php endif; ?>
    </div>
</div>
