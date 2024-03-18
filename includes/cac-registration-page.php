<?php
/**
 * CAC Registration Page
 */

// Register CAC registration shortcode
function cac_registration_shortcode() {
    ob_start();
    cac_render_registration_form();
    return ob_get_clean();
}
add_shortcode('cac_registration', 'cac_registration_shortcode');

// Render CAC registration form
function cac_render_registration_form() {
    if (isset($_GET['registration_error'])) {
        $error_code = $_GET['registration_error'];
        cac_display_registration_error($error_code);
    }
    ?>
    <div class="cac-registration-form">
        <h2>CAC Registration</h2>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="cac_process_registration">
            <?php wp_nonce_field('cac_registration', 'cac_registration_nonce'); ?>

            <div class="form-field">
                <label for="cac_email">Email</label>
                <input type="email" name="cac_email" id="cac_email" required>
            </div>

            <?php
            // Retrieve custom registration fields from the plugin settings
            $custom_fields = get_option('cac_auth_registration_fields', array());

            foreach ($custom_fields as $field_id => $field_label) {
                ?>
                <div class="form-field">
                    <label for="cac_field_<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                    <input type="text" name="cac_field_<?php echo esc_attr($field_id); ?>" id="cac_field_<?php echo esc_attr($field_id); ?>">
                </div>
                <?php
            }
            ?>

            <div class="form-field">
                <input type="submit" value="Register">
            </div>
        </form>
    </div>
    <?php
}

// Process CAC registration form submission
function cac_process_registration() {
    if (!isset($_POST['cac_registration_nonce']) || !wp_verify_nonce($_POST['cac_registration_nonce'], 'cac_registration')) {
        wp_die('Invalid nonce.');
    }

    $email = sanitize_email($_POST['cac_email']);

    if (empty($email)) {
        wp_redirect(add_query_arg('registration_error', 'missing_email', home_url('/cac-registration/')));
        exit;
    }

    $dn = $_SERVER['SSL_CLIENT_S_DN'] ?? '';
    $dod_id = cac_extract_dod_id($dn);
    $names = cac_extract_names($dn);

    if (!$dod_id || !$names) {
        wp_redirect(add_query_arg('registration_error', 'cac_extraction_failed', home_url('/cac-registration/')));
        exit;
    }

    $hashed_dod_id = hash('sha256', $dod_id);
    $user_query = get_users(array('meta_key' => 'hashed_dod_id', 'meta_value' => $hashed_dod_id));

    if (!empty($user_query)) {
        wp_redirect(add_query_arg('registration_error', 'user_exists', home_url('/cac-registration/')));
        exit;
    }

    $username = cac_generate_username($names, $email);
    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_email' => $email,
        'first_name' => $names['first_name'],
        'last_name' => $names['last_name'],
        'user_pass' => wp_generate_password(),
    ));

    if (is_wp_error($user_id)) {
        wp_redirect(add_query_arg('registration_error', 'user_creation_failed', home_url('/cac-registration/')));
        exit;
    }

    update_user_meta($user_id, 'hashed_dod_id', $hashed_dod_id);

    // Save custom registration field values as user meta
    $custom_fields = get_option('cac_auth_registration_fields', array());
    foreach ($custom_fields as $field_id => $field_label) {
        if (isset($_POST['cac_field_' . $field_id])) {
            $field_value = sanitize_text_field($_POST['cac_field_' . $field_id]);
            update_user_meta($user_id, 'cac_field_' . $field_id, $field_value);
        }
    }

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);

    $redirect_page_id = get_option('cac_auth_redirect_page');
    if ($redirect_page_id) {
        wp_redirect(get_permalink($redirect_page_id));
    } else {
        wp_redirect(home_url());
    }
    exit;
}
add_action('admin_post_nopriv_cac_process_registration', 'cac_process_registration');

// Display registration error message
function cac_display_registration_error($error_code) {
    $error_messages = array(
        'missing_email' => 'Please provide an email address.',
        'cac_extraction_failed' => 'Failed to extract information from CAC.',
        'user_exists' => 'An account with the provided CAC information already exists.',
        'user_creation_failed' => 'Failed to create a new user account.',
    );

    if (isset($error_messages[$error_code])) {
        echo '<div class="cac-registration-error">' . $error_messages[$error_code] . '</div>';
    }
}