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

$_SESSION['SSL_CLIENT_S_DN_CN'] = $_SERVER['SSL_CLIENT_S_DN_CN'];

cac_maybe_handle_authentication();

// If we reach here and the user is logged in, redirect to the appropriate page
if (is_user_logged_in()) {
    error_log('CAC Auth Endpoint: User successfully authenticated');
    cac_auth_handle_redirection();
    exit;
}

// If we reach here, authentication failed
wp_die('CAC authentication failed. Please try again or contact the site administrator.');