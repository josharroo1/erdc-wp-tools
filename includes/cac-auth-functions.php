<?php
/**
 * CAC Authentication Utility Functions
 */

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
    $cac_enabled = get_option('cac_auth_enabled', 'yes');
    $cac_fallback_action = get_option('cac_auth_fallback_action', 'allow');

    if ($cac_enabled === 'yes' && !isset($_SERVER['SSL_CLIENT_S_DN_CN'])) {
        if ($cac_fallback_action === 'block' && !current_user_can('manage_options')) {
            wp_die('Access restricted. CAC authentication is required.');
        }
        return;
    }

    if (isset($_SERVER['SSL_CLIENT_S_DN_CN']) && !is_user_logged_in()) {
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
        $user = get_users(array('meta_key' => 'hashed_dod_id', 'meta_value' => $hashed_dod_id));

        if (empty($user)) {
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
        } else {
            // Log the user in
            wp_set_current_user($user[0]->ID);
            wp_set_auth_cookie($user[0]->ID);

            // Get the selected redirect page ID
            $redirect_page_id = get_option('cac_auth_redirect_page');

            if ($redirect_page_id && !is_page($redirect_page_id)) {
                // Redirect to the selected page if the user is not already on it
                wp_redirect(get_permalink($redirect_page_id));
                exit;
            } elseif (!$redirect_page_id && !is_front_page()) {
                // Redirect to the home page if no redirect page is selected and the user is not already on the front page
                wp_redirect(home_url());
                exit;
            }
        }
    }
}
add_action('template_redirect', 'cac_handle_authentication');

function cac_restrict_registration_page() {
    $registration_page_id = get_option('cac_auth_registration_page');
    $cac_enabled = get_option('cac_auth_enabled', 'yes');
    $cac_fallback_action = get_option('cac_auth_fallback_action', 'allow');
    $is_cac_page = $registration_page_id && is_page($registration_page_id);

    // Conditions to redirect:
    // 1. If it's the CAC registration page and a user is logged in without admin rights.
    // 2. If it's the CAC registration page, CAC is enabled, fallback is 'allow', no CN is set, and the user is not logged in.
    if ($is_cac_page && is_user_logged_in() && !current_user_can('manage_options')) {
        cac_perform_redirect();
    } elseif ($is_cac_page && $cac_enabled === 'yes' && $cac_fallback_action === 'allow' && !isset($_SERVER['SSL_CLIENT_S_DN_CN']) && !is_user_logged_in()) {
        cac_perform_redirect();
    }
}

// Helper function to perform the redirect to avoid code duplication
function cac_perform_redirect() {
    $redirect_page_id = get_option('cac_auth_redirect_page');

    if ($redirect_page_id) {
        wp_redirect(get_permalink($redirect_page_id));
    } else {
        wp_redirect(home_url());
    }

    exit;
}

add_action('template_redirect', 'cac_restrict_registration_page');
