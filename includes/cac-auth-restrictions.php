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
    update_post_meta($post_id, '_requires_cac_auth', $requires_cac);
}
add_action('save_post', 'cac_auth_save_post_meta');

// Add CAC protection field to media uploader
function cac_auth_add_media_protection_field($form_fields, $post) {
    $is_protected = get_post_meta($post->ID, '_cac_protected', true);
    $form_fields['cac_protected'] = array(
        'label' => 'CAC Restrict',
        'input' => 'html',
        'html' => '<input type="checkbox" name="attachments[' . $post->ID . '][cac_protected]" id="attachments-' . $post->ID . '-cac_protected" value="1"' . ($is_protected ? ' checked="checked"' : '') . ' />',
        'value' => $is_protected,
        'helps' => 'Check this to require CAC authentication to download this file.',
    );

    // Add shortcode example
    $shortcode_example = '[cac_protected_download id="' . $post->ID . '"]';
    $form_fields['cac_shortcode_example'] = array(
        'label' => 'Download Link Shortcode',
        'input' => 'html',
        'html' => '<input type="text" value="' . esc_attr($shortcode_example) . '" readonly onclick="this.select();" style="width: 100%;" />',
        'helps' => 'Use this shortcode to add the CAC protected download link to your content.',
    );

    return $form_fields;
}
add_filter('attachment_fields_to_edit', 'cac_auth_add_media_protection_field', 10, 2);

// Save CAC protection for media items
function cac_auth_save_media_protection($post, $attachment) {
    if (isset($attachment['cac_protected'])) {
        update_post_meta($post['ID'], '_cac_protected', '1');
    } else {
        delete_post_meta($post['ID'], '_cac_protected');
    }
    return $post;
}
add_filter('attachment_fields_to_save', 'cac_auth_save_media_protection', 10, 2);

// Generate custom download URL
function cac_auth_get_protected_download_url($attachment_id) {
    $token = cac_auth_generate_token($attachment_id);
    set_transient('cac_download_' . $token, $attachment_id, 30 * MINUTE_IN_SECONDS);
    return add_query_arg(array(
        'cac_download' => $token
    ), home_url());
}

// Generate a token that includes the attachment ID
function cac_auth_generate_token($attachment_id) {
    return wp_generate_password(16, false) . '_' . $attachment_id;
}

// Decode the token to extract the attachment ID
function cac_auth_decode_token($token) {
    $parts = explode('_', $token);
    if (count($parts) === 2 && is_numeric($parts[1])) {
        return intval($parts[1]);
    }
    return false;
}

function cac_auth_handle_protected_download() {
    if (isset($_GET['cac_download'])) {
        $token = sanitize_text_field($_GET['cac_download']);
        $attachment_id = get_transient('cac_download_' . $token);
    } elseif (isset($_GET['attachment_id'])) {
        $attachment_id = intval($_GET['attachment_id']);
    } else {
        return;
    }

    if (!$attachment_id) {
        wp_die('Invalid or expired download link. Please return to the original page and try again.');
    }

    $is_protected = get_post_meta($attachment_id, '_cac_protected', true);
    if ($is_protected && !is_user_logged_in()) {
        $_SESSION['cac_auth_intended_download'] = isset($token) ? $token : $attachment_id;
        $_SESSION['cac_auth_referring_page'] = wp_get_referer();
        $_SESSION['cac_auth_intended_destination'] = add_query_arg(isset($token) ? array('cac_download' => $token) : array('attachment_id' => $attachment_id), home_url());
        cac_auth_redirect_to_cac_login();
        exit;
    }

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
    
    // Delete the transient after successful download if it exists
    if (isset($token)) {
        delete_transient('cac_download_' . $token);
    }
    
    exit;
}
add_action('init', 'cac_auth_handle_protected_download');

// Redirect to intended download after successful authentication
function cac_auth_redirect_after_login() {
    if (isset($_SESSION['cac_auth_intended_download']) && isset($_SESSION['cac_auth_referring_page'])) {
        $token = $_SESSION['cac_auth_intended_download'];
        $referring_page = $_SESSION['cac_auth_referring_page'];
        
        unset($_SESSION['cac_auth_intended_download']);
        unset($_SESSION['cac_auth_referring_page']);
        
        $redirect_url = add_query_arg(array('cac_download' => $token), $referring_page);
        wp_redirect($redirect_url);
        exit;
    }
}
add_action('wp_login', 'cac_auth_redirect_after_login');

function cac_auth_protected_download_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'cac_protected_download');

    $attachment_id = intval($atts['id']);
    if (!$attachment_id) {
        return 'Invalid attachment ID';
    }

    $download_url = cac_auth_get_protected_download_url($attachment_id);
    return esc_url($download_url);
}
add_shortcode('cac_protected_download', 'cac_auth_protected_download_shortcode');

function cac_auth_check_restrictions() {
    $cac_enabled = get_option('cac_auth_enabled', 'yes') === 'yes';
    $site_wide_restriction = get_option('cac_auth_site_wide_restriction', false);
    $enable_post_restriction = get_option('cac_auth_enable_post_restriction', false);
    $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    // Check if user is already logged in
    if (!is_user_logged_in()) {
        // Exclude the CAC endpoint and registration page from restriction
        $registration_page_id = get_option('cac_auth_registration_page');
        $registration_page_url = $registration_page_id ? get_permalink($registration_page_id) : '';
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

function cac_auth_check_media_access($rewrite) {
    if (isset($_GET['attachment_id']) && is_numeric($_GET['attachment_id'])) {
        $attachment_id = intval($_GET['attachment_id']);
        if (get_post_meta($attachment_id, '_cac_protected', true) && !current_user_can('manage_options')) {
            // File is protected, check if user is logged in
            if (!is_user_logged_in()) {
                // User is not logged in, redirect to CAC login
                cac_auth_redirect_to_cac_login();
                exit;
            }
        }
    }
    return $rewrite;
}
add_filter('rewrite_rules_array', 'cac_auth_check_media_access');

function cac_auth_modify_media_url($url, $post_id) {
    if (get_post_meta($post_id, '_cac_protected', true)) {
        $url = add_query_arg('attachment_id', $post_id, $url);
    }
    return $url;
}
add_filter('wp_get_attachment_url', 'cac_auth_modify_media_url', 10, 2);
add_filter('attachment_link', 'cac_auth_modify_media_url', 10, 2);