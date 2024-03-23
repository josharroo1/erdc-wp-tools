<?php
/**
 * Plugin Name: WP CAC Sync (DoD)
 * Plugin URI: https://github.com/josharroo1/WP-DoD-CAC-User
 * Description: A WordPress plugin for CAC authentication and user synchronization for the DoD.
 * Version: 2.7.9
 * Author: Josh Arruda
 * Author URI: https://github.com/josharroo1/wpcac-sync-dod
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('CAC_AUTH_PLUGIN_VERSION', '2.7.9');
define('CAC_AUTH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAC_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once CAC_AUTH_PLUGIN_DIR . 'includes/cac-auth-functions.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/cac-registration-page.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/dev-sec.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin-functions.php';
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
    // Perform any necessary actions upon plugin activation
}

// Plugin deactivation callback
function cac_auth_plugin_deactivate() {
    // Perform any necessary cleanup upon plugin deactivation
}

// Enqueue plugin styles and scripts
add_action('wp_enqueue_scripts', 'cac_auth_plugin_enqueue_scripts');
function cac_auth_plugin_enqueue_scripts() {
    wp_enqueue_style('cac-auth-styles', CAC_AUTH_PLUGIN_URL . 'includes/assets/css/cac-auth-style.css', array(), CAC_AUTH_PLUGIN_VERSION);
    wp_enqueue_script('cac-auth-scripts', CAC_AUTH_PLUGIN_URL . 'includes/assets/js/cac-auth-scripts.js', array('jquery'), CAC_AUTH_PLUGIN_VERSION, true);
}