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
    $default_logo_url = plugins_url('assets/images/default-logo.png', dirname(__FILE__));
    $logo_url = $custom_logo_url ? $custom_logo_url : $default_logo_url;

    $cac_auth_url = plugins_url('cac-auth-endpoint.php', dirname(__FILE__));
    
    $error_message = '';

    // Check if the form is submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log'], $_POST['pwd'])) {
        $creds = array(
            'user_login'    => wp_unslash($_POST['log']),
            'user_password' => $_POST['pwd'],
            'remember'      => isset($_POST['rememberme']),
        );

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            $error_message = $user->get_error_message();
        } else {
            wp_redirect(home_url());
            exit;
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
                echo '<div class="login-error">' . esc_html($error_message) . '</div>';
            }
            ?>
            <form name="loginform" id="cac-login-form" action="<?php echo esc_url(wp_login_url()); ?>" method="post">
                <input type="text" name="log" id="user_login" placeholder="Username" required>
                <input type="password" name="pwd" id="user_pass" placeholder="Password" required>
                <div class="login-remember">
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever">
                    <label for="rememberme">Remember Me</label>
                </div>
                <input type="submit" name="wp-submit" id="wp-submit" value="Log In">
                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">
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
        'invalid_username' => 'Invalid username or password.',
        'incorrect_password' => 'Invalid username or password.',
        'empty_username' => 'Please enter a username.',
        'empty_password' => 'Please enter a password.',
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