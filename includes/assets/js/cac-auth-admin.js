jQuery(document).ready(function($) {
    // Add custom field
    $('.cac-auth-add-field').on('click', function() {
        var fieldId = Date.now();
        var fieldRow = '<tr>' +
            '<td><input type="text" name="cac_auth_registration_fields[' + fieldId + '][label]" value=""></td>' +
            '<td>' +
            '<select name="cac_auth_registration_fields[' + fieldId + '][type]">' +
            '<option value="text">Text</option>' +
            '<option value="number">Number</option>' +
            '<option value="select">Select</option>' +
            '</select>' +
            '</td>' +
            '<td><input type="text" name="cac_auth_registration_fields[' + fieldId + '][options]" value="" placeholder="Enter options (comma-separated)" class="cac-auth-options-input"></td>' +
            '<td>' +
            '<input type="file" name="cac_auth_registration_fields[' + fieldId + '][csv_file]" accept=".csv" class="cac-auth-options-input">' +
            '</td>' +
            '<td><button type="button" class="button button-secondary cac-auth-remove-field">Remove</button></td>' +
            '</tr>';
        $('.cac-auth-custom-fields tbody').append(fieldRow);
        $('.cac-auth-custom-fields tbody tr:last-child select[name$="[type]"]').trigger('change');
    });

    // Remove custom field
    $('.cac-auth-custom-fields').on('click', '.cac-auth-remove-field', function() {
        $(this).closest('tr').remove();
    });

    // Toggle options input field based on field type
    $('.cac-auth-custom-fields').on('change', 'select[name$="[type]"]', function() {
        var fieldType = $(this).val();
        var optionsInput = $(this).closest('tr').find('.cac-auth-options-input');
        if (fieldType === 'select') {
            optionsInput.removeClass('disabled');
        } else {
            optionsInput.addClass('disabled');
        }
    });

    // Ensure file input fields are always included in form submission
    $('.cac-auth-custom-fields form').on('submit', function() {
        $(this).find('input[type="file"]').each(function() {
            if ($(this).val() === '') {
                // Create a hidden input field with the existing file name
                var existingFileName = $(this).closest('tr').find('span.small-desc').text().replace('Current file: ', '');
                $('<input type="hidden" name="' + $(this).attr('name') + '" value="' + existingFileName + '">').appendTo($(this).closest('tr'));
            }
        });
    });
    // Remove the current csv
    $('.cac-auth-custom-fields').on('click', '.cac-auth-remove-csv', function() {
        var fieldId = $(this).data('field-id');
        var confirmRemove = confirm('Are you sure you want to remove the CSV file for this field?');
        if (confirmRemove) {
            $.post(ajaxurl, {
                action: 'cac_auth_remove_csv',
                field_id: fieldId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to remove the CSV file. Please try again.');
                }
            });
        }
    });
});

// //Superficially disable the form if CAC auth turned off
// document.addEventListener('DOMContentLoaded', function() {
//     var cacAuthEnabledSelect = document.querySelector('select[name="cac_auth_enabled"]');

//     cacAuthEnabledSelect.addEventListener('change', function() {
//         var isDisabled = this.value === 'no';
//         var form = this.form;
//         var formElements = form.elements;

//         for (var i = 0; i < formElements.length; i++) {
//             var element = formElements[i];
//             if (element !== cacAuthEnabledSelect && element.type !== 'submit') {
//                 // Add or remove the 'disabled-style' class
//                 if (isDisabled) {
//                     element.classList.add('disabled-style');
//                 } else {
//                     element.classList.remove('disabled-style');
//                 }
//             }
//         }
//     });

//     // Trigger the change event on page load
//     var event = new Event('change');
//     cacAuthEnabledSelect.dispatchEvent(event);
// });

//Remove CAC related settings from the DOM
document.addEventListener('DOMContentLoaded', function() {
    var cacAuthEnabledSelect = document.querySelector('select[name="cac_auth_enabled"]');
    var sectionsToToggle = [
        { title: 'Account Approval', fields: ['cac_auth_user_approval'] },
        { title: 'CAC Registration Settings', fields: ['cac_auth_registration_page', 'cac_auth_redirect_page', 'cac_auth_default_role'] },
        { title: 'CAC Registration Form', fields: [] },
        { title: 'Color Settings', fields: ['cac_auth_svg_fill_color', 'cac_auth_link_color'] }
    ];

    function toggleSections() {
        var isEnabled = cacAuthEnabledSelect.value === 'yes';
        sectionsToToggle.forEach(function(section) {
            var sectionElement = findSectionByTitle(section.title);
            if (sectionElement) {
                sectionElement.style.display = isEnabled ? 'block' : 'none';
                // Toggle fields within the section
                section.fields.forEach(function(fieldName) {
                    var field = document.querySelector('[name="' + fieldName + '"]');
                    if (field) {
                        field.closest('tr').style.display = isEnabled ? 'table-row' : 'none';
                    }
                });
            }
        });
    }

    function findSectionByTitle(title) {
        var headers = document.querySelectorAll('h2');
        for (var i = 0; i < headers.length; i++) {
            if (headers[i].textContent.trim() === title) {
                return headers[i];
            }
        }
        return null;
    }

    cacAuthEnabledSelect.addEventListener('change', toggleSections);

    // Initial toggle on page load
    toggleSections();
});