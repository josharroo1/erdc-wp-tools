<?php
/**
 * CAC Authentication Utility Functions
 */

// Efficiently retrieves a user by their hashed DoD ID
function cac_get_user_by_dod_id($hashed_dod_id) {
    global $wpdb;
    if (!is_string($hashed_dod_id)) {
        return null;
    }

    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'hashed_dod_id' AND meta_value = %s LIMIT 1",
        $hashed_dod_id
    ));

    return $user_id ? get_user_by('ID', $user_id) : null;
}

// Extract DoD ID from CAC
function cac_extract_dod_id($dn) {
    if (!is_string($dn)) {
        return null;
    }

    $attributes = explode(',', sanitize_text_field($dn));
    if (isset($attributes[0])) {
        $cn_parts = explode('.', sanitize_text_field($attributes[0]));
        return end($cn_parts);
    }
    return null;
}

// Extract names from CAC
function cac_extract_names($dn) {
    if (!is_string($dn)) {
        return array('first_name' => '', 'last_name' => '');
    }

    // Split DN to get the CN part
    $attributes = explode(',', sanitize_text_field($dn));
    $cn = sanitize_text_field($attributes[0]);  // Assuming CN is the first attribute

    // Remove the "CN = " prefix if present
    $cn = preg_replace('/^CN\s*=\s*/', '', $cn);

    // Split the CN by periods
    $cn_parts = explode('.', $cn);

    // Ensure we have at least 2 parts (last name and first name)
    if (count($cn_parts) < 2) {
        return array('first_name' => '', 'last_name' => '');
    }

    $last_name = ucwords(strtolower(sanitize_text_field($cn_parts[0])));
    $first_name = ucwords(strtolower(sanitize_text_field($cn_parts[1])));

    // Log extracted names for debugging
    error_log("Extracted names: Last Name=$last_name, First Name=$first_name");

    return array('first_name' => $first_name, 'last_name' => $last_name);
}

// Generate a username based on names or email
function cac_generate_username($names, $email) {
    $username = '';

    if (!empty($names['first_name']) && !empty($names['last_name'])) {
        $username = strtolower(sanitize_user($names['first_name'] . '_' . $names['last_name'], true));
    } elseif (!empty($email)) {
        // Fallback to generating username from email
        $username = sanitize_user(strstr($email, '@', true), true);
    }

    $username = str_replace('.', '_', $username);

    // Ensure username is unique
    $suffix = 2; // Start suffix from 2 for better readability
    $base_username = $username;
    while (username_exists($username)) {
        $username = $base_username . $suffix++;
    }

    return $username;
}

// Handle CAC authentication if conditions are met
function cac_maybe_handle_authentication() {
    error_log('CAC Auth: Entering cac_maybe_handle_authentication');

    $registration_page_id = intval(get_option('cac_auth_registration_page'));
    $current_page_id = get_queried_object_id();

    if (get_option('cac_auth_enabled', 'yes') !== 'yes') {
        error_log('CAC Auth: CAC authentication is disabled');
        return;
    }

    // Check if we're on the registration page
    if ($registration_page_id && $current_page_id == $registration_page_id) {
        error_log('CAC Auth: On registration page, skipping authentication');
        return;
    }

    if (!isset($_SERVER['SSL_CLIENT_S_DN_CN']) && !isset($_SESSION['SSL_CLIENT_S_DN_CN'])) {
        error_log('CAC Auth: SSL_CLIENT_S_DN_CN is not set');
        return;
    }

    $dn = isset($_SERVER['SSL_CLIENT_S_DN_CN']) ? sanitize_text_field($_SERVER['SSL_CLIENT_S_DN_CN']) : sanitize_text_field($_SESSION['SSL_CLIENT_S_DN_CN']);
    $_SESSION['SSL_CLIENT_S_DN_CN'] = $dn;
    $dod_id = cac_extract_dod_id($dn);

    if (!$dod_id) {
        error_log('CAC Auth: DoD ID extraction failed');
        return;
    }

    $hashed_dod_id = hash('sha256', $dod_id);
    $user = cac_get_user_by_dod_id($hashed_dod_id);

    if ($user) {
        // Check user status
        $user_status = get_user_meta($user->ID, 'user_status', true);
        $user_approval_required = get_option('cac_auth_user_approval', false);
        if ($user_approval_required && $user_status !== 'active') {
            wp_die(cac_get_pending_approval_message(), 'Account Pending Approval', array('response' => 200));
        }
        // Proceed with authentication for existing user
        cac_handle_authentication($user);
        return true; // Indicate successful authentication
    } else {
        // User not found, redirect to registration
        error_log('CAC Auth: User not found, redirecting to registration');
        if ($registration_page_id) {
            wp_safe_redirect(get_permalink($registration_page_id));
            exit;
        } else {
            wp_die('CAC authentication failed. No registration page is set. Please contact the site administrator.');
        }
    }

    return false; // Indicate failed authentication
}

// Handle authentication for a user
function cac_handle_authentication($user) {
    error_log('CAC Auth: Entering cac_handle_authentication');

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);

    $current_time = time();
    update_user_meta($user->ID, 'last_login', $current_time);
    update_user_meta($user->ID, 'wfls-last-login', $current_time);
}

