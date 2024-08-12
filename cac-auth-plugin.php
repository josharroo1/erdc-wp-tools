<?php
/**
 * Plugin Name: ERDC WP Tools
 * Plugin URI: https://github.com/josharroo1/WP-DoD-CAC-User
 * Description: A suite of tools for managing WordPress within USACE ERDC.
 * Version: 4.5.0
 * Author: Josh Arruda
 * Author URI: https://github.com/josharroo1/wpcac-sync-dod
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * 
 * TODO: General plugin-wide todos can be listed here in the main plugin file header.
 * - Handle general user login redirection (non-CAC)
 * - Handle lack of redirection after an authenticated file download
 */

// Abort if this file is called directly
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CAC_AUTH_PLUGIN_VERSION', '4.5.0');
define('CAC_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAC_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once CAC_AUTH_PLUGIN_DIR . 'includes/cac-auth-functions.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/cac-registration-page.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/dev-sec.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin-functions.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-user-list.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/post-columns.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/comment-control.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/cac-auth-restrictions.php';

// Plugin update checker
require_once CAC_AUTH_PLUGIN_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://raw.githubusercontent.com/josharroo1/wpcac-sync-dod/main/cac-auth-plugin-update.json',
    __FILE__,
    'cac-auth-plugin'
);

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'cac_auth_plugin_activate');
register_deactivation_hook(__FILE__, 'cac_auth_plugin_deactivate');

// Plugin activation callback
function cac_auth_plugin_activate() {
    // Modify .htaccess file
    cac_auth_modify_htaccess();
}

// Plugin deactivation callback
function cac_auth_plugin_deactivate() {
    // Remove our rules from .htaccess file
    cac_auth_cleanup_htaccess();
}

// Function to modify .htaccess on plugin activation
function cac_auth_modify_htaccess() {
    $htaccess_rules = "
# Protect the CAC auth endpoint
<Files \"cac-auth-endpoint.php\">
    SSLOptions +StdEnvVars
    SSLVerifyClient require
    SSLVerifyDepth 2
    SSLRequire %{SSL_CLIENT_VERIFY} eq \"SUCCESS\"
</Files>
# Block direct access to specific file types in uploads directory
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_URI} \.(zip|pdf|doc|docx|xls|xlsx|exe|msi)$ [NC]
RewriteRule . - [R=403,L]
</IfModule>
";

    $htaccess_file = ABSPATH . '.htaccess';
    $htaccess_content = '';

    if (file_exists($htaccess_file)) {
        $htaccess_content = file_get_contents($htaccess_file);
    }

    if (strpos($htaccess_content, $htaccess_rules) === false) {
        $htaccess_content .= $htaccess_rules;
        if (file_put_contents($htaccess_file, $htaccess_content) === false) {
            error_log('CAC Auth: Failed to modify .htaccess file');
        } else {
            error_log('CAC Auth: Successfully modified .htaccess file');
        }
    } else {
        error_log('CAC Auth: .htaccess rules already present');
    }
}

// Function to remove our rules from .htaccess on plugin deactivation
function cac_auth_cleanup_htaccess() {
    $htaccess_file = ABSPATH . '.htaccess';
    if (file_exists($htaccess_file)) {
        $htaccess_content = file_get_contents($htaccess_file);
        $htaccess_rules = "
# Protect the CAC auth endpoint
<Files \"cac-auth-endpoint.php\">
    SSLOptions +StdEnvVars
    SSLVerifyClient require
    SSLVerifyDepth 2
    SSLRequire %{SSL_CLIENT_VERIFY} eq \"SUCCESS\"
</Files>
# Block direct access to specific file types in uploads directory
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_URI} \.(zip|pdf|doc|docx|xls|xlsx|exe|msi)$ [NC]
RewriteRule . - [R=403,L]
</IfModule>
";
        $htaccess_content = str_replace($htaccess_rules, '', $htaccess_content);
        if (file_put_contents($htaccess_file, $htaccess_content) === false) {
            error_log('CAC Auth: Failed to remove rules from .htaccess file on deactivation');
        } else {
            error_log('CAC Auth: Successfully removed rules from .htaccess file on deactivation');
        }
    }
}

// Enqueue plugin styles and scripts
add_action('wp_enqueue_scripts', 'cac_auth_plugin_enqueue_scripts');
function cac_auth_plugin_enqueue_scripts() {
    wp_enqueue_style('cac-auth-styles', esc_url(CAC_AUTH_PLUGIN_URL . 'includes/assets/css/cac-auth-style.css'), array(), CAC_AUTH_PLUGIN_VERSION);
    wp_enqueue_script('cac-auth-scripts', esc_url(CAC_AUTH_PLUGIN_URL . 'includes/assets/js/cac-auth-scripts.js'), array('jquery'), CAC_AUTH_PLUGIN_VERSION, true);
}

// Enqueue Select2 library
add_action('wp_enqueue_scripts', 'cac_enqueue_select2');
function cac_enqueue_select2() {
    wp_enqueue_style('select2', esc_url('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css'));
    wp_enqueue_script('select2', esc_url('https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js'), array('jquery'), '4.0.13', true);
}

// Add custom settings link to the plugin
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'cac_auth_add_settings_link');
function cac_auth_add_settings_link($links) {
    // Construct the settings link
    $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=cac-auth-settings')) . '">' . esc_html__('Settings', 'your-text-domain') . '</a>';
    
    // Add the settings link to the beginning of the links array
    array_unshift($links, $settings_link);
    
    return $links;
}