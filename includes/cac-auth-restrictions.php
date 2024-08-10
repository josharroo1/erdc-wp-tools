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

// Add the checkbox to Quick Edit
function cac_auth_add_quick_edit_fields($column_name, $post_type) {
    if ($column_name !== 'title' || !get_option('cac_auth_enable_post_restriction', false)) {
        return;
    }
    ?>
    <fieldset class="inline-edit-col-right">
        <div class="inline-edit-col">
            <label class="inline-edit-group">
                <input type="checkbox" name="cac_auth_requires_cac" value="1">
                <span class="checkbox-title">Require CAC Authentication</span>
            </label>
        </div>
    </fieldset>
    <?php
}
add_action('quick_edit_custom_box', 'cac_auth_add_quick_edit_fields', 10, 2);

// Save the meta value when Quick Edit is used
function cac_auth_save_quick_edit_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (!get_option('cac_auth_enable_post_restriction', false)) {
        return;
    }

    $requires_cac = isset($_POST['cac_auth_requires_cac']) ? '1' : '0';
    update_post_meta($post_id, '_requires_cac_auth', $requires_cac);
}
add_action('save_post', 'cac_auth_save_quick_edit_data');

// Populate the checkbox state when Quick Edit is opened
function cac_auth_quick_edit_javascript() {
    if (!get_option('cac_auth_enable_post_restriction', false)) {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(function($) {
        var $wp_inline_edit = inlineEditPost.edit;
        inlineEditPost.edit = function(id) {
            $wp_inline_edit.apply(this, arguments);
            var post_id = 0;
            if (typeof(id) == 'object') {
                post_id = parseInt(this.getId(id));
            }
            if (post_id > 0) {
                var requires_cac = $('#requires_cac_' + post_id).val();
                $('input[name="cac_auth_requires_cac"]', '.inline-edit-row').prop('checked', requires_cac === '1');
            }
        };
    });
    </script>
    <?php
}
add_action('admin_footer-edit.php', 'cac_auth_quick_edit_javascript');

// Add a hidden field to store the current CAC restriction state
function cac_auth_add_cac_restriction_state($columns, $post) {
    if (!get_option('cac_auth_enable_post_restriction', false)) {
        return $columns;
    }
    $requires_cac = get_post_meta($post->ID, '_requires_cac_auth', true);
    $columns['cac_restriction'] = '<input type="hidden" id="requires_cac_' . $post->ID . '" value="' . esc_attr($requires_cac) . '" />';
    return $columns;
}
add_filter('manage_posts_columns', 'cac_auth_add_cac_restriction_state', 10, 2);
add_filter('manage_pages_columns', 'cac_auth_add_cac_restriction_state', 10, 2);

// Display the hidden field in the custom column
function cac_auth_display_cac_restriction_state($column_name, $post_id) {
    if ($column_name == 'cac_restriction' && get_option('cac_auth_enable_post_restriction', false)) {
        $requires_cac = get_post_meta($post_id, '_requires_cac_auth', true);
        echo '<input type="hidden" id="requires_cac_' . $post_id . '" value="' . esc_attr($requires_cac) . '" />';
    }
}
add_action('manage_posts_custom_column', 'cac_auth_display_cac_restriction_state', 10, 2);
add_action('manage_pages_custom_column', 'cac_auth_display_cac_restriction_state', 10, 2);