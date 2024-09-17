<?php
/**
 * CAC Authentication Admin Functions
 */

// Add custom registration fields section
function cac_auth_custom_fields_section_callback() {
    echo '<p>Add custom fields to the CAC registration form.</p><p>For "select" field types, enter select options as a comma separated list OR upload a CSV file with "key" & "value" columns.</p><p><a href="#">Download Example CSV</a></p>';
}

// Render custom registration fields
function cac_auth_render_custom_fields() {
    $custom_fields = get_option('cac_auth_registration_fields', array());
    if (!is_array($custom_fields)) {
        $custom_fields = array();
    }
    ?>
    <div class="form-information">To display the CAC registration form on a page or post, use the following shortcode: <code>[cac_registration]</code></div>
    <div class="form-information">Users will fill out the form, including any custom fields you have defined below, and register using their CAC credentials.</div>
    <div class="form-information"><strong>An organization email is always required</strong></div>
    <div class="csv-information">For select fields, add options as a comma-separated list, or a CSV file upload with "key" & "value" columns.</div>
    <table class="cac-auth-custom-fields">
        <thead>
            <tr>
                <th>Field Label</th>
                <th>Field Type</th>
                <th>Options (for select field)</th>
                <th>CSV File (for select field)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($custom_fields as $field_id => $field_data) : ?>
                <?php
                if (!is_array($field_data)) {
                    $field_data = array(
                        'label' => '',
                        'type' => 'text',
                        'options' => '',
                    );
                }
                // Retrieve the CSV file information
                $csv_file = get_option('cac_auth_csv_file_' . $field_id, '');
                ?>
                <tr>
                    <td><input type="text" name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][label]" value="<?php echo esc_attr($field_data['label']); ?>"></td>
                    <td>
                        <select name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][type]">
                            <option value="text" <?php selected($field_data['type'], 'text'); ?>>Text</option>
                            <option value="number" <?php selected($field_data['type'], 'number'); ?>>Number</option>
                            <option value="select" <?php selected($field_data['type'], 'select'); ?>>Select</option>
                        </select>
                    </td>
                    <td><input type="text" name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][options]" value="<?php echo esc_attr($field_data['options']); ?>" placeholder="Options (comma-separated)" class="cac-auth-options-input <?php echo $field_data['type'] !== 'select' ? 'disabled' : ''; ?>"></td>
                    <td>
                        <input type="file" name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][csv_file]" accept=".csv" class="cac-auth-options-input <?php echo $field_data['type'] !== 'select' ? 'disabled' : ''; ?>">
                        <?php if (!empty($csv_file)) : ?>
                            <span class="small-desc">
                                Current file: <?php echo esc_html($csv_file); ?>
                                <button type="button" class="button button-small cac-auth-remove-csv" data-field-id="<?php echo esc_attr($field_id); ?>">&times;</button>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><button type="button" class="button button-secondary cac-auth-remove-field">Remove</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="button" class="button button-secondary cac-auth-add-field">Add Field</button>
    <?php
}

