jQuery(document).ready(function($) {
    // CAC Registration Form Scripts
    $('.cac-registration-form').on('submit', function(e) {
        // Add any necessary form validation or submission handling here
    });

    // Admin Settings Page Scripts
    $('.cac-auth-custom-fields').on('click', '.cac-auth-remove-field', function() {
        $(this).closest('tr').remove();
    });

    $('.cac-auth-add-field').on('click', function() {
        var fieldId = Date.now();
        var fieldRow = '<tr>' +
            '<td><input type="text" name="cac_auth_registration_fields[' + fieldId + ']" value=""></td>' +
            '<td><button type="button" class="button button-secondary cac-auth-remove-field">Remove</button></td>' +
            '</tr>';
        $('.cac-auth-custom-fields tbody').append(fieldRow);
    });
});