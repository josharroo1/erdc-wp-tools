<?php
/**
 * CAC Authentication Restrictions
 */

// Add meta box for post-specific CAC authentication requirement
function cac_auth_add_post_meta_box() {
    $enable_post_restriction = get_option('cac_auth_enable_post_restriction', false);
    
    if ($enable_post_restriction) {
        $post_types = get_post_types(array('public' => true), 'names');
        foreach ($post_types as $post_type) {
            add_meta_box(
                'cac_auth_post_restriction',
                'CAC Authentication',
                'cac_auth_render_post_meta_box',
                $post_type,
                'side',
                'high'
            );
        }
    }
}
add_action('add_meta_boxes', 'cac_auth_add_post_meta_box');

// Render meta box content
function cac_auth_render_post_meta_box($post) {
    wp_nonce_field('cac_auth_post_meta_box', 'cac_auth_post_meta_box_nonce');
    $requires_cac = get_post_meta($post->ID, '_requires_cac_auth', true);
    ?>
    <label for="cac_auth_requires_cac">
        <input type="checkbox" id="cac_auth_requires_cac" name="cac_auth_requires_cac" value="1" <?php checked($requires_cac, '1'); ?>>
       Require CAC Authentication
    </label>
    <?php
}

// Save post meta
function cac_auth_save_post_meta($post_id) {
if (!isset($_POST['cac_auth_post_meta_box_nonce']) || !wp_verify_nonce($_POST['cac_auth_post_meta_box_nonce'], 'cac_auth_post_meta_box')) {
    return;
}

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $requires_cac = isset($_POST['cac_auth_requires_cac']) ? '1' : '0';
    update_post_meta($post_id, '_requires_cac_auth', sanitize_text_field($requires_cac));
}
add_action('save_post', 'cac_auth_save_post_meta');

// Add CAC protection field to media uploader
function cac_auth_add_media_protection_field($form_fields, $post) {
    // Existing field for CAC protection
    $is_protected = get_post_meta($post->ID, '_cac_protected', true);
    $form_fields['cac_protected'] = array(
        'label' => 'CAC Restrict',
        'input' => 'html',
        'html' => '<input type="checkbox" name="attachments[' . esc_attr($post->ID) . '][cac_protected]" id="attachments-' . esc_attr($post->ID) . '-cac_protected" value="1"' . checked($is_protected, '1', false) . ' />',
        'value' => $is_protected,
        'helps' => 'Check this to require CAC authentication to download this file.',
    );

    // New field for login requirement
    $requires_login = get_post_meta($post->ID, '_requires_login', true);
    $form_fields['requires_login'] = array(
        'label' => 'Require Login',
        'input' => 'html',
        'html' => '<input type="checkbox" name="attachments[' . esc_attr($post->ID) . '][requires_login]" id="attachments-' . esc_attr($post->ID) . '-requires_login" value="1"' . checked($requires_login, '1', false) . ' />',
        'value' => $requires_login,
        'helps' => 'Check this to require users to be logged in to access this file.',
    );

    // Shortcode example (optional)
    $shortcode_example = '[protected_download id="' . esc_attr($post->ID) . '"]';
    $shortcode_example_url = '[protected_download url="' . esc_url(get_permalink($post->ID)) . '"]';
    $form_fields['cac_shortcode_example'] = array(
        'label' => 'Download Link Shortcode',
        'input' => 'html',
        'html' => '<input type="text" value="' . esc_attr($shortcode_example) . '" readonly onclick="this.select();" style="width: 100%;" />' . '<br><br>' . '<input type="text" value="' . esc_attr($shortcode_example_url) . '" readonly onclick="this.select();" style="width: 100%;" />',
        'helps' => 'Use one of these shortcodes to add the CAC protected download link to your content.',
    );

    return $form_fields;
}
add_filter('attachment_fields_to_edit', 'cac_auth_add_media_protection_field', 10, 2);

