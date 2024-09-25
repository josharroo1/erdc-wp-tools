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

                        // Send the custom HTML email
                        $sent = cac_auth_send_custom_password_reset_email($user, $reset_url);

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
            error_log('CAC Auth: Invalid security token in password reset.');
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
            } elseif (strlen($pass1) < 12) {  // Increased minimum length to 12 for better security
                $error_message = 'Password must be at least 12 characters long.';
            } else {
                // Retrieve the user by login
                $user = check_password_reset_key($post_key, $post_login);
                if (is_wp_error($user)) {
                    if ($user->get_error_code() === 'invalid_key') {
                        $error_message = 'Invalid reset key.';
                    } elseif ($user->get_error_code() === 'expired_key') {
                        $error_message = 'Reset key has expired.';
                    } else {
                        $error_message = 'Invalid reset key.';
                    }
                    error_log('CAC Auth: Invalid reset key - ' . $user->get_error_message());
                } else {
                    // Reset the password
                    $result = reset_password($user, $pass1);
                    if (is_wp_error($result)) {
                        $error_message = 'Failed to reset password: ' . $result->get_error_message();
                        error_log('CAC Auth: Failed to reset password - ' . $result->get_error_message());
                    } else {
                        $success_message = 'Your password has been reset successfully. You can now <a href="' . esc_url(wp_login_url()) . '">log in</a> with your new password.';
                        error_log('CAC Auth: Password reset successful for user ' . $post_login);
    
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
            #password-strength {
                margin-bottom: 0px;
                margin-top: 5px;
                font-weight: bold;
            }
            .very-weak { color: #ff0000; }
            .weak { color: #ff4500; }
            .medium { color: #ffa500; }
            .strong { color: #9acd32; }
            .very-strong { color: #006400; }
            #password-strength-meter {
                width: 100%;
                height: 15px;
                margin-top: 5px;
                background-color: #f0f0f0;
                margin-bottom: 25px;
                border-radius: 10px;
                overflow: hidden;
            }
            #password-strength-meter div {
                height: 100%;
                width: 0;
                transition: width 0.3s ease-in-out;
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
                echo '<div class="login-error">' . esc_html($error_message) . '</div>';
            }
            if (!empty($success_message)) {
                echo '<div class="login-success">' . $success_message . '</div>';
            }

            if (empty($success_message)) :
            ?>
            <form name="resetpasswordform" id="cac-reset-password-form" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>" method="post">
                <?php wp_nonce_field('cac_auth_reset_password_action', 'cac_auth_reset_password_nonce'); ?>
                <input type="password" name="pass1" id="pass1" placeholder="New Password" required>
                <input type="password" name="pass2" id="pass2" placeholder="Confirm New Password" required>
                <div id="password-strength">Password Strength: <span></span></div>
                <div id="password-strength-meter"><div></div></div>
                <input type="submit" name="wp-submit" id="wp-submit" value="Reset Password">
                <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>">
                <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">
            </form>
            <?php endif; ?>
            <div class="login-links">
                <a href="<?php echo esc_url(wp_login_url()); ?>">Back to Login</a>
            </div>
        </div>
        <script>
document.addEventListener('DOMContentLoaded', function() {
    // Cache DOM elements for better performance
    const passwordInput = document.getElementById('pass1');
    const confirmPasswordInput = document.getElementById('pass2');
    const strengthDisplay = document.querySelector('#password-strength span');
    const strengthMeter = document.querySelector('#password-strength-meter div');
    const submitButton = document.getElementById('wp-submit');

    // Configuration for password strength criteria
    const PASSWORD_CONFIG = {
        minLength: 8,
        maxLength: 32,
        patterns: {
            lowercase: /[a-z]/,
            uppercase: /[A-Z]/,
            numeric: /\d/,
            specialChar: /[^a-zA-Z\d]/,
            repeatedChars: /(.)\1{2,}/, // Three or more repeated characters
            sequentialChars: /abc|bcd|cde|def|efg|fgh|ghi|hij|ijk|jkl|klm|lmn|mno|nop|opq|pqr|qrs|rst|stu|tuv|uvw|vwx|wxy|xyz/i,
        },
        commonPasswords: ['password', '123456', 'qwerty', 'admin'],
        scoring: {
            length: 25,
            lowercase: 10,
            uppercase: 10,
            numeric: 10,
            specialChar: 10,
            noRepeatedChars: 10,
            noSequentialChars: 10,
            noCommonPasswords: 10,
        },
        strengthLevels: [
            { score: 80, label: 'Very Strong', class: 'very-strong', width: '100%' },
            { score: 60, label: 'Strong', class: 'strong', width: '80%' },
            { score: 40, label: 'Medium', class: 'medium', width: '60%' },
            { score: 20, label: 'Weak', class: 'weak', width: '40%' },
            { score: 0, label: 'Very Weak', class: 'very-weak', width: '20%' }
        ],
        minRequiredScore: 40 // Minimum score required to enable submission
    };

    /**
     * Calculates the strength score of the given password based on predefined criteria.
     * @param {string} password - The password to evaluate.
     * @returns {number} - The calculated strength score.
     */
    function calculatePasswordScore(password) {
        let score = 0;

        // Length scoring
        if (password.length >= PASSWORD_CONFIG.minLength) {
            const lengthScore = password.length >= PASSWORD_CONFIG.maxLength
                ? PASSWORD_CONFIG.scoring.length
                : Math.floor(((password.length - PASSWORD_CONFIG.minLength) / (PASSWORD_CONFIG.maxLength - PASSWORD_CONFIG.minLength)) * PASSWORD_CONFIG.scoring.length);
            score += lengthScore;
        } else {
            // If password is shorter than minLength, no need to evaluate further
            return 0;
        }

        // Character type scoring
        if (PASSWORD_CONFIG.patterns.lowercase.test(password)) score += PASSWORD_CONFIG.scoring.lowercase;
        if (PASSWORD_CONFIG.patterns.uppercase.test(password)) score += PASSWORD_CONFIG.scoring.uppercase;
        if (PASSWORD_CONFIG.patterns.numeric.test(password)) score += PASSWORD_CONFIG.scoring.numeric;
        if (PASSWORD_CONFIG.patterns.specialChar.test(password)) score += PASSWORD_CONFIG.scoring.specialChar;

        // Additional checks
        if (!PASSWORD_CONFIG.patterns.repeatedChars.test(password)) {
            score += PASSWORD_CONFIG.scoring.noRepeatedChars;
        }

        if (!PASSWORD_CONFIG.patterns.sequentialChars.test(password)) {
            score += PASSWORD_CONFIG.scoring.noSequentialChars;
        }

        const isCommon = PASSWORD_CONFIG.commonPasswords.some(commonPwd => password.toLowerCase().includes(commonPwd));
        if (!isCommon) {
            score += PASSWORD_CONFIG.scoring.noCommonPasswords;
        }

        return score;
    }

    /**
     * Determines the strength level based on the score.
     * @param {number} score - The password strength score.
     * @returns {object} - The strength level object containing label, class, and width.
     */
    function getStrengthLevel(score) {
        for (let level of PASSWORD_CONFIG.strengthLevels) {
            if (score >= level.score) {
                return level;
            }
        }
        // Default to the lowest strength level
        return PASSWORD_CONFIG.strengthLevels[PASSWORD_CONFIG.strengthLevels.length - 1];
    }

    /**
     * Updates the password strength meter and messages based on the current password.
     */
    function updatePasswordStrength() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        const score = calculatePasswordScore(password);

        // Determine strength level
        const strengthLevel = getStrengthLevel(score);

        // Update strength display
        strengthDisplay.textContent = strengthLevel.label;
        strengthDisplay.className = strengthLevel.class;
        strengthMeter.style.width = strengthLevel.width;
        strengthMeter.style.backgroundColor = getComputedStyle(strengthDisplay).color;

        // Validate password confirmation
        if (confirmPassword) {
            if (password !== confirmPassword) {
                strengthDisplay.textContent = 'Passwords do not match';
                strengthDisplay.className = 'very-weak';
                strengthMeter.style.width = '0';
                submitButton.disabled = true;
                return;
            }
        }

        // Enable or disable submit button based on strength and confirmation
        submitButton.disabled = score < PASSWORD_CONFIG.minRequiredScore;
    }

    // Event listeners for real-time validation
    passwordInput.addEventListener('input', updatePasswordStrength);
    confirmPasswordInput.addEventListener('input', updatePasswordStrength);
});
</script>


        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Sends a custom HTML password reset email.
 *
 * @param WP_User $user The user object.
 * @param string  $reset_url The password reset URL.
 * @return bool Whether the email was sent successfully.
 */
function cac_auth_send_custom_password_reset_email($user, $reset_url) {
    $custom_logo_url = get_option('cac_auth_custom_login_logo', '');
    $default_logo_url = CAC_AUTH_PLUGIN_URL . 'includes/assets/images/default-logo.png';
    $logo_url = $custom_logo_url ? $custom_logo_url : $default_logo_url;

    $subject = sprintf(__('Password Reset Request for %s'), get_bloginfo('name'));

    $html_message = '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset Request</title>
    </head>
    <body style="font-family: Arial, sans-serif; font-size: 14px; color: #333333; line-height: 1.6; margin: 0; padding: 0; background-color: #f0f2f5;">
        <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f0f2f5;">
            <tr>
                <td align="center" style="padding: 20px 0;">
                    <table cellpadding="0" cellspacing="0" border="0" width="600" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0px 2px 6px 0px #0000001c;">
                        <tr>
                            <td style="padding: 40px;">
                                <table cellpadding="0" cellspacing="0" border="0" width="100%">
                                    <tr>
                                        <td align="center" style="padding-bottom: 20px;">
                                            <img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . ' Logo" style="max-width: 200px; height: auto;">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 16px; color: #333333;">
                                            <p>Hi ' . esc_html($user->user_login) . ',</p>
                                            <p>You recently requested to reset your password for your account at <strong>' . esc_html(get_bloginfo('name')) . '</strong>.</p>
                                            <p>If this was a mistake, just ignore this email and no changes will be made.</p>
                                            <p>To reset your password, click the button below:</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="padding: 20px 0;">
                                            <a href="' . esc_url($reset_url) . '" style="display: inline-block; padding: 12px 24px; background-color: #1e1e1e; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">Reset Password</a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-size: 11px; color: #999999;">
                                            <p>If the button above does not work, copy and paste the following link into your browser:</p>
                                            <p><a href="' . esc_url($reset_url) . '" style="color: #1e1e1e; text-decoration: underline;">' . esc_html($reset_url) . '</a></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 30px; font-size: 12px; color: #999999;">
                                            <p>Thanks,<br>' . esc_html(get_bloginfo('name')) . ' Team</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    add_filter('wp_mail_content_type', 'cac_auth_set_html_content_type');
    $sent = wp_mail($user->user_email, $subject, $html_message);
    remove_filter('wp_mail_content_type', 'cac_auth_set_html_content_type');

    return $sent;
}

/**
 * Sets the email content type to HTML.
 *
 * @return string The content type.
 */
function cac_auth_set_html_content_type() {
    return 'text/html';
}
