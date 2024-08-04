<?php
/**
 * CAC Authentication Admin Settings
 */

require_once CAC_AUTH_PLUGIN_DIR . 'includes/admin/cac-auth-admin-functions.php';
require_once CAC_AUTH_PLUGIN_DIR . 'includes/dev-sec.php'; 

// Add CAC Authentication settings page
function cac_auth_add_settings_page() {
    add_options_page(
        'ERDC WP Tools Settings',
        'ERDC WP Tools',
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
    register_setting('cac_auth_settings', 'cac_auth_svg_fill_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#000000',
    ));
    register_setting('cac_auth_settings', 'cac_auth_link_color', array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default' => '#0073aa',
    ));
    register_setting('cac_auth_settings', 'cac_auth_user_approval', array(
        'type' => 'boolean',
        'sanitize_callback' => 'absint',
        'default' => false,
    ));

    add_settings_section(
        'cac_auth_general_section',
        'CAC Sync Settings',
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
        'cac_auth_approval_section',
        'Account Approval',
        'cac_auth_approval_callback',
        'cac-auth-settings'
    );

    add_settings_section(
        'cac_auth_redirect_section',
        'CAC Registration Settings',
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
        'Login Redirect Page',
        'cac_auth_redirect_page_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
    );

    // add_settings_section(
    //     'cac_auth_registration_settings_section',
    //     'Registration Settings',
    //     'cac_auth_registration_settings_section_callback',
    //     'cac-auth-settings'
    // );

    add_settings_field(
        'cac_auth_default_role',
        'Default User Role',
        'cac_auth_default_role_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
    );

    add_settings_section(
        'cac_auth_custom_fields_section',
        'CAC Registration Form',
        'cac_auth_render_custom_fields',
        'cac-auth-settings'
    );

    add_settings_section(
        'cac_auth_color_picker_section',
        'Color Settings',
        'cac_auth_render_color_settings',
        'cac-auth-settings'
    );

    add_settings_section(
        'cac_auth_security_section', // Section ID
        'Active Security Mitigations', // Section title
        'cac_auth_security_section_callback', // Callback function
        'cac-auth-settings' // Page to add the section to
    );
}
add_action('admin_init', 'cac_auth_register_settings');

// Add color picker fields and user approval toggle to the settings page
function cac_auth_render_color_settings() {
    $svg_fill_color = get_option('cac_auth_svg_fill_color', '#000000');
    $link_color = get_option('cac_auth_link_color', '#0073aa');
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="cac_auth_svg_fill_color">Form Icon Color</label></th>
            <td>
                <input type="text" name="cac_auth_svg_fill_color" id="cac_auth_svg_fill_color" value="<?php echo esc_attr($svg_fill_color); ?>" class="cac-color-picker">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cac_auth_link_color">Form Links Color</label></th>
            <td>
                <input type="text" name="cac_auth_link_color" id="cac_auth_link_color" value="<?php echo esc_attr($link_color); ?>" class="cac-color-picker">
            </td>
        </tr>
    </table>
    <?php
}
add_action('cac_auth_settings_page', 'cac_auth_render_color_settings', 20);

// Add user approval toggle to the settings page
function cac_auth_approval_callback() {
    $user_approval = get_option('cac_auth_user_approval', false);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="cac_auth_user_approval">Require Account Approvals?</label></th>
            <td>
                <input type="checkbox" name="cac_auth_user_approval" id="cac_auth_user_approval" value="1" <?php checked($user_approval, true); ?>>
                <label class="description" for="cac_auth_user_approval">Manually activate new users.</label>
            </td>
        </tr>
    </table>
    <?php
}
add_action('cac_auth_settings_page', 'cac_auth_approval_callback', 20);

// Security section callback
function cac_auth_security_section_callback() {
    global $securityMitigationsDescriptions;

    echo '<p>The following security mitigations are implemented:</p>';
    echo '<ul class="security-mitigations">';
    foreach ($securityMitigationsDescriptions as $funcName => $description) {
        echo "<li>$description</li>";
    }
    echo '</ul>';
}

// Redirect section callback
function cac_auth_redirect_section_callback() {
    echo '<p>The <strong><em>Registration Page</em></strong> is shown to users authenticated via CAC but not yet registered or synced with WordPress.</p>
<p>The <strong><em>Login Redirect Page</em></strong> is the destination for registered and synced users after successful CAC authentication.</p>';
}

// Redirect page callback
function cac_auth_redirect_page_callback() {
    $selected_redirect = get_option('cac_auth_redirect_page', 'wp-admin');
    ?>
    <select name="cac_auth_redirect_page">
        <option value="wp-admin" <?php selected($selected_redirect, 'wp-admin'); ?>>Admin Panel</option>
        <?php
        $pages = get_pages();
        foreach ($pages as $page) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($page->ID),
                selected($selected_redirect, $page->ID, false),
                esc_html($page->post_title)
            );
        }
        ?>
    </select>
    <?php
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
    echo '<p>Enable syncing a CAC authentication with a WordPress user account.</p>';
}

// // Registration settings section callback
// function cac_auth_registration_settings_section_callback() {
//     echo '<p>Configure the registration settings for CAC authentication.</p>';
// }

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

// Ajax to remove the current csv from a field
function cac_auth_remove_csv() {
    $field_id = isset($_POST['field_id']) ? sanitize_text_field($_POST['field_id']) : '';
    if (!empty($field_id)) {
        delete_option('cac_auth_csv_file_' . $field_id);
        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_cac_auth_remove_csv', 'cac_auth_remove_csv');