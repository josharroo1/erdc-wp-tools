jQuery(document).ready(function($) {
    // Add custom field
    $('.cac-auth-add-field').on('click', function() {
        var fieldId = Date.now();
        var fieldRow = '<tr>' +
            '<td><input type="text" name="cac_auth_registration_fields[' + fieldId + ']" value=""></td>' +
            '<td><button type="button" class="button button-secondary cac-auth-remove-field">Remove</button></td>' +
            '</tr>';
        $('.cac-auth-custom-fields tbody').append(fieldRow);
    });

    // Remove custom field
    $('.cac-auth-custom-fields').on('click', '.cac-auth-remove-field', function() {
        $(this).closest('tr').remove();
    });
});

//disable the form if CAC auth turned off
document.addEventListener('DOMContentLoaded', function() {
    // Select the CAC authentication enabled dropdown
    var cacAuthEnabledSelect = document.querySelector('select[name="cac_auth_enabled"]');

    // Function to toggle other form fields
    function toggleFormFields(enable) {
        // Find the parent form of the select dropdown
        var form = cacAuthEnabledSelect.closest('form');
        if (!form) return; // If the select is not inside a form, do nothing

        // Get all form inputs, selects, and textareas except the CAC auth enabled select
        var formFields = form.querySelectorAll('input, select, textarea');
        formFields.forEach(function(field) {
            if (field.name !== 'cac_auth_enabled') { // Skip the CAC auth enabled select
                field.disabled = !enable; // Enable or disable based on the parameter
            }
        });
    }

    // Initial check to set the correct state when the page loads
    toggleFormFields(cacAuthEnabledSelect.value === 'yes');

    // Add an event listener to change the state when the select value changes
    cacAuthEnabledSelect.addEventListener('change', function() {
        toggleFormFields(this.value === 'yes');
    });
});
