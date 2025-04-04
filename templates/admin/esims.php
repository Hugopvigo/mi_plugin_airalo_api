<div class="wrap">
    <h1 class="wp-heading-inline">Información de eSIM</h1>
    
    <form method="get" action="<?php echo admin_url('admin.php'); ?>" class="airalo-form">
        <input type="hidden" name="page" value="airalo-api-esims">
        <?php wp_nonce_field('airalo_get_esim'); ?>
        
        <div class="form-group">
            <label for="iccid">ICCID:</label>
            <input type="text" id="iccid" name="iccid" value="<?php echo esc_attr($iccid); ?>" 
                   placeholder="Ingrese el ICCID de la eSIM" required class="regular-text">
            <?php submit_button('Buscar eSIM', 'primary', 'submit', false); ?>
        </div>
    </form>
    
    <?php if (!empty($iccid)): ?>
        <div class="airalo-results">
            <?php if (isset($esim_data['success']) && $esim_data['success']): ?>
                <div class="notice notice-success">
                    <p>Información de eSIM obtenida correctamente</p>
                </div>
                
                <div class="airalo-card">
                    <h2>Detalles de la eSIM</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <tbody>
                            <tr>
                                <th>ICCID</th>
                                <td><?php echo esc_html($esim_data['data']['iccid'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Estado</th>
                                <td><?php echo esc_html($esim_data['data']['status'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>País</th>
                                <td><?php echo esc_html($esim_data['data']['country_code'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Creación</th>
                                <td><?php echo esc_html($esim_data['data']['created_at'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Fecha de Expiración</th>
                                <td><?php echo esc_html($esim_data['data']['expired_at'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Datos Restantes</th>
                                <td><?php echo esc_html($esim_data['data']['remaining'] ?? 'N/A'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($esim_data['data']['package'])): ?>
                <div class="airalo-card">
                    <h2>Paquete Actual</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <tbody>
                            <tr>
                                <th>Nombre</th>
                                <td><?php echo esc_html($esim_data['data']['package']['package'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Datos</th>
                                <td><?php echo esc_html($esim_data['data']['package']['data'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Validez</th>
                                <td><?php echo esc_html($esim_data['data']['package']['day'] ?? 'N/A'); ?> días</td>
                            </tr>
                            <tr>
                                <th>Precio</th>
                                <td>$<?php echo esc_html($esim_data['data']['package']['price'] ?? 'N/A'); ?> USD</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
            <?php elseif (isset($esim_data['error'])): ?>
                <div class="notice notice-error">
                    <p>Error al obtener información de eSIM: <?php echo esc_html($esim_data['error']); ?></p>
                    <?php if (!empty($esim_data['data'])): ?>
                        <pre><?php echo esc_html(print_r($esim_data['data'], true)); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>