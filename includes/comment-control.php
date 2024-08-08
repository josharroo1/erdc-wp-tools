<?php
/**
 * Comment Control Functionality
 */

function cac_auth_disable_comments_functionality() {
    if (get_option('cac_auth_disable_comments', false)) {
        // Remove comment support from all post types
        foreach (get_post_types() as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }

        // Close comments on the front-end
        add_filter('comments_open', '__return_false', 20, 2);
        add_filter('pings_open', '__return_false', 20, 2);

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array', 10, 2);

        // Remove comments page from admin menu
        add_action('admin_menu', function() {
            remove_menu_page('edit-comments.php');
        });

        // Remove comments links from admin bar
        add_action('init', function () {
            if (is_admin_bar_showing()) {
                remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
            }
        });

        // Redirect any user trying to access comments page
        add_action('admin_init', function () {
            global $pagenow;
            
            if ($pagenow === 'edit-comments.php') {
                wp_safe_redirect(admin_url());
                exit;
            }
            // Remove comments metabox from dashboard
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        });

        // Disable comment feeds
        add_action('do_feed_rss2_comments', 'cac_auth_disable_comment_feed', 1);
        add_action('do_feed_atom_comments', 'cac_auth_disable_comment_feed', 1);

        // Remove comments from admin bar
        add_action('wp_before_admin_bar_render', function() {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('comments');
        });
    }
}
add_action('init', 'cac_auth_disable_comments_functionality', 9999);

function cac_auth_disable_comment_feed() {
    wp_die(__('Comments are closed.', 'cac-auth'), '', array('response' => 403));
}