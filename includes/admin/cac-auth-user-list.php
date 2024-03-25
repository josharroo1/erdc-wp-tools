<?php
/**
 * CAC Authentication User List Modifications
 */

// Add user approval status column to the Users list table
function cac_auth_add_user_status_column($columns) {
    $columns['user_status'] = 'Status';
    return $columns;
}
add_filter('manage_users_columns', 'cac_auth_add_user_status_column');

// Display user approval status in the Users list table
function cac_auth_display_user_status_column($value, $column_name, $user_id) {
    if ('user_status' === $column_name) {
        $user_status = get_user_meta($user_id, 'user_status', true);
        if ('pending' === $user_status) {
            return '<span class="user-status pending">Pending Approval</span>';
        } elseif ('active' === $user_status) {
            return '<span class="user-status active">Active</span>';
        }
    }
    return $value;
}
add_action('manage_users_custom_column', 'cac_auth_display_user_status_column', 10, 3);

// Add bulk action to approve users
function cac_auth_add_bulk_actions($bulk_actions) {
    $bulk_actions['approve'] = 'Approve';
    return $bulk_actions;
}
add_filter('bulk_actions-users', 'cac_auth_add_bulk_actions');

// Handle bulk user approval
function cac_auth_handle_bulk_user_approval($redirect_to, $action, $user_ids) {
    if ('approve' === $action) {
        foreach ($user_ids as $user_id) {
            update_user_meta($user_id, 'user_status', 'active');
        }
        $redirect_to = add_query_arg('approved', count($user_ids), $redirect_to);
    }
    return $redirect_to;
}
add_filter('handle_bulk_actions-users', 'cac_auth_handle_bulk_user_approval', 10, 3);

// Display success message after bulk user approval
function cac_auth_display_bulk_approval_success_message() {
    if (isset($_REQUEST['approved'])) {
        $approved_count = intval($_REQUEST['approved']);
        printf('<div class="updated notice is-dismissible"><p>' . _n('%s user approved.', '%s users approved.', $approved_count, 'cac-auth') . '</p></div>', $approved_count);
    }
}
add_action('admin_notices', 'cac_auth_display_bulk_approval_success_message');