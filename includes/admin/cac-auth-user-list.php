<?php
/**
 * CAC Authentication User List Modifications
 */

// Add user approval status column to the Users list table
function cac_auth_add_user_status_column($columns) {
    $user_approval_required = (bool) get_option('cac_auth_user_approval', false);

    if ($user_approval_required) {
        $columns['user_status'] = 'Status';
        $columns['user_approve_action'] = 'Approve/Reject';
    }

    return $columns; // Always return the columns array
}

add_filter('manage_users_columns', 'cac_auth_add_user_status_column');

// Display user approval status and action in the Users list table
function cac_auth_display_user_status_column($value, $column_name, $user_id) {
    $user_approval_required = (bool) get_option('cac_auth_user_approval', false);

    if ($user_approval_required) {
        if ('user_status' === $column_name) {
            $user_status = get_user_meta($user_id, 'user_status', true);
            if ('pending' === $user_status) {
                return '<span class="user-status pending">Pending Approval</span>';
            } elseif ('active' === $user_status) {
                return '<span class="user-status active">Active</span>';
            }
        } elseif ('user_approve_action' === $column_name) {
            $user_status = get_user_meta($user_id, 'user_status', true);
            if ('pending' === $user_status) {
                $approve_url = add_query_arg(['user_id' => $user_id, 'action' => 'approve'], admin_url('users.php'));
                $reject_url = add_query_arg(['user_id' => $user_id, 'action' => 'reject'], admin_url('users.php'));
                return '<a href="' . esc_url($approve_url) . '">Approve</a> | <a href="' . esc_url($reject_url) . '">Reject</a>';
            }
        }
    }

    return $value; // Always return the value
}
add_action('manage_users_custom_column', 'cac_auth_display_user_status_column', 10, 3);

// Handle individual approval/rejection actions
function cac_auth_handle_user_action() {
    if (isset($_GET['action']) && isset($_GET['user_id']) && current_user_can('edit_users')) {
        $user_id = intval($_GET['user_id']);
        $action = $_GET['action'];

        if ('approve' === $action) {
            update_user_meta($user_id, 'user_status', 'active');
            wp_redirect(add_query_arg('user_action', 'approved', admin_url('users.php')));
            exit;
        } elseif ('reject' === $action) {
            update_user_meta($user_id, 'user_status', 'rejected');
            wp_redirect(add_query_arg('user_action', 'rejected', admin_url('users.php')));
            exit;
        }
    }
}
add_action('admin_init', 'cac_auth_handle_user_action');

// Add a custom admin menu for user approvals
function cac_auth_add_admin_menu() {
    $user_approval_required = (bool) get_option('cac_auth_user_approval', false);

    if ($user_approval_required) {
        add_menu_page(
            'User Approvals',
            'User Approvals',
            'manage_options',
            'cac-user-approvals',
            'cac_auth_user_approvals_page',
            'dashicons-groups',
            30
        );
    }
}

add_action('admin_menu', 'cac_auth_add_admin_menu');

// User Approvals page content
function cac_auth_user_approvals_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('User Approvals', 'cac-auth'); ?></h1>
        <form method="post">
            <?php
            $user_query = new WP_User_Query([
                'meta_key' => 'user_status',
                'meta_value' => 'pending',
            ]);
            $users = $user_query->get_results();

            if (!empty($users)) {
                ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1">
                        </th>
                        <th><?php _e('Username', 'cac-auth'); ?></th>
                        <th><?php _e('Email', 'cac-auth'); ?></th>
                        <th><?php _e('Status', 'cac-auth'); ?></th>
                        <th><?php _e('Action', 'cac-auth'); ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($users as $user) {
                        ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="users[]" value="<?php echo esc_attr($user->ID); ?>">
                            </th>
                            <td><?php echo esc_html($user->user_login); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(get_user_meta($user->ID, 'user_status', true)); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['user_id' => $user->ID, 'action' => 'approve'], admin_url('users.php'))); ?>">
                                    <?php _e('Approve', 'cac-auth'); ?>
                                </a> |
                                <a href="<?php echo esc_url(add_query_arg(['user_id' => $user->ID, 'action' => 'reject'], admin_url('users.php'))); ?>">
                                    <?php _e('Reject', 'cac-auth'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                    </tbody>
                </table>
                <div class="bulk-actions">
                    <input type="submit" name="bulk_approve" class="button action" value="<?php _e('Bulk Approve', 'cac-auth'); ?>">
                    <input type="submit" name="bulk_reject" class="button action" value="<?php _e('Bulk Reject', 'cac-auth'); ?>">
                </div>
                <?php
            } else {
                _e('No users pending approval.', 'cac-auth');
            }
            ?>
        </form>
    </div>
    <?php
}

// Handle bulk actions on the custom admin page
function cac_auth_handle_custom_bulk_actions() {
    if (isset($_POST['bulk_approve']) && isset($_POST['users'])) {
        foreach ($_POST['users'] as $user_id) {
            update_user_meta($user_id, 'user_status', 'active');
        }
        wp_redirect(add_query_arg('bulk_action', 'approved', menu_page_url('cac-user-approvals', false)));
        exit;
    }

    if (isset($_POST['bulk_reject']) && isset($_POST['users'])) {
        foreach ($_POST['users'] as $user_id) {
            update_user_meta($user_id, 'user_status', 'rejected');
        }
        wp_redirect(add_query_arg('bulk_action', 'rejected', menu_page_url('cac-user-approvals', false)));
        exit;
    }
}
add_action('admin_init', 'cac_auth_handle_custom_bulk_actions');

// Display success message after bulk user approval/rejection
function cac_auth_display_bulk_action_success_message() {
    if (isset($_GET['bulk_action'])) {
        $action = $_GET['bulk_action'];
        if ('approved' === $action) {
            printf('<div class="updated notice is-dismissible"><p>' . __('Users have been approved.', 'cac-auth') . '</p></div>');
        } elseif ('rejected' === $action) {
            printf('<div class="updated notice is-dismissible"><p>' . __('Users have been rejected.', 'cac-auth') . '</p></div>');
        }
    } elseif (isset($_GET['user_action'])) {
        $action = $_GET['user_action'];
        if ('approved' === $action) {
            printf('<div class="updated notice is-dismissible"><p>' . __('User approved.', 'cac-auth') . '</p></div>');
        } elseif ('rejected' === $action) {
            printf('<div class="updated notice is-dismissible"><p>' . __('User rejected.', 'cac-auth') . '</p></div>');
        }
    }
}
add_action('admin_notices', 'cac_auth_display_bulk_action_success_message');