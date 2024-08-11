<?php

// Descriptions for Settings Page info
global $securityMitigationsDescriptions;
$securityMitigationsDescriptions = [
    'disable_password_autocomplete' => 'Disable Autocomplete on Login Password',
    'set_dynamic_httponly_cookies' => 'Add HTTPOnly or Secure to Cookies Dynamically',
    'remove_script_version' => 'Remove jQuery Version Information',
    'restrict_direct_access' => 'Restricting Direct Access to Filetypes: zip|pdf|doc|docx|xls|xlsx',
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
        if (isset($_COOKIE[$name])) {
            // Reset the cookie with HTTPOnly and Secure flags
            setcookie($name, $value, time() + 3600, '/', '', is_ssl(), true);
        }
    }
}
add_action('init', 'set_dynamic_httponly_cookies', 1);

/**
 * Remove jQuery Version Information
 * @SecurityMitigation
 */
function remove_script_version($src) {
    return $src ? esc_url(remove_query_arg('ver', $src)) : false;
}
add_filter('script_loader_src', 'remove_script_version', PHP_INT_MAX);
add_filter('style_loader_src', 'remove_script_version', PHP_INT_MAX);