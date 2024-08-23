<?php
function cac_auth_add_download_metrics_page() {
    add_menu_page(
        'Download Info',
        'Download Info',
        'manage_options',
        'cac-auth-download-metrics',
        'cac_auth_render_download_metrics_page',
        'dashicons-chart-bar',
        30
    );
}
add_action('admin_menu', 'cac_auth_add_download_metrics_page');

function cac_auth_render_download_metrics_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cac_download_metrics';

    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'download_count';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

    $metrics = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT m.*, p.post_title
            FROM $table_name m
            JOIN {$wpdb->posts} p ON m.attachment_id = p.ID
            ORDER BY %s %s",
            $orderby,
            $order
        )
    );

    ?>
    <div class="wrap">
        <h1>Download Metrics</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo cac_auth_sortable_column('File Name', 'post_title'); ?></th>
    <th scope="col">File ID</th>
                    <th scope="col">File URL</th>
                    <th scope="col"><?php echo cac_auth_sortable_column('Download Count', 'download_count'); ?></th>
                    <th scope="col"><?php echo cac_auth_sortable_column('Last Downloaded', 'last_downloaded'); ?></th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($metrics as $metric) : ?>
                    <tr>
                        <td><?php echo esc_html($metric->post_title); ?></td>
                        <td><?php echo esc_html($metric->attachment_id); ?></td>
                        <td><?php echo esc_url(wp_get_attachment_url($metric->attachment_id)); ?></td>
                        <td><?php echo esc_html($metric->download_count); ?></td>
                        <td><?php echo esc_html($metric->last_downloaded); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cac-auth-download-metrics&action=reset&attachment_id=' . $metric->attachment_id), 'reset_metrics_' . $metric->attachment_id); ?>" class="button button-secondary">Reset Metrics</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function cac_auth_sortable_column($label, $column) {
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'download_count';
    $order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'desc' : 'asc';
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
    $url = add_query_arg([
        'page' => $current_page,
        'orderby' => $column,
        'order' => $order,
    ]);

    $class = $orderby === $column ? 'sorted ' . $order : 'sortable asc';
    
    return sprintf(
        '<a href="%s" class="%s"><span>%s</span><span class="sorting-indicator"></span></a>',
        esc_url($url),
        esc_attr($class),
        esc_html($label)
    );
}

function cac_auth_handle_reset_metrics() {
    if (isset($_GET['action']) && $_GET['action'] === 'reset' && isset($_GET['attachment_id'])) {
        $attachment_id = intval($_GET['attachment_id']);
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'reset_metrics_' . $attachment_id)) {
            wp_die('Security check failed');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'cac_download_metrics';
        
        $wpdb->update(
            $table_name,
            array(
                'download_count' => 0,
                'last_downloaded' => '0000-00-00 00:00:00'
            ),
            array('attachment_id' => $attachment_id),
            array('%d', '%s'),
            array('%d')
        );
        
        wp_redirect(add_query_arg('reset', 'success', admin_url('admin.php?page=cac-auth-download-metrics')));
        exit;
    }
}
add_action('admin_init', 'cac_auth_handle_reset_metrics');