// Save CAC protection for media items
function cac_auth_save_media_protection($post, $attachment) {
    $post_id = intval($post['ID']);
    
    if (isset($attachment['cac_protected'])) {
        update_post_meta($post_id, '_cac_protected', '1');
    } else {
        delete_post_meta($post_id, '_cac_protected');
    }

    if (isset($attachment['requires_login'])) {
        update_post_meta($post_id, '_requires_login', '1');
    } else {
        delete_post_meta($post_id, '_requires_login');
    }
    return $post;
}
add_filter('attachment_fields_to_save', 'cac_auth_save_media_protection', 10, 2);

// Generate custom download URL
function cac_auth_get_protected_download_url($attachment_id) {
    $token = cac_auth_generate_token($attachment_id);
    set_transient('file_download_' . sanitize_key($token), $attachment_id, 30 * MINUTE_IN_SECONDS);
    return esc_url(add_query_arg(array('file_download' => $token), home_url('/protected-download')));
}

// Generate a token that includes the attachment ID
function cac_auth_generate_token($attachment_id) {
    $expiration = time() + (30 * 60); // 30 minutes from now
    $random = wp_generate_password(16, false);
    $token = $random . '_' . $attachment_id . '_' . $expiration;
    $hash = wp_hash($token);
    return $hash . '_' . $token;
}

// Decode the token to extract the attachment ID
function cac_auth_decode_token($token) {
    $parts = explode('_', $token);
    if (count($parts) !== 4) {
        return false;
    }
    $hash = $parts[0];
    $actual_token = $parts[1] . '_' . $parts[2] . '_' . $parts[3];
    if (wp_hash($actual_token) !== $hash) {
        return false;
    }
    $expiration = intval($parts[3]);
    if (time() > $expiration) {
        return false;
    }
    return intval($parts[2]);
}

function cac_auth_handle_protected_download() {
    // Run cleanup at the start of each download request
    cac_auth_cleanup_expired_transients();
    
    if (!isset($_GET['file_download'])) {
        return;
    }

    $token = sanitize_text_field($_GET['file_download']);
    $attachment_id = get_transient('file_download_' . sanitize_key($token));

    if (!$attachment_id) {
        $attachment_id = cac_auth_decode_token($token);
        if (!$attachment_id) {
            wp_die('Invalid or expired download link. Please return to the original page and try again.');
        }
    } else {
        // Delete the transient to ensure one-time use
        delete_transient('file_download_' . sanitize_key($token));
    }

    $is_protected = get_post_meta($attachment_id, '_cac_protected', true);
    if ($is_protected && !is_user_logged_in()) {
        $_SESSION['cac_auth_intended_download'] = $token;
        $_SESSION['cac_auth_referring_page'] = wp_get_referer();
        $_SESSION['cac_auth_intended_destination'] = esc_url(add_query_arg(array('file_download' => $token), home_url()));
        cac_auth_redirect_to_cac_login();
        exit;
    }

    $requires_login = get_post_meta($attachment_id, '_requires_login', true);
    if ($requires_login && !is_user_logged_in()) {
        $_SESSION['cac_auth_referring_page'] = wp_get_referer();
        $_SESSION['cac_auth_intended_destination'] = esc_url(add_query_arg(array('file_download' => $token), home_url()));
        $_SESSION['cac_auth_intended_download'] = $token;
        $login_url = wp_login_url(esc_url(home_url($_SERVER['REQUEST_URI'])));
        wp_safe_redirect($login_url);
        exit;
    }

    // Record the download
    cac_auth_record_download($attachment_id);

    // Serve the file
    $file_path = get_attached_file($attachment_id);
    if (!$file_path) {
        wp_die('File not found');
    }

    // Clear any output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set headers for download
    header('Content-Type: ' . get_post_mime_type($attachment_id));
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Pragma: public');

    // Output file
    readfile($file_path);
    
    // Delete the transient after successful download
    delete_transient('file_download_' . sanitize_key($token));
    wp_safe_redirect($_SESSION['cac_auth_referring_page']);
    unset($_SESSION['cac_auth_referring_page']);
    exit;
}
add_action('init', 'cac_auth_handle_protected_download');

