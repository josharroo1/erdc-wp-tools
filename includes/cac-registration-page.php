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
        <p class="form-subtitle">We just need a few more details</p>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
            <input type="hidden" name="action" value="cac_process_registration">
            <?php wp_nonce_field('cac_registration', 'cac_registration_nonce'); ?>

            <div class="form-field">
                <label for="cac_email">Organization Email</label>
                <input placeholder="e.g., sample@usace.army.mil" type="email" name="cac_email" id="cac_email" required>
            </div>

            <?php
            // Retrieve custom registration fields from the plugin settings
            $custom_fields = get_option('cac_auth_registration_fields', array());

            // Check if $custom_fields is an array before using foreach
            if (is_array($custom_fields)) {
                foreach ($custom_fields as $field_id => $field_data) {
                    $field_label = isset($field_data['label']) ? $field_data['label'] : '';
                    $field_type = isset($field_data['type']) ? $field_data['type'] : 'text';
                    $field_options = isset($field_data['options']) ? $field_data['options'] : '';

                    switch ($field_type) {
                        case 'text':
                        case 'number':
                            ?>
                            <div class="form-field">
                                <label for="cac_field_<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                                <input type="<?php echo esc_attr($field_type); ?>" name="cac_field_<?php echo esc_attr($field_id); ?>" id="cac_field_<?php echo esc_attr($field_id); ?>" required>
                            </div>
                            <?php
                            break;
                        case 'select':
                            $options = array();
                            $csv_file = get_option('cac_auth_csv_file_' . $field_id, '');
                            if (!empty($csv_file)) {
                                $upload_dir = wp_upload_dir();
                                $csv_path = $upload_dir['basedir'] . '/cac-auth-csv-files/' . $csv_file;
                                if (file_exists($csv_path)) {
                                    $csv_data = array_map('str_getcsv', file($csv_path));
                                    array_walk($csv_data, function(&$a) use ($csv_data) {
                                        $a = array_combine($csv_data[0], $a);
                                    });
                                    array_shift($csv_data);
                                    foreach ($csv_data as $row) {
                                        $options[$row['key']] = $row['value'];
                                    }
                                }
                            } else {
                                $options = array_map('trim', explode(',', $field_options));
                                $options = array_combine($options, $options);
                            }
                            ?>
                            <div class="form-field">
                                <label for="cac_field_<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                                <select name="cac_field_<?php echo esc_attr($field_id); ?>" id="cac_field_<?php echo esc_attr($field_id); ?>" required>
                                    <?php foreach ($options as $key => $value) : ?>
                                        <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($key); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php
                            break;
                        default:
                            ?>
                            <div class="form-field">
                                <label for="cac_field_<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                                <input type="text" name="cac_field_<?php echo esc_attr($field_id); ?>" id="cac_field_<?php echo esc_attr($field_id); ?>">
                            </div>
                            <?php
                            break;
                    }
                }
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
        $registration_page_id = get_option('cac_auth_registration_page');
        $registration_page_url = $registration_page_id ? get_permalink($registration_page_id) : home_url('/cac-registration/');
        wp_redirect(add_query_arg('registration_error', 'missing_email', $registration_page_url));
        exit;
    }

    $dn = $_SERVER['SSL_CLIENT_S_DN'] ?? '';
    $dod_id = cac_extract_dod_id($dn);
    $names = cac_extract_names($dn);

    if (!$dod_id || !$names) {
        $registration_page_id = get_option('cac_auth_registration_page');
        $registration_page_url = $registration_page_id ? get_permalink($registration_page_id) : home_url('/cac-registration/');
        wp_redirect(add_query_arg('registration_error', 'cac_extraction_failed', $registration_page_url));
        exit;
    }

    $hashed_dod_id = hash('sha256', $dod_id);
    $user_query = get_users(array('meta_key' => 'hashed_dod_id', 'meta_value' => $hashed_dod_id));

    if (!empty($user_query)) {
        $registration_page_id = get_option('cac_auth_registration_page');
        $registration_page_url = $registration_page_id ? get_permalink($registration_page_id) : home_url('/cac-registration/');
        wp_redirect(add_query_arg('registration_error', 'user_exists', $registration_page_url));
        exit;
    }

    $default_role = get_option('cac_auth_default_role', 'subscriber');
    $username = cac_generate_username($names, $email);
    $user_id = wp_insert_user(array(
        'user_login' => $username,
        'user_email' => $email,
        'first_name' => $names['first_name'],
        'last_name' => $names['last_name'],
        'user_pass' => wp_generate_password(),
        'role' => $default_role,
    ));

    if (is_wp_error($user_id)) {
        $registration_page_id = get_option('cac_auth_registration_page');
        $registration_page_url = $registration_page_id ? get_permalink($registration_page_id) : home_url('/cac-registration/');
        wp_redirect(add_query_arg('registration_error', 'user_creation_failed', $registration_page_url));
        exit;
    }

    update_user_meta($user_id, 'hashed_dod_id', $hashed_dod_id);

    // Save custom registration field values as user meta
    $custom_fields = get_option('cac_auth_registration_fields', array());
    foreach ($custom_fields as $field_id => $field_label) {
        if (isset($_POST['cac_field_' . $field_id])) {
            $field_value = sanitize_text_field($_POST['cac_field_' . $field_id]);
            update_user_meta($user_id, 'cac_field_' . $field_label, $field_value);
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