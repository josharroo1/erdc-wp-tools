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
    <table class="cac-auth-custom-fields">
        <thead>
            <tr>
                <th>Field Label</th>
                <th>Field Type</th>
                <th>Select Options (Comma Separated)</th>
                <th>CSV File (for Select options)</th>
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
                    <td><input type="text" name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][options]" value="<?php echo esc_attr($field_data['options']); ?>" placeholder="Enter options (comma-separated)" class="cac-auth-options-input <?php echo $field_data['type'] !== 'select' ? 'disabled' : ''; ?>"></td>
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
    wp_enqueue_style('cac-auth-styles', CAC_AUTH_PLUGIN_URL . 'includes/assets/css/cac-admin-style.css', array(), CAC_AUTH_PLUGIN_VERSION);
    wp_enqueue_script('cac-auth-admin', CAC_AUTH_PLUGIN_URL . 'includes/assets/js/cac-auth-admin.js', array('jquery'), CAC_AUTH_PLUGIN_VERSION, true);
}
add_action('admin_enqueue_scripts', 'cac_auth_admin_enqueue_scripts');