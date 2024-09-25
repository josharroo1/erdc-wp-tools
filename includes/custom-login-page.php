<?php
/**
 * Custom Login Page for ERDC WP Tools
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function cac_auth_custom_login_page() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        wp_redirect(home_url());
        exit;
    }

    $custom_logo_url = get_option('cac_auth_custom_login_logo', '');
    $default_logo_url = plugins_url('includes/assets/images/default-logo.png', dirname(__FILE__));
    $logo_url = $custom_logo_url ? $custom_logo_url : $default_logo_url;

    $cac_auth_url = plugins_url('cac-auth-endpoint.php', dirname(__FILE__));

    $error_message = '';

    // Determine the redirect URL based on settings
    $redirect_setting = get_option('cac_auth_redirect_page', 'wp-admin'); // Default to 'wp-admin' if not set

    if ($redirect_setting === 'wp-admin') {
        $redirect_url = admin_url();
    } elseif (is_numeric($redirect_setting)) {
        $redirect_url = get_permalink($redirect_setting);
        // Fallback to home_url() if the page doesn't exist
        if (!$redirect_url) {
            $redirect_url = home_url();
        }
    } else {
        $redirect_url = home_url();
    }

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log'], $_POST['pwd'])) {
        // Verify nonce for security
        if (!isset($_POST['cac_auth_login_nonce']) || !wp_verify_nonce($_POST['cac_auth_login_nonce'], 'cac_auth_login_action')) {
            $error_message = 'Invalid security token.';
        } else {
            // Sanitize user input
            $username = sanitize_user($_POST['log']);
            $password = $_POST['pwd']; // Passwords should not be sanitized to allow all characters

            $creds = array(
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => isset($_POST['rememberme']),
            );

            $user = wp_signon($creds, is_ssl());

            if (is_wp_error($user)) {
                $error_message = $user->get_error_message();
            } else {
                wp_redirect($redirect_url);
                exit;
            }
        }
    }

    // Custom login page HTML
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html(get_bloginfo('name')); ?> - Login</title>
        <?php wp_head(); ?>
        <style>
            /* Your existing CSS styles */
            body {
                background-color: #f0f2f5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-container {
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
                padding: 40px;
                width: 100%;
                max-width: 400px;
            }
            .login-logo {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-logo img {
                max-width: 200px;
                height: auto;
            }
            #cac-login-form {
                display: flex;
                flex-direction: column;
            }
            #cac-login-form input[type="text"],
            #cac-login-form input[type="password"] {
                border: 1px solid #dddfe2;
                border-radius: 6px;
                font-size: 16px;
                padding: 14px 16px;
                margin-bottom: 15px;
            }
            #cac-login-form input[type="submit"],
            .cac-login-button {
                background-color: #1e1e1e;
                border: none;
                border-radius: 6px;
                color: #ffffff;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                padding: 14px 16px;
                transition: background-color 0.3s;
            }
            #cac-login-form input[type="submit"]:hover,
            .cac-login-button:hover {
                background-color: #333333;
                color: white;
            }
            .login-remember {
                display: flex;
                align-items: center;
                margin-bottom: 15px;
            }
            .login-remember input[type="checkbox"] {
                margin-right: 8px;
            }
            .login-separator {
                border-bottom: 1px solid #dadde1;
                margin: 20px 0;
                text-align: center;
            }
            .login-separator span {
                background-color: #ffffff;
                padding: 0 10px;
                position: relative;
                top: 10px;
                color: #96999e;
            }
            .cac-login-button-wrapper {
                text-align: center;
            }
            .cac-login-button {
                display: inline-block;
                text-decoration: none;
                margin-top: 15px;
            }
            .login-error {
                background-color: #ffebe8;
                border: 1px solid #c00;
                color: #333;
                margin-bottom: 16px;
                padding: 12px;
                border-radius: 6px;
            }
            .login-error a {
                color: #484848;
                text-decoration: underline;
            }
            .login-links {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
            }
            .login-links a {
                color: #1e1e1e;
                text-decoration: none;
            }
            .login-links a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?> Logo">
            </div>
            <?php
            if (!empty($error_message)) {
                echo '<div class="login-error">' . $error_message . '</div>';
            }
            ?>
            <form name="loginform" id="cac-login-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <?php wp_nonce_field('cac_auth_login_action', 'cac_auth_login_nonce'); ?>
                <input type="text" name="log" id="user_login" placeholder="Username" required>
                <input type="password" name="pwd" id="user_pass" placeholder="Password" required>
                <div class="login-remember">
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                    <label for="rememberme">Remember Me</label>
                </div>
                <input type="submit" name="wp-submit" id="wp-submit" value="Log In">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url($redirect_url); ?>">
            </form>
            <div class="login-separator">
                <span>or</span>
            </div>
            <div class="cac-login-button-wrapper">
                <a href="<?php echo esc_url($cac_auth_url); ?>" class="cac-login-button">Login with CAC</a>
            </div>
            <div class="login-links">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Lost your password?</a>
            </div>
        </div>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit;
}

