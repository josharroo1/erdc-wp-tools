<?php
/**
 * CAC Authentication Download Metrics
 */

function cac_auth_add_download_metrics_page() {
    add_menu_page(
        'Downloads Info',
        'Downloads Info',
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
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

    // Calculate total downloads
    $total_downloads = $wpdb->get_var("SELECT SUM(download_count) FROM $table_name");
    $total_downloads = $total_downloads ? $total_downloads : 0; // Ensure it's not null

    $query = "SELECT m.*, p.post_title, p.guid
              FROM $table_name m
              JOIN {$wpdb->posts} p ON m.attachment_id = p.ID";

    if (!empty($search)) {
        $query .= $wpdb->prepare(
            " WHERE p.post_title LIKE %s OR p.guid LIKE %s",
            '%' . $wpdb->esc_like($search) . '%',
            '%' . $wpdb->esc_like($search) . '%'
        );
    }

    $query .= " ORDER BY $orderby $order";

    $metrics = $wpdb->get_results($query);

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Download Metrics</h1>
        <?php
        $export_url = wp_nonce_url(admin_url('admin-post.php?action=export_download_metrics'), 'export_download_metrics');
        echo '<a href="' . esc_url($export_url) . '" class="page-title-action">Export to CSV</a>';
        ?>
        <div class="total-downloads-info">
            <p><strong>Total File Downloads: </strong><?php echo number_format($total_downloads); ?></p>
        </div>
        <form method="get">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']) ?>">
            <p class="search-box">
                <label class="screen-reader-text" for="download-metrics-search-input">Search Downloads:</label>
                <input type="search" id="download-metrics-search-input" name="s" value="<?php echo esc_attr($search) ?>">
                <input type="submit" id="search-submit" class="button" value="Search Downloads">
            </p>
        </form>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo cac_auth_sortable_column('File Name', 'post_title'); ?></th>
                    <th scope="col">Media ID</th>
                    <th scope="col">File Path</th>
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
                        <td>
                            <?php
                            $full_url = esc_url($metric->guid);
                            $site_url = site_url();
                            $relative_path = str_replace($site_url, '', $full_url);
                            echo "<a href='{$full_url}' target='_blank'>" . esc_html($relative_path) . "</a>";
                            ?>
                        </td>
                        <td><?php echo esc_html($metric->download_count); ?></td>
                        <td><?php echo esc_html($metric->last_downloaded); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=cac-auth-download-metrics&action=reset&attachment_id=' . $metric->attachment_id), 'reset_metrics_' . $metric->attachment_id); ?>" class="button button-secondary">Reset This Metric</a>
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
        
        // Delete the entry instead of updating it
        $wpdb->delete(
            $table_name,
            array('attachment_id' => $attachment_id),
            array('%d')
        );
        
        wp_redirect(add_query_arg('reset', 'success', admin_url('admin.php?page=cac-auth-download-metrics')));
        exit;
    }
}
add_action('admin_init', 'cac_auth_handle_reset_metrics');

function cac_auth_export_download_metrics() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    check_admin_referer('export_download_metrics');

    global $wpdb;
    $table_name = $wpdb->prefix . 'cac_download_metrics';

    $metrics = $wpdb->get_results(
        "SELECT m.*, p.post_title, p.guid
        FROM $table_name m
        JOIN {$wpdb->posts} p ON m.attachment_id = p.ID
        ORDER BY m.download_count DESC"
    );

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="download_metrics.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('File Name', 'File ID', 'File URL', 'Download Count', 'Last Downloaded'));

    foreach ($metrics as $metric) {
        fputcsv($output, array(
            $metric->post_title,
            $metric->attachment_id,
            $metric->guid,
            $metric->download_count,
            $metric->last_downloaded
        ));
    }

    fclose($output);
    exit;
}
add_action('admin_post_export_download_metrics', 'cac_auth_export_download_metrics');