<?php
/**
 * CAC Authentication Admin Settings
 */
require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin-functions.php';

// Add CAC Authentication settings page
function cac_auth_add_settings_page() {
    add_options_page(
        'WP CAC Sync Settings',
        'WP CAC Sync',
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
        <div class="settings-header">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M308.5 135.3c7.1-6.3 9.9-16.2 6.2-25c-2.3-5.3-4.8-10.5-7.6-15.5L304 89.4c-3-5-6.3-9.9-9.8-14.6c-5.7-7.6-15.7-10.1-24.7-7.1l-28.2 9.3c-10.7-8.8-23-16-36.2-20.9L199 27.1c-1.9-9.3-9.1-16.7-18.5-17.8C173.9 8.4 167.2 8 160.4 8h-.7c-6.8 0-13.5 .4-20.1 1.2c-9.4 1.1-16.6 8.6-18.5 17.8L115 56.1c-13.3 5-25.5 12.1-36.2 20.9L50.5 67.8c-9-3-19-.5-24.7 7.1c-3.5 4.7-6.8 9.6-9.9 14.6l-3 5.3c-2.8 5-5.3 10.2-7.6 15.6c-3.7 8.7-.9 18.6 6.2 25l22.2 19.8C32.6 161.9 32 168.9 32 176s.6 14.1 1.7 20.9L11.5 216.7c-7.1 6.3-9.9 16.2-6.2 25c2.3 5.3 4.8 10.5 7.6 15.6l3 5.2c3 5.1 6.3 9.9 9.9 14.6c5.7 7.6 15.7 10.1 24.7 7.1l28.2-9.3c10.7 8.8 23 16 36.2 20.9l6.1 29.1c1.9 9.3 9.1 16.7 18.5 17.8c6.7 .8 13.5 1.2 20.4 1.2s13.7-.4 20.4-1.2c9.4-1.1 16.6-8.6 18.5-17.8l6.1-29.1c13.3-5 25.5-12.1 36.2-20.9l28.2 9.3c9 3 19 .5 24.7-7.1c3.5-4.7 6.8-9.5 9.8-14.6l3.1-5.4c2.8-5 5.3-10.2 7.6-15.5c3.7-8.7 .9-18.6-6.2-25l-22.2-19.8c1.1-6.8 1.7-13.8 1.7-20.9s-.6-14.1-1.7-20.9l22.2-19.8zM112 176a48 48 0 1 1 96 0 48 48 0 1 1 -96 0zM504.7 500.5c6.3 7.1 16.2 9.9 25 6.2c5.3-2.3 10.5-4.8 15.5-7.6l5.4-3.1c5-3 9.9-6.3 14.6-9.8c7.6-5.7 10.1-15.7 7.1-24.7l-9.3-28.2c8.8-10.7 16-23 20.9-36.2l29.1-6.1c9.3-1.9 16.7-9.1 17.8-18.5c.8-6.7 1.2-13.5 1.2-20.4s-.4-13.7-1.2-20.4c-1.1-9.4-8.6-16.6-17.8-18.5L583.9 307c-5-13.3-12.1-25.5-20.9-36.2l9.3-28.2c3-9 .5-19-7.1-24.7c-4.7-3.5-9.6-6.8-14.6-9.9l-5.3-3c-5-2.8-10.2-5.3-15.6-7.6c-8.7-3.7-18.6-.9-25 6.2l-19.8 22.2c-6.8-1.1-13.8-1.7-20.9-1.7s-14.1 .6-20.9 1.7l-19.8-22.2c-6.3-7.1-16.2-9.9-25-6.2c-5.3 2.3-10.5 4.8-15.6 7.6l-5.2 3c-5.1 3-9.9 6.3-14.6 9.9c-7.6 5.7-10.1 15.7-7.1 24.7l9.3 28.2c-8.8 10.7-16 23-20.9 36.2L315.1 313c-9.3 1.9-16.7 9.1-17.8 18.5c-.8 6.7-1.2 13.5-1.2 20.4s.4 13.7 1.2 20.4c1.1 9.4 8.6 16.6 17.8 18.5l29.1 6.1c5 13.3 12.1 25.5 20.9 36.2l-9.3 28.2c-3 9-.5 19 7.1 24.7c4.7 3.5 9.5 6.8 14.6 9.8l5.4 3.1c5 2.8 10.2 5.3 15.5 7.6c8.7 3.7 18.6 .9 25-6.2l19.8-22.2c6.8 1.1 13.8 1.7 20.9 1.7s14.1-.6 20.9-1.7l19.8 22.2zM464 304a48 48 0 1 1 0 96 48 48 0 1 1 0-96z"/></svg>
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        </div>
        <form action="options.php" method="post" enctype="multipart/form-data">
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
    register_setting('cac_auth_settings', 'cac_auth_default_role');
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
        'Enable WP CAC Sync?',
        'cac_auth_enabled_callback',
        'cac-auth-settings',
        'cac_auth_general_section'
    );

    add_settings_field(
        'cac_auth_fallback_action',
        'Non-CAC Fallback',
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
        'User Account Page',
        'cac_auth_redirect_page_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
    );

    add_settings_section(
        'cac_auth_registration_settings_section',
        'Registration Settings',
        'cac_auth_registration_settings_section_callback',
        'cac-auth-settings'
    );

    add_settings_field(
        'cac_auth_default_role',
        'Default User Role',
        'cac_auth_default_role_callback',
        'cac-auth-settings',
        'cac_auth_registration_settings_section'
    );
    add_settings_section(
        'cac_auth_custom_fields_section',
        'CAC Registration Fields',
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
        'CAC Registration Form Instructions',
        'cac_auth_usage_section_callback',
        'cac-auth-settings'
    );

    add_settings_section(
        'cac_auth_security_section', // Section ID
        'Security Mitigations', // Section title
        'cac_auth_security_section_callback', // Callback function
        'cac-auth-settings' // Page to add the section to
    );
    
}
add_action('admin_init', 'cac_auth_register_settings');