// Function to handle login errors
function cac_auth_login_errors($errors) {
    if (empty($errors)) {
        return $errors;
    }

    $error_codes = $errors->get_error_codes();
    $custom_errors = array(
        'invalid_username'   => 'Invalid username or password.',
        'incorrect_password' => 'Invalid username or password.',
        'empty_username'     => 'Please enter a username.',
        'empty_password'     => 'Please enter a password.',
    );

    foreach ($error_codes as $code) {
        if (isset($custom_errors[$code])) {
            $errors->remove($code);
            $errors->add($code, $custom_errors[$code]);
        }
    }

    return $errors;
}
add_filter('wp_login_errors', 'cac_auth_login_errors');

function cac_auth_custom_forgot_password_page() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        wp_redirect(home_url());
        exit;
    }

    // Define the plugin URL constant if not already defined
    if (!defined('CAC_AUTH_PLUGIN_URL')) {
        define('CAC_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    $cac_auth_url = CAC_AUTH_PLUGIN_URL . 'cac-auth-endpoint.php';
    $error_message = '';
    $success_message = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_email'])) {
        // Verify nonce for security
        if (!isset($_POST['cac_auth_forgot_password_nonce']) || !wp_verify_nonce($_POST['cac_auth_forgot_password_nonce'], 'cac_auth_forgot_password_action')) {
            $error_message = 'Invalid security token.';
        } else {
            // Sanitize email input
            $user_email = sanitize_email($_POST['user_email']);

            if (empty($user_email)) {
                $error_message = 'Please enter your email address.';
            } elseif (!is_email($user_email)) {
                $error_message = 'Please enter a valid email address.';
            } else {
                // Attempt to retrieve the user by email
                $user = get_user_by('email', $user_email);

                if (!$user) {
                    $error_message = 'There is no user registered with that email address.';
                } else {
                    // Generate a password reset key
                    $reset_key = get_password_reset_key($user);

                    if (is_wp_error($reset_key)) {
                        $error_message = 'An error occurred while generating a reset link. Please try again.';
                    } else {
                        // Construct the reset URL
                        $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');

                        // Prepare email
                        $message = __('Someone requested that the password be reset for the following account:') . "\r\n\r\n";
                        $message .= network_home_url('/') . "\r\n\r\n";
                        $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
                        $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
                        $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
                        $message .= '<' . $reset_url . ">\r\n";

                        // Send email
                        $sent = wp_mail($user_email, __('Password Reset Request'), $message);

                        if ($sent) {
                            $success_message = 'A password reset link has been sent to your email address.';
                        } else {
                            $error_message = 'Failed to send password reset email. Please try again later.';
                        }
                    }
                }
            }
        }
    }

    // Custom forgot password page HTML
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html(get_bloginfo('name')); ?> - Forgot Password</title>
        <?php wp_head(); ?>
        <style>
            /* Reuse the same CSS styles as the login page for consistency */
            body {
                background-color: #f0f2f5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-container {
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
                padding: 40px;
                width: 100%;
                max-width: 400px;
            }
            .login-logo {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-logo img {
                max-width: 200px;
                height: auto;
            }
            #cac-forgot-password-form {
                display: flex;
                flex-direction: column;
            }
            #cac-forgot-password-form input[type="email"] {
                border: 1px solid #dddfe2;
                border-radius: 6px;
                font-size: 16px;
                padding: 14px 16px;
                margin-bottom: 15px;
            }
            #cac-forgot-password-form input[type="submit"] {
                background-color: #1e1e1e;
                border: none;
                border-radius: 6px;
                color: #ffffff;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                padding: 14px 16px;
                transition: background-color 0.3s;
            }
            #cac-forgot-password-form input[type="submit"]:hover {
                background-color: #333333;
                color: white;
            }
            .login-error {
                background-color: #ffebe8;
                border: 1px solid #c00;
                color: #333;
                margin-bottom: 16px;
                padding: 12px;
                border-radius: 6px;
            }
            .login-success {
                background-color: #e6ffed;
                border: 1px solid #46a049;
                color: #333;
                margin-bottom: 16px;
                padding: 12px;
                border-radius: 6px;
            }
            .login-links {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
            }
            .login-links a {
                color: #1e1e1e;
                text-decoration: none;
            }
            .login-links a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-logo">
                <img src="<?php echo esc_url(get_option('cac_auth_custom_login_logo', CAC_AUTH_PLUGIN_URL . 'includes/assets/images/default-logo.png')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?> Logo">
            </div>
            <?php
            if (!empty($error_message)) {
                echo '<div class="login-error">' . esc_html($error_message) . '</div>';
            }
            if (!empty($success_message)) {
                echo '<div class="login-success">' . esc_html($success_message) . '</div>';
            }
            ?>
            <form name="forgotpasswordform" id="cac-forgot-password-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <?php wp_nonce_field('cac_auth_forgot_password_action', 'cac_auth_forgot_password_nonce'); ?>
                <input type="email" name="user_email" id="user_email" placeholder="Email Address" required>
                <input type="submit" name="wp-submit" id="wp-submit" value="Reset Password">
            </form>
            <div class="login-links">
                <a href="<?php echo esc_url(wp_login_url()); ?>">Back to Login</a>
            </div>
        </div>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Render Custom Reset Password Page
 */
