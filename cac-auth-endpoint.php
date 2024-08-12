<?php
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

// Ensure this file is being accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Force SSL
if (!is_ssl()) {
    wp_safe_redirect('https://' . sanitize_text_field($_SERVER['HTTP_HOST']) . sanitize_text_field($_SERVER['REQUEST_URI']), 301);
    exit();
}

// Start session if not already started
if (!session_id()) {
    session_start([
        'cookie_lifetime' => 0,
        'read_and_close'  => false,
        'cookie_secure'   => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
    ]);
}

// Trigger CAC authentication
if (!isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Negotiate');
    exit;
}

// Sanitize and store the DN string in session
$_SESSION['SSL_CLIENT_S_DN_CN'] = sanitize_text_field($_SERVER['SSL_CLIENT_S_DN_CN']);

// Trigger CAC authentication
if (cac_maybe_handle_authentication()) {
    // Authentication successful, handle redirection
    cac_auth_handle_redirection();
} else {
    // Authentication failed
    wp_die(esc_html__('CAC authentication failed. Please try again or contact the site administrator.', 'your-text-domain'));
}