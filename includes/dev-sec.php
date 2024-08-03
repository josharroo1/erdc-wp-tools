<?php

// Descriptions for Settings Page info
global $securityMitigationsDescriptions;
$securityMitigationsDescriptions = [
    'disable_password_autocomplete' => 'Disable Autocomplete on Login Password',
    'set_dynamic_httponly_cookies' => 'Add HTTPOnly or Secure to Cookies Dynamically',
    'remove_script_version' => 'Remove jQuery Version Information',
];

/**
 * Disable Autocomplete on Login Password
 * @SecurityMitigation
 */
function disable_password_autocomplete() {
    echo '<script>
        window.addEventListener("load", function(){
            var passwordFields = document.querySelectorAll("input[type=\'password\']");
            passwordFields.forEach(function(field){
                field.setAttribute("autocomplete", "off");
            });
        });
    </script>';
}
add_action('login_enqueue_scripts', 'disable_password_autocomplete');

/**
 * Add HTTPOnly or Secure to Cookies Dynamically
 * @SecurityMitigation
 */
function set_dynamic_httponly_cookies() {
    // Define a list of cookies to set with their names and values
    $cookies_to_set = array(
        'wordpress_test_cookie' => 'WP Cookie check',
        // Add more cookies here as 'cookie_name' => 'cookie_value'
    );

    foreach ($cookies_to_set as $name => $value) {
        // Check if the cookie is not already set to avoid unnecessary duplication
        if (!isset($_COOKIE[$name])) {
            // Set the cookie with HTTPOnly flag
            setcookie($name, $value, time() + 3600, '/', '', is_ssl(), true);
        }
    }
}
add_action('init', 'set_dynamic_httponly_cookies');

/**
 * Remove jQuery Version Information
 * @SecurityMitigation
 */
function remove_script_version($src) {
    return $src ? esc_url(remove_query_arg('ver', $src)) : false;
}
add_filter('script_loader_src', 'remove_script_version', PHP_INT_MAX);
add_filter('style_loader_src', 'remove_script_version', PHP_INT_MAX);

/**
 * Future Security Mitigations
 * 
 * This section is reserved for future security mitigation functions.
 * Follow the pattern below to add new security features:
 * 
 * // Description of the new mitigation
 * function new_security_mitigation() {
 *     // Implementation of the mitigation
 * }
 * add_action('appropriate_hook', 'new_security_mitigation');
 * 
 * Add a description in the $securityMitigationsDescriptions array at the top.
 */