function cac_auth_custom_reset_password_page() {
    // Check if user is already logged in
    if (is_user_logged_in()) {
        wp_redirect(home_url());
        exit;
    }

    // Define the plugin URL constant if not already defined
    if (!defined('CAC_AUTH_PLUGIN_URL')) {
        define('CAC_AUTH_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    // Retrieve the logo URL
    $custom_logo_url = get_option('cac_auth_custom_login_logo', '');
    $default_logo_url = CAC_AUTH_PLUGIN_URL . 'includes/assets/images/default-logo.png';
    $logo_url = $custom_logo_url ? $custom_logo_url : $default_logo_url;

    // Retrieve the redirect page setting
    $redirect_page = get_option('cac_auth_redirect_page', 'wp-admin');

    $error_message = '';
    $success_message = '';

    // Retrieve key and login from URL
    $key = isset($_GET['key']) ? sanitize_text_field($_GET['key']) : '';
    $login = isset($_GET['login']) ? sanitize_user($_GET['login']) : '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass1'], $_POST['pass2'], $_POST['key'], $_POST['login'])) {
        // Verify nonce for security
        if (!isset($_POST['cac_auth_reset_password_nonce']) || !wp_verify_nonce($_POST['cac_auth_reset_password_nonce'], 'cac_auth_reset_password_action')) {
            $error_message = 'Invalid security token.';
        } else {
            // Sanitize and validate passwords
            $pass1 = $_POST['pass1'];
            $pass2 = $_POST['pass2'];
            $post_key = sanitize_text_field($_POST['key']);
            $post_login = sanitize_user($_POST['login']);

            if (empty($pass1) || empty($pass2)) {
                $error_message = 'Please enter both password fields.';
            } elseif ($pass1 !== $pass2) {
                $error_message = 'Passwords do not match.';
            } elseif (strlen($pass1) < 8) {
                $error_message = 'Password must be at least 8 characters long.';
            } else {
                // Retrieve the user by login
                $user = get_user_by('login', $post_login);
                if (!$user) {
                    $error_message = 'Invalid username.';
                } else {
                    // Validate the reset key
                    $valid_key = check_password_reset_key($post_key, $user->user_login);
                    if (is_wp_error($valid_key)) {
                        if ($valid_key->get_error_code() === 'invalid_key') {
                            $error_message = 'Invalid reset key.';
                        } elseif ($valid_key->get_error_code() === 'expired_key') {
                            $error_message = 'Reset key has expired.';
                        } else {
                            $error_message = 'Invalid reset key.';
                        }
                    } else {
                        // Reset the password
                        reset_password($user, $post_key, $pass1);
                        $success_message = 'Your password has been reset successfully. You can now <a href="' . esc_url(wp_login_url()) . '">log in</a> with your new password.';

                        // Redirect based on plugin settings after 5 seconds
                        ?>
                        <script>
                            setTimeout(function(){
                                <?php
                                if ($redirect_page === 'wp-admin') {
                                    echo "window.location.href = '" . esc_url(admin_url()) . "';";
                                } elseif (!empty($redirect_page)) {
                                    $page = get_post($redirect_page);
                                    if ($page) {
                                        echo "window.location.href = '" . esc_url(get_permalink($page->ID)) . "';";
                                    } else {
                                        echo "window.location.href = '" . esc_url(home_url()) . "';";
                                    }
                                } else {
                                    echo "window.location.href = '" . esc_url(home_url()) . "';";
                                }
                                ?>
                            }, 5000); // Redirect after 5 seconds
                        </script>
                        <?php
                    }
                }
            }
        }
    }

    // Custom Reset Password page HTML
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html(get_bloginfo('name')); ?> - Reset Password</title>
        <?php wp_head(); ?>
        <style>
            /* Embedded CSS for Consistent Styling */

            /* Body Styles */
            body {
                background-color: #f0f2f5;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }

            /* Container Styles */
            .login-container {
                background-color: #ffffff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
                padding: 40px;
                width: 100%;
                max-width: 400px;
            }

            /* Logo Styles */
            .login-logo {
                text-align: center;
                margin-bottom: 30px;
            }

            .login-logo img {
                max-width: 200px;
                height: auto;
            }

            /* Form Styles */
            #cac-reset-password-form {
                display: flex;
                flex-direction: column;
            }

            #cac-reset-password-form input[type="password"] {
                border: 1px solid #dddfe2;
                border-radius: 6px;
                font-size: 16px;
                padding: 14px 16px;
                margin-bottom: 15px;
            }

            #cac-reset-password-form input[type="submit"] {
                background-color: #1e1e1e;
                border: none;
                border-radius: 6px;
                color: #ffffff;
                cursor: pointer;
                font-size: 16px;
                font-weight: bold;
                padding: 14px 16px;
                transition: background-color 0.3s;
            }

            #cac-reset-password-form input[type="submit"]:hover {
                background-color: #333333;
                color: white;
            }

            /* Password Strength Meter */
            #password-strength {
                font-size: 14px;
                margin-bottom: 15px;
            }

            /* Error and Success Messages */
            .login-error {
                background-color: #ffebe8;
                border: 1px solid #c00;
                color: #333;
                margin-bottom: 16px;
                padding: 12px;
                border-radius: 6px;
            }

            .login-success {
                background-color: #e6ffed;
                border: 1px solid #46a049;
                color: #333;
                margin-bottom: 16px;
                padding: 12px;
                border-radius: 6px;
            }

            /* Links Styles */
            .login-links {
                text-align: center;
                margin-top: 20px;
                font-size: 14px;
            }

            .login-links a {
                color: #1e1e1e;
                text-decoration: none;
            }

            .login-links a:hover {
                text-decoration: underline;
            }
        </style>
            <script>
        // Password Strength Meter JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            var pass1 = document.getElementById('pass1');
            var strengthDiv = document.getElementById('password-strength');

            pass1.addEventListener('input', function() {
                var strength = wp.passwordStrength.meter(pass1.value, null, 'password');
                var strengthText = '';
                switch(strength) {
                    case 0:
                    case 1:
                        strengthText = 'Weak';
                        strengthDiv.style.color = '#c00';
                        break;
                    case 2:
                        strengthText = 'Medium';
                        strengthDiv.style.color = '#e6b800';
                        break;
                    case 3:
                    case 4:
                        strengthText = 'Strong';
                        strengthDiv.style.color = '#46a049';
                        break;
                }
                strengthDiv.textContent = 'Password Strength: ' + strengthText;
            });
        });
    </script>
    </head>
    <body>
        <div class="login-container">
            <div class="login-logo">
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?> Logo">
            </div>
            <?php
            if (!empty($error_message)) {
                echo '<div class="login-error">' . esc_html($error_message) . '</div>';
            }
            if (!empty($success_message)) {
                echo '<div class="login-success">' . $success_message . '</div>'; // Allow HTML in success message
            }

            // Only show the form if there's no success message
            if (empty($success_message)) :
            ?>
            <form name="resetpasswordform" id="cac-reset-password-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <?php wp_nonce_field('cac_auth_reset_password_action', 'cac_auth_reset_password_nonce'); ?>
                <input type="password" name="pass1" id="pass1" placeholder="New Password" required>
                <div id="password-strength">Password Strength: </div>
                <input type="password" name="pass2" id="pass2" placeholder="Confirm New Password" required>
                <input type="submit" name="wp-submit" id="wp-submit" value="Reset Password">
                <input type="hidden" name="action" value="resetpass">
                <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
                <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            </form>
            <?php endif; ?>
            <div class="login-links">
                <a href="<?php echo esc_url(wp_login_url()); ?>">Back to Login</a>
            </div>
        </div>
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Customize Password Reset Email Content
 *
 * @param string $message The email message.
 * @param string $key     The password reset key.
 * @param string $user_login The user's login name.
 * @param WP_User $user_data The WP_User object.
 * @return string Modified email message.
 */