// Save custom registration fields
function cac_auth_save_custom_fields($options) {
    error_log('Entering cac_auth_save_custom_fields function');
    error_log(print_r($options, true));
    error_log(print_r($_FILES, true));

    if (isset($_POST['cac_auth_registration_fields'])) {
        error_log('$_POST[cac_auth_registration_fields] is set');

        $custom_fields = array();
        foreach ($_POST['cac_auth_registration_fields'] as $field_id => $field_data) {
            error_log('Processing field ID: ' . $field_id);

            $field_label = sanitize_text_field($field_data['label']);
            $field_type = sanitize_text_field($field_data['type']);
            $field_options = sanitize_text_field($field_data['options']);

            // Check if a new CSV file is uploaded
            if ($field_type === 'select' && isset($_FILES['cac_auth_registration_fields']['name'][$field_id]['csv_file']) && !empty($_FILES['cac_auth_registration_fields']['name'][$field_id]['csv_file'])) {
                error_log('Processing CSV file for field ID: ' . $field_id);

                $csv_file_name = $_FILES['cac_auth_registration_fields']['name'][$field_id]['csv_file'];
                if (!empty($csv_file_name)) {
                    // Generate a unique file name to prevent collisions
                    $unique_file_name = $field_id . '_' . sanitize_file_name($csv_file_name);
                    $upload_dir = wp_upload_dir();
                    $target_dir = trailingslashit($upload_dir['basedir']) . 'cac-auth-csv-files/';
                    $target_file = $target_dir . $unique_file_name;

                    if (!file_exists($target_dir)) {
                        wp_mkdir_p($target_dir);
                    }

                    if (move_uploaded_file($_FILES['cac_auth_registration_fields']['tmp_name'][$field_id]['csv_file'], $target_file)) {
                        // Store the CSV file information separately
                        update_option('cac_auth_csv_file_' . $field_id, $unique_file_name);
                        error_log('File uploaded successfully: ' . $target_file);
                    } else {
                        error_log('Failed to move uploaded file: ' . $csv_file_name);
                    }
                }
            }

            $custom_fields[$field_id] = array(
                'label' => $field_label,
                'type' => $field_type,
                'options' => $field_options,
            );
        }

        $options = $custom_fields;
        error_log(print_r($options, true));
    } else {
        error_log('$_POST[cac_auth_registration_fields] is not set');
    }

    error_log('Leaving cac_auth_save_custom_fields function');
    return $options;
}
add_filter('cac_auth_settings_sanitize', 'cac_auth_save_custom_fields');

// Enqueue admin scripts
function cac_auth_admin_enqueue_scripts($hook) {
    if ('settings_page_cac-auth-settings' !== $hook) {
        return;
    }
    wp_enqueue_media();

    wp_enqueue_script('wp-color-picker');

    // Enqueue color picker styles
    wp_enqueue_style('wp-color-picker');

    // Initialize color picker
    $script = '
        jQuery(document).ready(function($) {
            $(".cac-color-picker").wpColorPicker();
        });
    ';
    wp_add_inline_script('wp-color-picker', $script);
    
    wp_enqueue_style('cac-auth-styles', CAC_AUTH_PLUGIN_URL . 'includes/assets/css/cac-admin-style.css', array(), CAC_AUTH_PLUGIN_VERSION);
    wp_enqueue_script('cac-auth-admin', CAC_AUTH_PLUGIN_URL . 'includes/assets/js/cac-auth-admin.js', array('jquery', 'media-upload', 'thickbox', 'wp-color-picker'), CAC_AUTH_PLUGIN_VERSION, true);

    // Localize the script with new data
    $script_data = array(
        'choose_image' => __('Choose or Upload an Image', 'cac-auth'),
    );
    wp_localize_script('cac-auth-admin', 'cacAuthData', $script_data);
}
add_action('admin_enqueue_scripts', 'cac_auth_admin_enqueue_scripts');


// Display additional user meta from CAC registration
add_action('show_user_profile', 'cac_show_additional_user_meta');
add_action('edit_user_profile', 'cac_show_additional_user_meta');

function cac_show_additional_user_meta($user) {
    $custom_fields = get_option('cac_auth_registration_fields', array());
    $user_meta = get_user_meta($user->ID);

    echo '<div class="cac-additional-info">';
    echo '<h3>Additional Information</h3>';

    foreach ($custom_fields as $field_id => $field_data) {
        $meta_key = 'cac_field_' . strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $field_data['label'])));
        $field_value = isset($user_meta[$meta_key][0]) ? maybe_unserialize($user_meta[$meta_key][0]) : '';
        $field_label = isset($field_data['label']) ? $field_data['label'] : '';
        $field_type = isset($field_data['type']) ? $field_data['type'] : 'text';

        echo '<div class="cac-field-row">';
        echo '<label for="' . esc_attr($meta_key) . '">' . esc_html($field_label) . '</label>';

        switch ($field_type) {
            case 'text':
            case 'number':
                echo '<input type="' . esc_attr($field_type) . '" name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" value="' . esc_attr($field_value) . '" class="regular-text">';
                break;
            case 'select':
                echo '<select name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '">';
                $options = array();
                $csv_file = get_option('cac_auth_csv_file_' . $field_id, '');
                if (!empty($csv_file)) {
                    $upload_dir = wp_upload_dir();
                    $csv_path = $upload_dir['basedir'] . '/cac-auth-csv-files/' . $csv_file;
                    if (file_exists($csv_path)) {
                        $csv_data = array_map('str_getcsv', file($csv_path));
                        array_walk($csv_data, function(&$a) use ($csv_data) {
                            $a = array_combine($csv_data[0], $a);
                        });
                        array_shift($csv_data);
                        foreach ($csv_data as $row) {
                            $options[$row['key']] = $row['value'];
                        }
                    }
                } else {
                    $field_options = isset($field_data['options']) ? $field_data['options'] : '';
                    $options = array_map('trim', explode(',', $field_options));
                    $options = array_combine($options, $options);
                }
                foreach ($options as $key => $value) {
                    echo '<option value="' . esc_attr($value) . '" ' . selected($field_value, $value, false) . '>' . esc_html($key) . '</option>';
                }
                echo '</select>';
                break;
            default:
                echo '<input type="text" name="' . esc_attr($meta_key) . '" id="' . esc_attr($meta_key) . '" value="' . esc_attr($field_value) . '" class="regular-text">';
                break;
        }
        echo '</div>';
    }

    echo '</div>'; // Close .cac-additional-info
}