//Observe Security Mitigations
function cac_auth_extract_security_mitigations() {
    // Ensure the path is correctly formed with the CAC_AUTH_PLUGIN_DIR constant
    $fileName = CAC_AUTH_PLUGIN_DIR . 'includes/dev-sec.php'; // Correct path to dev-sec.php

    if (!file_exists($fileName)) {
        // Handle the error appropriately if the file does not exist
        return [];
    }

    $fileContents = file_get_contents($fileName);
    $tokens = token_get_all($fileContents);
    $functions = array();

    $nextStringIsFunction = false;
    $docComment = '';

    foreach ($tokens as $token) {
        if (is_array($token)) {
            list($id, $text) = $token;

            if ($id == T_DOC_COMMENT && strpos($text, '@SecurityMitigation') !== false) {
                // Extract the first line of the doc comment as the description
                if (preg_match('/\*\s*(.*?)\n/', $text, $matches)) {
                    $docComment = trim($matches[1]);
                }
            }

            if ($id == T_FUNCTION) {
                $nextStringIsFunction = true;
            } elseif ($nextStringIsFunction && $id == T_STRING) {
                if (!empty($docComment)) {
                    // Associate the function name with the extracted description
                    $functions[$text] = $docComment;
                    $docComment = ''; // Reset for the next function
                }
                $nextStringIsFunction = false;
            }
        }
    }

    return $functions;
}

// Security mitigations callback
function cac_auth_security_section_callback() {
    $mitigations = cac_auth_extract_security_mitigations();
    echo '<p>The following security mitigations are implemented:</p>';
    echo '<ul>';
    foreach ($mitigations as $funcName => $description) {
        echo "<li><strong>{$description}</strong> (Function: $funcName)</li>";
    }
    echo '</ul>';
}

// Redirect section callback
function cac_auth_redirect_section_callback() {
    echo '<p>Select the pages to redirect users to after successful CAC authentication.</p><p>The <em><strong>registration page</strong></em> will be shown to non-synced/unregistered users who have authenticated via CAC.</p><p> The <strong><em>account page</em></strong> will be shown to synced/registered users who have authenticated via CAC.</p>';
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

// Registration settings section callback
function cac_auth_registration_settings_section_callback() {
    echo '<p>Configure the registration settings for CAC authentication.</p>';
}

// Default user role callback
function cac_auth_default_role_callback() {
    $selected_role = get_option('cac_auth_default_role', 'subscriber');
    $roles = get_editable_roles();
    ?>
    <select name="cac_auth_default_role">
        <?php foreach ($roles as $role_name => $role_info) : ?>
            <option value="<?php echo esc_attr($role_name); ?>" <?php selected($selected_role, $role_name); ?>><?php echo esc_html($role_info['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
}