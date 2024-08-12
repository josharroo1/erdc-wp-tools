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
    $svg_fill_color = esc_attr(get_option('cac_auth_svg_fill_color', '#000000'));
    $link_color = esc_attr(get_option('cac_auth_link_color', '#0073aa'));
    ?>
    <style>
        .cac-registration-form .form-header svg {
            fill: <?php echo esc_attr($svg_fill_color); ?>;
        }
        .cac-registration-form .form-footer a {
            color: <?php echo esc_attr($link_color); ?>;
        }
    </style>
    <div class="cac-registration-form">
    <?php
    if (isset($_GET['registration_error'])) {
        $error_code = sanitize_text_field($_GET['registration_error']);
        cac_display_registration_error($error_code);
    }
    ?>
    <div class="form-header">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M399 384.2C376.9 345.8 335.4 320 288 320H224c-47.4 0-88.9 25.8-111 64.2c35.2 39.2 86.2 63.8 143 63.8s107.8-24.7 143-63.8zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256 16a72 72 0 1 0 0-144 72 72 0 1 0 0 144z"/></svg>
        <h2><?php echo esc_html__('CAC Registration', 'your-text-domain'); ?></h2>
    </div>
    <p class="form-subtitle"><?php echo esc_html__('We need a few more details', 'your-text-domain'); ?></p>
    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
        <input type="hidden" name="action" value="cac_process_registration">
        <?php wp_nonce_field('cac_registration', 'cac_registration_nonce'); ?>

        <div class="form-field">
            <label for="cac_email"><?php echo esc_html__('Organization Email', 'your-text-domain'); ?></label>
            <input placeholder="<?php echo esc_attr__('e.g., sample@usace.army.mil', 'your-text-domain'); ?>" type="email" name="cac_email" id="cac_email" required>
        </div>

        <?php
        // Retrieve custom registration fields from the plugin settings
        $custom_fields = get_option('cac_auth_registration_fields', array());

        if (is_array($custom_fields)) {
            foreach ($custom_fields as $field_id => $field_data) {
                $field_label = isset($field_data['label']) ? sanitize_text_field($field_data['label']) : '';
                $field_type = isset($field_data['type']) ? sanitize_text_field($field_data['type']) : 'text';
                $field_options = isset($field_data['options']) ? sanitize_text_field($field_data['options']) : '';

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
                        $csv_file = get_option('cac_auth_csv_file_' . sanitize_key($field_id), '');
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
                                    $options[sanitize_text_field($row['key'])] = sanitize_text_field($row['value']);
                                }
                            }
                        } else {
                            $options = array_map('trim', explode(',', $field_options));
                            $options = array_combine($options, $options);
                        }
                        ?>
                        <div class="form-field">
                            <label for="cac_field_<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                            <select name="cac_field_<?php echo esc_attr($field_id); ?>" id="cac_field_<?php echo esc_attr($field_id); ?>" class="cac-select2" required>
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
            <input type="submit" value="<?php echo esc_attr__('Register', 'your-text-domain'); ?>">
        </div>
        <div class="form-footer">
            <div><?php echo esc_html__('You are registering with', 'your-text-domain'); ?> <a href="<?php echo esc_url(get_site_url()); ?>"><?php echo esc_html(get_site_url()); ?></a></div>
        </div>
    </form>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.cac-select2').select2();
    });
    </script>
    <?php
}

