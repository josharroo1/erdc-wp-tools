<?php
function cac_auth_virtual_login_page() {
    // Check if CAC authentication is enabled
    if (get_option('cac_auth_enabled', 'yes') !== 'yes') {
        return; // Let WordPress handle the login as usual
    }

    if ($_SERVER['REQUEST_URI'] === '/wp-login.php' || $_SERVER['REQUEST_URI'] === '/login/') {
        // Attempt CAC authentication
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
        status_header(200);
        include(plugin_dir_path(__FILE__) . 'templates/cac-login-template.php');
        exit;
    }
}
add_action('init', 'cac_auth_virtual_login_page', 1);