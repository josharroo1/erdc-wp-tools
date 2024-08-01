<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(get_bloginfo('name')); ?> - CAC Login</title>
    <?php wp_head(); ?>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: white; padding: 2em; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>CAC Authentication Required</h1>
        <p>Please ensure your CAC is inserted and <a href="<?php echo esc_url(wp_login_url()); ?>">try again</a>.</p>
        <p>If you're unable to use CAC authentication, please contact your administrator.</p>
    </div>
    <?php wp_footer(); ?>
</body>
</html>