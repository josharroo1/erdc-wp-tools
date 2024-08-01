<?php
function cac_auth_custom_login() {
    // Check if CAC authentication is enabled
    if (get_option('cac_auth_enabled', 'yes') !== 'yes') {
        return; // Let WordPress handle the login as usual
    }

    // Check for a special parameter to bypass CAC auth (for admin access)
    if (isset($_GET['wp_native_login'])) {
        return;
    }

    if (isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
        $dn = $_SERVER['SSL_CLIENT_S_DN_CN'];
        $dod_id = cac_extract_dod_id($dn);
        $hashed_dod_id = hash('sha256', $dod_id);
        $user = cac_get_user_by_dod_id($hashed_dod_id);

        if ($user) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            wp_redirect(admin_url()); // Or redirect to a specific page
            exit;
        }
    }

    // If CAC auth fails or isn't available, show the custom login page
    include(plugin_dir_path(__FILE__) . 'templates/cac-login-template.php');
    exit;
}
add_action('login_init', 'cac_auth_custom_login');

// Provide a way to access the default WordPress login
function cac_auth_login_url($login_url, $redirect) {
    if (get_option('cac_auth_enabled', 'yes') === 'yes') {
        $login_url = add_query_arg('wp_native_login', '1', $login_url);
    }
    return $login_url;
}
add_filter('login_url', 'cac_auth_login_url', 10, 2);