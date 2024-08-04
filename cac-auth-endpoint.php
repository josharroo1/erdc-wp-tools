<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Ensure this file is being accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Force SSL
if (!is_ssl()) {
    wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301);
    exit();
}

// Trigger CAC authentication
if (!isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Negotiate');
    exit;
}
    $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : '';

    if (empty($redirect_to)) {
        $redirect_option = get_option('cac_auth_redirect_page', 'wp-admin');
        $redirect_url = ($redirect_option === 'wp-admin') ? admin_url() : 
                        (($redirect_option === 'home') ? home_url() : get_permalink($redirect_option));
    } else {
        $redirect_url = $redirect_to;
    }

if (is_user_logged_in()) {
    error_log('CAC Auth: User is already logged in, skipping authentication');
    wp_redirect($redirect_url);
    exit;
}
// Process CAC authentication
cac_maybe_handle_authentication();

// If we reach here, authentication failed
wp_die('CAC authentication failed. Please try again or contact the site administrator.');