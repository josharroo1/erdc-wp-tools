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

// Handle CAC authentication
function cac_handle_authentication() {
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
        // Handle error if DoD ID or name extraction fails
        wp_die('Failed to verify CAC information');
    }

    // Hash the DoD ID
    $hashed_dod_id = hash('sha256', $dod_id);

    // Check if the user exists in WordPress based on the hashed DoD ID
    $user = cac_get_user_by_dod_id($hashed_dod_id);

    if ($user) {
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

        // Log the user in
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);

        // Get the selected redirect option
        $redirect_option = get_option('cac_auth_redirect_page', 'wp-admin');

        // Determine the redirect URL
        if ($redirect_option === 'wp-admin') {
            $redirect_url = admin_url();
        } elseif ($redirect_option === 'home') {
            $redirect_url = home_url();
        } else {
            $redirect_url = get_permalink($redirect_option);
        }

        // Prevent redirect loops
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        if ($current_url !== $redirect_url) {
            wp_safe_redirect($redirect_url);
            exit;
        }
    } else {
        // Get the selected registration page ID from the plugin settings
        $registration_page_id = get_option('cac_auth_registration_page');

        if ($registration_page_id && !is_page($registration_page_id)) {
            // Redirect to the selected registration page if the user is not already on it
            wp_redirect(get_permalink($registration_page_id));
            exit;
        } elseif (!$registration_page_id && !is_front_page()) {
            // Redirect to the home page if no registration page is selected and the user is not already on the front page
            wp_redirect(home_url());
            exit;
        }
    }
}

function cac_maybe_handle_authentication() {
    // Only proceed if CAC authentication is enabled
    if (get_option('cac_auth_enabled', 'yes') !== 'yes') {
        return;
    }

    // Only proceed for non-logged in users
    if (is_user_logged_in()) {
        return;
    }

    // Only proceed if SSL_CLIENT_S_DN_CN is set (indicating a CAC connection attempt)
    if (!isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
        return;
    }

    // Now we can safely call the authentication function
    cac_handle_authentication();
}

// Add new action to init hook with the wrapper function
add_action('init', 'cac_maybe_handle_authentication', 1);