// Save additional user meta from CAC registration
function cac_save_additional_user_meta($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    $custom_fields = get_option('cac_auth_registration_fields', array());

    foreach ($custom_fields as $field_data) {
        $meta_key = 'cac_field_' . strtolower(str_replace(' ', '_', preg_replace('/[^A-Za-z0-9 ]/', '', $field_data['label'])));
        if (isset($_POST[$meta_key])) {
            update_user_meta($user_id, $meta_key, sanitize_text_field($_POST[$meta_key]));
        }
    }
}
add_action('personal_options_update', 'cac_save_additional_user_meta');
add_action('edit_user_profile_update', 'cac_save_additional_user_meta');

// Custom admin styles
function cac_admin_styles() {
    echo '<style>
        .cac-additional-info {
            background-color: #ffffff;
            border: 1px solid #ccd0d4;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
        }
        .cac-field-row {
            margin-bottom: 20px;
        }
        .cac-field-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .cac-input {
            width: 100%;
            max-width: 400px;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccd0d4;
            box-sizing: border-box;
        }
        .cac-additional-info h3 {
            text-transform: uppercase;
        }
    </style>';
}
add_action('admin_head', 'cac_admin_styles');

function cac_auth_add_dod_id_field($user) {
    // Check if the current user can edit other users
    if (!current_user_can('edit_users')) {
        return;
    }

    // Check if the user already has a hashed DoD ID
    $hashed_dod_id = get_user_meta($user->ID, 'hashed_dod_id', true);

    if (empty($hashed_dod_id)) {
        ?>
        <h3><?php _e('CAC Authentication', 'cac-auth'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="dod_id"><?php _e('DoD ID Number', 'cac-auth'); ?></label></th>
                <td>
                    <input type="text" name="dod_id" id="dod_id" value="" class="regular-text" />
                    <p class="description"><?php _e('Enter the user\'s DoD ID number to associate with their CAC.', 'cac-auth'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    } else {
        ?>
        <h3><?php _e('CAC Authentication', 'cac-auth'); ?></h3>
        <table class="form-table">
            <tr>
                <th><?php _e('DoD ID Status', 'cac-auth'); ?></th>
                <td>
                    <p><strong><?php _e('DoD ID is set for this user.', 'cac-auth'); ?></strong></p>
                </td>
            </tr>
        </table>
        <?php
    }
}

function cac_auth_save_dod_id_field($user_id) {
    if (!current_user_can('edit_user', $user_id)) {
        return false;
    }

    if (isset($_POST['dod_id']) && !empty($_POST['dod_id'])) {
        $dod_id = sanitize_text_field($_POST['dod_id']);
        $hashed_dod_id = hash('sha256', $dod_id);
        update_user_meta($user_id, 'hashed_dod_id', $hashed_dod_id);
    }
}

add_action('show_user_profile', 'cac_auth_add_dod_id_field');
add_action('edit_user_profile', 'cac_auth_add_dod_id_field');
add_action('personal_options_update', 'cac_auth_save_dod_id_field');
add_action('edit_user_profile_update', 'cac_auth_save_dod_id_field');