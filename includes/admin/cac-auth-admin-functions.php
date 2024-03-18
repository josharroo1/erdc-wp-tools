<?php
/**
 * CAC Authentication Admin Functions
 */

// Add custom registration fields section
function cac_auth_custom_fields_section_callback() {
    echo '<p>Add custom fields to the CAC registration form.</p>';
}

// Render custom registration fields
function cac_auth_render_custom_fields() {
    $custom_fields = get_option('cac_auth_registration_fields', array());

    // Check if $custom_fields is an array, if not, convert it to an empty array
    if (!is_array($custom_fields)) {
        $custom_fields = array();
    }

    ?>
    <table class="cac-auth-custom-fields">
        <thead>
            <tr>
                <th>Field Label</th>
                <th>Field Type</th>
                <th>Options (for Select fields)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($custom_fields as $field_id => $field_data) : ?>
                <tr>
                    <td><input type="text" name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][label]" value="<?php echo esc_attr($field_data['label']); ?>"></td>
                    <td>
                        <select name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][type]">
                            <option value="text" <?php selected($field_data['type'], 'text'); ?>>Text</option>
                            <option value="number" <?php selected($field_data['type'], 'number'); ?>>Number</option>
                            <option value="select" <?php selected($field_data['type'], 'select'); ?>>Select</option>
                        </select>
                    </td>
                    <td><input type="text" name="cac_auth_registration_fields[<?php echo esc_attr($field_id); ?>][options]" value="<?php echo esc_attr($field_data['options']); ?>" placeholder="key1:value1,key2:value2"></td>
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
    if (isset($_POST['cac_auth_registration_fields'])) {
        $custom_fields = array();
        foreach ($_POST['cac_auth_registration_fields'] as $field_id => $field_data) {
            $field_label = sanitize_text_field($field_data['label']);
            $field_type = sanitize_text_field($field_data['type']);
            $field_options = sanitize_text_field($field_data['options']);

            if (!empty($field_label)) {
                $custom_fields[$field_id] = array(
                    'label' => $field_label,
                    'type' => $field_type,
                    'options' => $field_options,
                );
            }
        }
        $options = $custom_fields;
    }
    return $options;
}
add_filter('cac_auth_settings_sanitize', 'cac_auth_save_custom_fields');

// Enqueue admin scripts
function cac_auth_admin_enqueue_scripts($hook) {
    if ('settings_page_cac-auth-settings' !== $hook) {
        return;
    }

    wp_enqueue_script('cac-auth-admin', CAC_AUTH_PLUGIN_URL . 'includes/assets/js/cac-auth-admin.js', array('jquery'), CAC_AUTH_PLUGIN_VERSION, true);
}
add_action('admin_enqueue_scripts', 'cac_auth_admin_enqueue_scripts');