function cac_auth_custom_password_reset_email($message, $key, $user_login, $user_data) {
    // Construct the reset URL
    $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login), 'login');

    // Customize the email content
    $message = sprintf(__('Hi %s,'), $user_login) . "\r\n\r\n";
    $message .= __('You requested a password reset for your account at ') . get_bloginfo('name') . " (" . network_home_url('/') . ").\r\n\r\n";
    $message .= __('If this was a mistake, just ignore this email and nothing will happen.') . "\r\n\r\n";
    $message .= __('To reset your password, visit the following address:') . "\r\n\r\n";
    $message .= '<' . $reset_url . ">\r\n\r\n";
    $message .= __('Thanks!') . "\r\n";

    return $message;
}
add_filter('retrieve_password_message', 'cac_auth_custom_password_reset_email', 10, 4);

/**
 * Redirect User After Successful Password Reset
 *
 * @param WP_User $user The WP_User object.
 * @param string  $new_pass The new password.
 */
function cac_auth_after_password_reset($user, $new_pass) {
    $redirect_page = get_option('cac_auth_redirect_page', 'wp-admin');

    if ($redirect_page === 'wp-admin') {
        wp_safe_redirect(admin_url());
    } elseif (!empty($redirect_page)) {
        $page = get_post($redirect_page);
        if ($page) {
            wp_safe_redirect(get_permalink($page->ID));
        } else {
            wp_safe_redirect(home_url());
        }
    } else {
        wp_safe_redirect(home_url());
    }
    exit;
}
add_action('password_reset', 'cac_auth_after_password_reset', 10, 2);

/**
 * Enqueue Password Strength Scripts on Custom Reset Password Page
 */
function cac_auth_enqueue_password_strength_scripts() {
    // Only enqueue on custom reset password page
    global $pagenow, $action;
    if ($pagenow === 'wp-login.php' && ($action === 'rp' || $action === 'resetpass')) {
        wp_enqueue_script('zxcvbn');
        wp_enqueue_script('password-strength-meter');
    }
}
add_action('wp_enqueue_scripts', 'cac_auth_enqueue_password_strength_scripts');