function cac_auth_record_download($attachment_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cac_download_metrics';

    // Check if the record exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE attachment_id = %d",
        $attachment_id
    ));

    if ($exists) {
        // If the record exists, update it
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name 
            SET download_count = download_count + 1,
            last_downloaded = %s
            WHERE attachment_id = %d",
            current_time('mysql'),
            $attachment_id
        ));
    } else {
        // If the record doesn't exist, insert a new one
        $wpdb->insert(
            $table_name,
            array(
                'attachment_id' => $attachment_id,
                'download_count' => 1,
                'last_downloaded' => current_time('mysql')
            ),
            array('%d', '%d', '%s')
        );
    }
}

// Redirect to intended download after successful authentication
function cac_auth_redirect_after_login() {
    if (isset($_SESSION['cac_auth_intended_download']) && isset($_SESSION['cac_auth_referring_page'])) {
        $token = sanitize_text_field($_SESSION['cac_auth_intended_download']);
        $referring_page = esc_url_raw($_SESSION['cac_auth_referring_page']);
        
        unset($_SESSION['cac_auth_intended_download']);
        unset($_SESSION['cac_auth_referring_page']);
        
        $redirect_url = add_query_arg(array('file_download' => sanitize_text_field($token)), $referring_page);
        wp_safe_redirect($redirect_url);
        exit;
    }
}
add_action('wp_login', 'cac_auth_redirect_after_login');

function cac_auth_get_attachment_id_from_url($url) {
    global $wpdb;
    $attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $url ));
    return $attachment ? $attachment[0] : 0;
}

function cac_auth_protected_download_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'url' => '',
    ), $atts, 'protected_download');

    $attachment_id = intval($atts['id']);

    // If no ID is provided, try to get it from the URL
    if (!$attachment_id && !empty($atts['url'])) {
        $attachment_id = cac_auth_get_attachment_id_from_url($atts['url']);
    }

    if (!$attachment_id) {
        return esc_html__('Invalid attachment ID or URL', 'your-text-domain');
    }

    $download_url = cac_auth_get_protected_download_url($attachment_id);
    return esc_url($download_url);
}
add_shortcode('protected_download', 'cac_auth_protected_download_shortcode');

function cac_auth_check_restrictions() {
    $cac_enabled = get_option('cac_auth_enabled', 'yes') === 'yes';
    $site_wide_restriction = get_option('cac_auth_site_wide_restriction', false);
    $enable_post_restriction = get_option('cac_auth_enable_post_restriction', false);
    $current_url = esc_url_raw((is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

    // Check if user is already logged in
    if (!is_user_logged_in()) {
        // Exclude the CAC endpoint and registration page from restriction
        $registration_page_id = get_option('cac_auth_registration_page');
        $registration_page_url = $registration_page_id ? esc_url_raw(get_permalink($registration_page_id)) : '';
        if (strpos($_SERVER['REQUEST_URI'], 'cac-auth-endpoint') !== false || $current_url === $registration_page_url) {
            return;
        }

        if ($cac_enabled) {
            if ($site_wide_restriction) {
                cac_auth_redirect_to_cac_login();
            } elseif ($enable_post_restriction && is_singular()) {
                $post_id = get_the_ID();
                $requires_cac = get_post_meta($post_id, '_requires_cac_auth', true);
                if ($requires_cac) {
                    cac_auth_redirect_to_cac_login();
                }
            }
        }
    }
}

// Add this function to the 'template_redirect' hook to check restrictions on every page load
add_action('template_redirect', 'cac_auth_check_restrictions', 1);

// Add a function to clean up expired transients
function cac_auth_cleanup_expired_transients() {
    global $wpdb;
    $expired = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT option_name FROM $wpdb->options
            WHERE option_name LIKE %s
            AND option_value < %d",
            $wpdb->esc_like('_transient_timeout_file_download_') . '%',
            time()
        )
    );

    if ($expired) {
        foreach ($expired as $transient) {
            delete_transient(str_replace('_transient_timeout_', '', $transient));
        }
    }
}