<?php
/**
 * CAC Authentication Admin Settings
 */
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin-functions.php';

// Add CAC Authentication settings page
function cac_auth_add_settings_page() {
    add_options_page(
        'WP CAC Block Settings',
        'WP CAC Block',
        'manage_options',
        'cac-auth-settings',
        'cac_auth_render_settings_page'
    );
}
add_action('admin_menu', 'cac_auth_add_settings_page');

// Render CAC Authentication settings page
function cac_auth_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('cac_auth_settings');
            do_settings_sections('cac-auth-settings');
            submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}

// Register CAC Authentication settings
function cac_auth_register_settings() {
    register_setting('cac_auth_settings', 'cac_auth_redirect_page');
    register_setting('cac_auth_settings', 'cac_auth_registration_page');
    register_setting('cac_auth_settings', 'cac_auth_enabled');
    register_setting('cac_auth_settings', 'cac_auth_fallback_action');
    register_setting('cac_auth_settings', 'cac_auth_registration_fields', array(
        'sanitize_callback' => 'cac_auth_save_custom_fields',
    ));


    add_settings_section(
        'cac_auth_general_section',
        'General Settings',
        'cac_auth_general_section_callback',
        'cac-auth-settings'
    );

    add_settings_field(
        'cac_auth_enabled',
        'Enable CAC Authentication',
        'cac_auth_enabled_callback',
        'cac-auth-settings',
        'cac_auth_general_section'
    );

    add_settings_field(
        'cac_auth_fallback_action',
        'Fallback Action',
        'cac_auth_fallback_action_callback',
        'cac-auth-settings',
        'cac_auth_general_section'
    );

    add_settings_section(
        'cac_auth_redirect_section',
        'Redirection Settings',
        'cac_auth_redirect_section_callback',
        'cac-auth-settings'
    );

    add_settings_field(
        'cac_auth_registration_page',
        'Registration Page',
        'cac_auth_registration_page_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
    );

    add_settings_field(
        'cac_auth_redirect_page',
        'Redirect Page',
        'cac_auth_redirect_page_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
    );

    add_settings_section(
        'cac_auth_custom_fields_section',
        'Custom Registration Fields',
        'cac_auth_custom_fields_section_callback',
        'cac-auth-settings'
    );

    add_settings_field(
        'cac_auth_custom_fields',
        'Custom Fields',
        'cac_auth_render_custom_fields',
        'cac-auth-settings',
        'cac_auth_custom_fields_section'
    );

    // Usage Instructions section
    add_settings_section(
        'cac_auth_usage_section',
        'Usage Instructions',
        'cac_auth_usage_section_callback',
        'cac-auth-settings'
    );
}
add_action('admin_init', 'cac_auth_register_settings');

// Redirect section callback
function cac_auth_redirect_section_callback() {
    echo '<p>Select the page to redirect users to after successful CAC authentication.</p>';
}

// Redirect page callback
function cac_auth_redirect_page_callback() {
    $selected_page = get_option('cac_auth_redirect_page');
    wp_dropdown_pages(array(
        'name' => 'cac_auth_redirect_page',
        'echo' => 1,
        'show_option_none' => '&mdash; Select &mdash;',
        'option_none_value' => '0',
        'selected' => $selected_page,
    ));
}

// Registration page callback
function cac_auth_registration_page_callback() {
    $selected_page = get_option('cac_auth_registration_page');
    wp_dropdown_pages(array(
        'name' => 'cac_auth_registration_page',
        'echo' => 1,
        'show_option_none' => '&mdash; Select &mdash;',
        'option_none_value' => '0',
        'selected' => $selected_page,
    ));
}

// CAC Registration Form usage callback
function cac_auth_usage_section_callback() {
    ?>
    <p>To display the CAC registration form on a page or post, use the following shortcode:</p>
    <code>[cac_registration]</code>
    <p>Simply place this shortcode in the content of the desired page or post where you want the registration form to appear.</p>
    <p>Users will be able to fill out the registration form, including any custom fields you have defined, and submit it to register using their CAC credentials.</p>
    <?php
}

// Enable CAC Authentication callback
function cac_auth_enabled_callback() {
    $enabled = get_option('cac_auth_enabled', 'yes');
    ?>
    <select name="cac_auth_enabled">
        <option value="yes" <?php selected($enabled, 'yes'); ?>>Yes</option>
        <option value="no" <?php selected($enabled, 'no'); ?>>No</option>
    </select>
    <?php
}

// Fallback Action callback
function cac_auth_fallback_action_callback() {
    $fallback_action = get_option('cac_auth_fallback_action', 'allow');
    ?>
    <select name="cac_auth_fallback_action">
        <option value="allow" <?php selected($fallback_action, 'allow'); ?>>Allow access</option>
        <option value="block" <?php selected($fallback_action, 'block'); ?>>Block access for non-admins</option>
    </select>
    <?php
}

// General section callback
function cac_auth_general_section_callback() {
    echo '<p>Configure the general settings for CAC authentication.</p>';
}