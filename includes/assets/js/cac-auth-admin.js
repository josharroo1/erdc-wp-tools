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

//Superficially disable the form if CAC auth turned off
document.addEventListener('DOMContentLoaded', function() {
    var cacAuthEnabledSelect = document.querySelector('select[name="cac_auth_enabled"]');

    cacAuthEnabledSelect.addEventListener('change', function() {
        var isDisabled = this.value === 'no';
        var form = this.form;
        var formElements = form.elements;

        for (var i = 0; i < formElements.length; i++) {
            var element = formElements[i];
            if (element !== cacAuthEnabledSelect && element.type !== 'submit') {
                // Add or remove the 'disabled-style' class
                if (isDisabled) {
                    element.classList.add('disabled-style');
                } else {
                    element.classList.remove('disabled-style');
                }
            }
        }
    });

    // Trigger the change event on page load
    var event = new Event('change');
    cacAuthEnabledSelect.dispatchEvent(event);
});
