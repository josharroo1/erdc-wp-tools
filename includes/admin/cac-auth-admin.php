<?php
/**
 * CAC Authentication Admin Settings
 */

// Add CAC Authentication settings page
function cac_auth_add_settings_page() {
    add_options_page(
        'CAC Authentication Settings',
        'CAC Authentication',
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

    add_settings_section(
        'cac_auth_redirect_section',
        'Redirection Settings',
        'cac_auth_redirect_section_callback',
        'cac-auth-settings'
    );

    add_settings_field(
        'cac_auth_redirect_page',
        'Redirect Page',
        'cac_auth_redirect_page_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
    );

    add_settings_field(
        'cac_auth_registration_page',
        'Registration Page',
        'cac_auth_registration_page_callback',
        'cac-auth-settings',
        'cac_auth_redirect_section'
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