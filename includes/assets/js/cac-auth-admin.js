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
    // Select the cac_auth_enabled dropdown
    var cacAuthEnabledSelect = document.querySelector('select[name="cac_auth_enabled"]');

    // Add an event listener for when the value of the dropdown changes
    cacAuthEnabledSelect.addEventListener('change', function() {
        // Check if the selected value is 'no'
        var isDisabled = this.value === 'no';

        // Get all form elements
        var form = this.form;
        var formElements = form.elements;

        // Loop through each form element
        for (var i = 0; i < formElements.length; i++) {
            var element = formElements[i];

            // Skip disabling the cac_auth_enabled select itself and any submit buttons
            if (element !== cacAuthEnabledSelect && element.type !== 'submit') {
                element.disabled = isDisabled;
            }
        }
    });

    // Optionally, you may want to trigger the change event on page load
    // in case the form is loaded with 'no' already selected
    var event = new Event('change');
    cacAuthEnabledSelect.dispatchEvent(event);
});