// Get pending approval message
function cac_get_pending_approval_message() {
    return <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Account Pending Approval</title>
    <style>
        html, body {
            margin: 0 !important;
            padding: 0 !important;
            height: 100%;
            background: none !important;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            font-family: Arial, sans-serif;
            border: none !important;
        }
        #custom-error {
            width: 80%;
            max-width: 600px;
            padding: 20px;
            background: white;
            color: #333;
            box-shadow: none;
        }
    </style>
    </head>
    <body>
    <div id="custom-error">
        <h1 style="font-weight: bold;">Pending Approval</h1>
        <p>Your account is pending approval. Please be patient as no further action is required from you at this point. An administrator will review and approve your account shortly.</p>
    </div>
    </body>
    </html>
    HTML;
}

// Add "Login with CAC" button to the login form
function cac_auth_add_login_button() {
    $cac_enabled = get_option('cac_auth_enabled', 'yes');
    if ($cac_enabled === 'yes') {
        $cac_auth_url = plugins_url('cac-auth-endpoint.php', dirname(__FILE__));
        echo '<div class="cac-login-button-wrapper">';
        echo '<a href="' . esc_url($cac_auth_url) . '" class="button button-primary cac-login-button">Login with CAC</a>';
        echo '</div>';
    }
}
add_action('login_form', 'cac_auth_add_login_button');

// Enqueue login page styles
function cac_auth_enqueue_login_styles() {
    wp_enqueue_style('cac-auth-login-styles', plugins_url('includes/assets/css/cac-auth-login.css', __FILE__), array(), '1.0.0');
}
add_action('login_enqueue_scripts', 'cac_auth_enqueue_login_styles');

// Start session if not already started
function cac_start_session() {
    $cookie_params = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'], // dynamically set the domain
        'secure' => is_ssl(), // ensures the cookie is secure only if HTTPS is used
        'httponly' => true,
        'samesite' => 'Strict' // Or 'Lax' depending on your requirement
    ];

    if (!session_id()) {
        // Start the session with the specified cookie parameters
        session_set_cookie_params($cookie_params);
        session_start();
    } else {
        // Modify the existing session cookie to match the new parameters
        setcookie(session_name(), session_id(), [
            'expires' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => is_ssl(),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
}
add_action('init', 'cac_start_session', 1);

// Custom login page styles
function login_style_changer() {
    echo '<style>
    .forgetmenot { display: none !important; }
    .button-primary {
        background: black !important;
        border: none !important;
        margin: 0px 10px !important;
        height: 35px !important;
        font-family: Arial !important;
    }
    .cac-login-button {
        line-height: 2.6em !important;
    }
    .cac-login-button-wrapper {
        margin-right: -9px;
        margin-top: 12px;
    }
    </style>';
}
add_action('login_head', 'login_style_changer');

// BEGIN NEW CAC REDIRECTION LOGIC
function cac_auth_handle_redirection() {
    $cac_enabled = get_option('cac_auth_enabled', 'yes') === 'yes';
    $site_wide_restriction = get_option('cac_auth_site_wide_restriction', false);
    $enable_post_restriction = get_option('cac_auth_enable_post_restriction', false);
    $current_url = esc_url_raw((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    // Check if user is already logged in
    if (!is_user_logged_in()) {
        // User is not logged in, check if we need to redirect to CAC login
        if ($cac_enabled) {
            if ($site_wide_restriction) {
                cac_auth_redirect_to_cac_login();
            } elseif ($enable_post_restriction && is_singular()) {
                $post_id = get_the_ID();
                $requires_cac = get_post_meta($post_id, '_requires_cac_auth', true);
                if ($requires_cac) {
                    cac_auth_redirect_to_cac_login();
                }
            }
        }
        return; // Exit if not logged in and no redirection occurred
    }

    // User is logged in, handle redirection
    $intended_destination = isset($_SESSION['cac_auth_intended_destination']) ? esc_url_raw($_SESSION['cac_auth_intended_destination']) : '';
    $pending_download = isset($_SESSION['cac_auth_intended_download']) ? sanitize_file_name($_SESSION['cac_auth_intended_download']) : '';
    $referring_page = isset($_SESSION['cac_auth_referring_page']) ? esc_url_raw($_SESSION['cac_auth_referring_page']) : '';

    // Clear all redirection-related session variables
    unset($_SESSION['cac_auth_intended_destination'], $_SESSION['cac_auth_intended_download'], $_SESSION['cac_auth_referring_page']);

    if ($pending_download && $referring_page) {
        $redirect_url = add_query_arg(array('file_download' => $pending_download), $referring_page);
        wp_safe_redirect($redirect_url);
        exit;
    } elseif ($intended_destination && !$site_wide_restriction) {
        wp_safe_redirect($intended_destination);
        exit;
    } else {
        $redirect_option = get_option('cac_auth_redirect_page', 'wp-admin');
        $redirect_url = ($redirect_option === 'wp-admin') ? admin_url() : 
                        (($redirect_option === 'home') ? home_url() : get_permalink(intval($redirect_option)));
        wp_safe_redirect($redirect_url);
        exit;
    }
}

function cac_auth_redirect_to_cac_login() {
    $cac_auth_url = plugins_url('cac-auth-endpoint.php', dirname(__FILE__));
    $current_url = esc_url_raw((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    // Set the intended destination in the session
    $_SESSION['cac_auth_intended_destination'] = $current_url;

    wp_safe_redirect($cac_auth_url);
    exit;
}