// Process CAC registration form submission
function cac_process_registration() {
    error_log('CAC registration process started');

    if (!isset($_POST['cac_registration_nonce']) || !wp_verify_nonce($_POST['cac_registration_nonce'], 'cac_registration')) {
        wp_die(esc_html__('Invalid nonce.', 'your-text-domain'));
    }

    $email = sanitize_email($_POST['cac_email']);
    $max_email_length = 100; // WordPress default limit
    if (strlen($email) > $max_email_length) {
        error_log('Email exceeds maximum length: ' . strlen($email) . ' characters');
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'email_too_long', $registration_page_url));
        exit;
    }

    if (empty($email)) {
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'missing_email', $registration_page_url));
        exit;
    }

    // Check if email is valid
    if (!is_email($email)) {
        error_log('Invalid email format: ' . $email);
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'invalid_email', $registration_page_url));
        exit;
    }
    
    // Check if email already exists
    if (email_exists($email)) {
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'email_exists', $registration_page_url));
        exit;
    }

    $dn = isset($_SESSION['SSL_CLIENT_S_DN_CN']) ? sanitize_text_field($_SESSION['SSL_CLIENT_S_DN_CN']) : '';
    $dod_id = cac_extract_dod_id($dn);
    $names = cac_extract_names($dn);

    if (!$dod_id || !$names) {
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'cac_extraction_failed', $registration_page_url));
        exit;
    }

    $hashed_dod_id = hash('sha256', $dod_id);
    $user_query = get_users(array('meta_key' => 'hashed_dod_id', 'meta_value' => $hashed_dod_id));

    if (!empty($user_query)) {
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'user_exists', $registration_page_url));
        exit;
    }

    $default_role = sanitize_text_field(get_option('cac_auth_default_role', 'subscriber'));
    $user_approval_required = get_option('cac_auth_user_approval', false);
    $user_status = $user_approval_required ? 'pending' : 'active';
    $username = cac_generate_username($names, $email);
    $user_id = wp_insert_user(array(
        'user_login' => sanitize_user($username, true),
        'user_email' => $email,
        'first_name' => sanitize_text_field($names['first_name']),
        'last_name' => sanitize_text_field($names['last_name']),
        'user_pass' => wp_generate_password(),
        'role' => $default_role,
        'user_status' => sanitize_text_field($user_status),
    ));

    if (is_wp_error($user_id)) {
        $registration_page_url = esc_url(get_permalink(get_option('cac_auth_registration_page', home_url('/cac-registration/'))));
        wp_safe_redirect(add_query_arg('registration_error', 'user_creation_failed', $registration_page_url));
        exit;
    }

    update_user_meta($user_id, 'hashed_dod_id', $hashed_dod_id);

    // Save custom registration field values as user meta
    $custom_fields = get_option('cac_auth_registration_fields', array());
    foreach ($custom_fields as $field_id => $field_data) {
        if (isset($_POST['cac_field_' . $field_id])) {
            $field_value = sanitize_text_field($_POST['cac_field_' . sanitize_key($field_id)]);
            $sanitized_label = strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $field_data['label'])));
            $meta_key = 'cac_field_' . sanitize_key($sanitized_label);
            update_user_meta($user_id, $meta_key, $field_value);
        }
    }

    if ($user_approval_required && $user_status !== 'active') {
        update_user_meta($user_id, 'user_status', 'pending');
        // User is not approved, log them out and display an error message
        wp_logout();
        $message = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account Pending Approval</title>
<style>
    html, body {
        margin: 0 !important;
        padding: 0 !important;
        height: 100%;
        background: none !important;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        font-family: Arial, sans-serif;
        border: none !important;
    }
    #custom-error {
        width: 80%;
        max-width: 600px;
        padding: 20px;
        background: white;
        color: #333;
        box-shadow: none;
    }
</style>
</head>
<body>
<div id="custom-error">
    <h1 style="font-weight: bold;">Pending Approval</h1>
    <p>Your account is pending approval. Please be patient as no further action is required from you at this point. An administrator will review and approve your account shortly.</p>
</div>
</body>
</html>
HTML;

        wp_die($message, esc_html__('Account Pending Approval', 'your-text-domain'), array('response' => 200));
    } else {
        error_log('User approved. Proceeding with login and redirection.');
        
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        cac_auth_handle_redirection();
    }

    error_log('End of cac_process_registration function reached unexpectedly.');
}
add_action('admin_post_cac_process_registration', 'cac_process_registration');
add_action('admin_post_nopriv_cac_process_registration', 'cac_process_registration');

// Display registration error messages
function cac_display_registration_error($error_code) {
    $error_messages = array(
        'missing_email' => esc_html__('Please provide an email address.', 'your-text-domain'),
        'cac_extraction_failed' => esc_html__('Failed to extract information from CAC.', 'your-text-domain'),
        'user_exists' => esc_html__('An account with the provided CAC information already exists.', 'your-text-domain'),
        'user_creation_failed' => esc_html__('Failed to create a new user account.', 'your-text-domain'),
        'email_exists' => esc_html__('An account with the provided email address already exists.', 'your-text-domain'),
        'email_too_long' => esc_html__('The provided email address is too long. Maximum length is 100 characters.', 'your-text-domain'),
        'invalid_email' => esc_html__('The provided email address is not valid.', 'your-text-domain'),
    );

    if (isset($error_messages[$error_code])) {
        echo '<div class="cac-registration-error">' . esc_html($error_messages[$error_code]) . '</div>';
    }
}
