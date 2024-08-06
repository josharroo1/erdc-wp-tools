<?php
/**
 * Custom Post Columns for ERDC WP Tools
 */

// Don't allow direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add custom columns to all public post types
 */
function cac_auth_add_custom_columns_to_all_post_types() {
    if (get_option('cac_auth_enable_custom_columns', true)) {
        $post_types = get_post_types(['public' => true], 'names');
        foreach ($post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", 'cac_auth_add_custom_columns');
            add_action("manage_{$post_type}_posts_custom_column", 'cac_auth_display_custom_columns', 10, 2);
        }
        add_filter('display_post_states', 'cac_auth_change_draft_to_in_progress');
    }
}
add_action('admin_init', 'cac_auth_add_custom_columns_to_all_post_types');

/**
 * Add custom columns to the post list
 */
function cac_auth_add_custom_columns($columns) {
    $position = get_option('cac_auth_custom_columns_position', 'after_title');
    $new_columns = array();
    $custom_columns = array(
        'date_created' => 'Date Created',
        'last_revision' => 'Last Revision',
        'date_published' => 'Date Published'
    );

    if ($position === 'end') {
        $new_columns = $columns + $custom_columns;
    } else {
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if (($position === 'after_title' && $key === 'title') ||
                ($position === 'before_date' && $key === 'date')) {
                $new_columns = array_merge($new_columns, $custom_columns);
            }
        }
    }

    return $new_columns;
}

/**
 * Display content for custom columns
 */
function cac_auth_display_custom_columns($column_name, $post_id) {
    switch ($column_name) {
        case 'date_created':
            cac_auth_display_date_created($post_id);
            break;
        case 'last_revision':
            cac_auth_display_last_revision($post_id);
            break;
        case 'date_published':
            cac_auth_display_date_published($post_id);
            break;
    }
}

/**
 * Display date created and author
 */
function cac_auth_display_date_created($post_id) {
    $created_gmt = get_post_time('Y-m-d H:i:s', true, $post_id);
    $created_date = get_date_from_gmt($created_gmt, 'F j, Y - g:i a');
    $author_id = get_post_field('post_author', $post_id);
    $author_email = get_the_author_meta('user_email', $author_id);
    echo cac_auth_format_column_output($created_date, $author_email);
}

/**
 * Display last revision date and author
 */
function cac_auth_display_last_revision($post_id) {
    $post_status = get_post_status($post_id);
    if ($post_status == 'draft') {
        echo 'No Revisions';
    } else {
        $last_revision = wp_get_post_revisions($post_id);
        if (!empty($last_revision)) {
            $last_revision = array_shift($last_revision);
            $last_revision_gmt = get_post_modified_time('Y-m-d H:i:s', true, $last_revision);
            $last_revision_date = get_date_from_gmt($last_revision_gmt, 'F j, Y - g:i a');
            $reviser_id = $last_revision->post_author;
            $reviser_email = get_the_author_meta('user_email', $reviser_id);
            echo cac_auth_format_column_output($last_revision_date, $reviser_email);
        } else {
            echo 'No Revisions';
        }
    }
}

/**
 * Display date published and author
 */
function cac_auth_display_date_published($post_id) {
    $published_gmt = get_post_field('post_date_gmt', $post_id);
    if ($published_gmt && $published_gmt != '0000-00-00 00:00:00') {
        $published_date = get_date_from_gmt($published_gmt, 'F j, Y - g:i a');
        $author_id = get_post_field('post_author', $post_id);
        $author_email = get_the_author_meta('user_email', $author_id);
        echo cac_auth_format_column_output($published_date, $author_email);
    } else {
        echo '--';
    }
}

/**
 * Format column output
 */
function cac_auth_format_column_output($date, $email) {
    return sprintf(
        '<span style="font-size: 12px; text-transform: uppercase;">%s</span><br><span style="font-weight: 600; font-size: 13px; font-style: italic;">%s</span>',
        esc_html($date),
        esc_html($email)
    );
}

/**
 * Change 'Draft' post state to 'In Progress'
 */
function cac_auth_change_draft_to_in_progress($states) {
    if (get_post_status() == 'draft') {
        $states = array('In Progress');
    }
    return $states;
}