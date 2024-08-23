<?php

// Descriptions for Settings Page info
global $securityMitigationsDescriptions;
$securityMitigationsDescriptions = [
    'disable_password_autocomplete' => 'Disable Autocomplete on Login Password',
    'set_dynamic_httponly_cookies' => 'Add HTTPOnly or Secure to Cookies Dynamically',
    'remove_script_version' => 'Remove jQuery Version Information',
    'restrict_direct_access' => 'Restricting Direct Access to Filetypes: zip|pdf|doc|docx|xls|xlsx|exe|msi',
    'elementor_form_protection' => 'Protection Elementor Forms with CSRF',

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

/**
 * Add CSRF to Elementor Forms
 * @SecurityMitigation
 */
if ( did_action( 'elementor/loaded' ) ) {
function elementor_csrf_protection_init() {
    add_action('wp_footer', 'add_csrf_script', 100);
    add_action('elementor_pro/forms/validation', 'validate_csrf_token', 10, 2);
}
add_action('init', 'elementor_csrf_protection_init');

// Generate a cryptographically secure token
function generate_secure_token() {
    $token = bin2hex(random_bytes(32)); // 256-bit token
    $expiration = time() + 300; // 5 minutes expiration
    set_transient('elementor_form_csrf_' . $token, $expiration, 300);
    return $token;
}

// Add JavaScript to inject CSRF field
function add_csrf_script() {
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var forms = document.querySelectorAll('.elementor-form');
        forms.forEach(function(form) {
            if (!form.querySelector('input[name="elementor_form_csrf"]')) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '<?php echo admin_url('admin-ajax.php?action=get_csrf_token'); ?>', true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'elementor_form_csrf';
                        input.value = xhr.responseText;
                        form.appendChild(input);
                    }
                };
                xhr.send();
            }
        });
    });
    </script>
    <?php
}

// AJAX handler to generate a new token
function get_csrf_token() {
    echo generate_secure_token();
    wp_die();
}
add_action('wp_ajax_get_csrf_token', 'get_csrf_token');
add_action('wp_ajax_nopriv_get_csrf_token', 'get_csrf_token');

// Validate CSRF token
function validate_csrf_token($record, $ajax_handler) {
    $token = isset($_POST['elementor_form_csrf']) ? sanitize_text_field($_POST['elementor_form_csrf']) : '';
    $transient_key = 'elementor_form_csrf_' . $token;
    
    $expiration = get_transient($transient_key);
    
    if ($expiration && $expiration > time()) {
        $deleted = delete_transient($transient_key);
        
        if ($deleted) {
            log_csrf_event('success', $token);
        } else {
            $ajax_handler->add_error_message('Security token already used. Please try again.');
            $ajax_handler->set_success(false);
            $ajax_handler->add_response_data('csrf_error', true);
            log_csrf_event('reuse_attempt', $token);
            halt_form_submission($ajax_handler);
        }
    } else {
        $ajax_handler->add_error_message('Invalid or expired security token. Please refresh the page and try again.');
        $ajax_handler->set_success(false);
        $ajax_handler->add_response_data('csrf_error', true);
        log_csrf_event('failure', $token);
        halt_form_submission($ajax_handler);
    }
}

// Halt form submission
function halt_form_submission($ajax_handler) {
    add_action('elementor_pro/forms/process', function($record, $ajax_handler) {
        $ajax_handler->send();
        die();
    }, 1, 2);
}

// Log CSRF events
function log_csrf_event($status, $token) {
    $log_entry = sprintf(
        "[%s] CSRF %s: Token=%s, IP=%s, User=%s, URL=%s",
        current_time('mysql'),
        $status,
        substr($token, 0, 8) . '...',  // Log only part of the token for security
        $_SERVER['REMOTE_ADDR'],
        is_user_logged_in() ? wp_get_current_user()->user_login : 'anonymous',
        $_SERVER['REQUEST_URI']
    );
    error_log($log_entry);
}
}