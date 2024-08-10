<?php
/**
 * CAC Authentication Restrictions
 */

// Check if site-wide restriction is enabled and user is not authenticated
function cac_auth_check_site_wide_restriction() {
    $site_wide_restriction = get_option('cac_auth_site_wide_restriction', false);
    $cac_auth_enabled = get_option('cac_auth_enabled', 'yes') === 'yes';

    if ($site_wide_restriction && $cac_auth_enabled && !is_user_logged_in()) {
        cac_auth_redirect_to_login();
    }
}
add_action('template_redirect', 'cac_auth_check_site_wide_restriction', 1);

// Check if post-specific restriction is enabled and user is not authenticated
function cac_auth_check_post_restriction() {
    $enable_post_restriction = get_option('cac_auth_enable_post_restriction', false);
    $cac_auth_enabled = get_option('cac_auth_enabled', 'yes') === 'yes';

    if ($enable_post_restriction && $cac_auth_enabled && is_singular() && !is_user_logged_in()) {
        $post_id = get_the_ID();
        $requires_cac = get_post_meta($post_id, '_requires_cac_auth', true);

        if ($requires_cac) {
            cac_auth_redirect_to_login();
        }
    }
}
add_action('template_redirect', 'cac_auth_check_post_restriction', 2);

// Redirect user to CAC authentication endpoint
function cac_auth_redirect_to_login() {
    $cac_auth_url = plugins_url('cac-auth-endpoint.php', dirname(__FILE__));
    $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $redirect_url = add_query_arg('redirect_to', urlencode($current_url), $cac_auth_url);
    wp_redirect($redirect_url);
    exit;
}

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