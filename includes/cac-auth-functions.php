<?php
/**
 * CAC Authentication Utility Functions
 */

 // Efficiently retrieves a user by their hashed DoD ID
 function cac_get_user_by_dod_id($hashed_dod_id) {
    global $wpdb;
    $user_id = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'hashed_dod_id' AND meta_value = %s LIMIT 1",
        $hashed_dod_id
    ));
    return $user_id ? get_user_by('ID', $user_id) : null;
}

// Extract DoD ID from CAC
function cac_extract_dod_id($dn) {
    $attributes = explode(',', $dn);
    $cn_parts = explode('.', $attributes[0]);
    $dod_id = end($cn_parts);
    return $dod_id;
}

// Extract names from CAC
function cac_extract_names($dn) {
    $attributes = explode(',', $dn);
    $cn = substr($attributes[0], 3);
    $cn_parts = explode('.', $cn);
    $last_name = ucwords(strtolower($cn_parts[0]));
    $first_name = ucwords(strtolower($cn_parts[1]));

    error_log("Extracted names: Last Name=$last_name, First Name=$first_name");
    return array('first_name' => $first_name, 'last_name' => $last_name);
}

// Generate a username based on names or email
function cac_generate_username($names, $email) {
    if ($names) {
        $username = strtolower($names['first_name'] . '_' . $names['last_name']);
    } else {
        // Fallback to generating username from email
        $username = strstr($email, '@', true);
    }
    $username = sanitize_user($username, true);
    $username = str_replace('.', '_', $username);

    // Ensure username is unique
    $suffix = 2; // Start suffix from 2 for better readability
    $base_username = $username;
    while (username_exists($username)) {
        $username = $base_username . $suffix++;
    }
    return $username;
}

// CAC Handle Authentication
    function cac_handle_authentication() {
        error_log('CAC Auth: Entering cac_handle_authentication');
    
        $cac_fallback_action = get_option('cac_auth_fallback_action', 'allow');
    
        if (!isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
            if ($cac_fallback_action === 'block' && !current_user_can('manage_options')) {
                wp_die('Access restricted. CAC authentication is required.');
            }
            return;
        }
    
        $dn = $_SERVER['SSL_CLIENT_S_DN_CN'];
        $dod_id = cac_extract_dod_id($dn);
        $names = cac_extract_names($dn);
    
        if (!$dod_id || !$names) {
            wp_die('Failed to verify CAC information');
        }
    
        $hashed_dod_id = hash('sha256', $dod_id);
    
        $user = cac_get_user_by_dod_id($hashed_dod_id);
    
        if ($user) {
            error_log('CAC Auth: User found');
            $user_status = get_user_meta($user->ID, 'user_status', true);
            $user_approval_required = get_option('cac_auth_user_approval', false);
    
            if ($user_approval_required && $user_status !== 'active') {
            $message = <<<HTML
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

            wp_die($message, 'Account Pending Approval', array('response' => 200));
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        $redirect_option = get_option('cac_auth_redirect_page', 'wp-admin');
        $redirect_url = ($redirect_option === 'wp-admin') ? admin_url() : 
                        (($redirect_option === 'home') ? home_url() : get_permalink($redirect_option));

        error_log('CAC Auth: Redirecting to ' . $redirect_url);
        wp_redirect($redirect_url);
        exit;
    } else {
        error_log('CAC Auth: User not found');
        $registration_page_id = get_option('cac_auth_registration_page');
        if ($registration_page_id) {
            $registration_url = add_query_arg('action', 'cac_register', get_permalink($registration_page_id));
            error_log('CAC Auth: Redirecting to registration page: ' . $registration_url);
            wp_redirect($registration_url);
            exit;
        } else {
            wp_die('CAC authentication failed. No registration page is set. Please contact the site administrator.');
        }
    }
}

function cac_maybe_handle_authentication() {
    error_log('CAC Auth: Entering cac_maybe_handle_authentication');

    if (get_option('cac_auth_enabled', 'yes') !== 'yes') {
        error_log('CAC Auth: CAC authentication is disabled');
        return;
    }

    if (is_user_logged_in()) {
        error_log('CAC Auth: User is already logged in');
        return;
    }

    $registration_page_id = get_option('cac_auth_registration_page');
    $current_page_id = get_queried_object_id();

    // Check if we're on the registration page or if the 'action' parameter is set to 'cac_register'
    if (($registration_page_id && $current_page_id == $registration_page_id) || 
        (isset($_GET['action']) && $_GET['action'] === 'cac_register')) {
        error_log('CAC Auth: On registration page or registration action, skipping authentication');
        return;
    }

    if (!isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
        error_log('CAC Auth: SSL_CLIENT_S_DN_CN is not set');
        return;
    }

    error_log('CAC Auth: Proceeding with authentication');
    cac_handle_authentication();
}
// Add new action to init hook with the wrapper function
add_action('init', 'cac_maybe_handle_authentication', 1);