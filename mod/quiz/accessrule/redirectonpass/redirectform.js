// This script handles the conditional visibility of the target dropdown.
// It is loaded directly via $PAGE->requires->js() in lib.php.

/**
 * Initialize the form behaviour for the redirect on pass access rule.
 * Toggles the visibility of the redirect activity dropdown based on the enable checkbox.
 */
(function($) { // Encapsulate with jQuery wrapper to avoid conflicts.
    $(document).ready(function() {
        console.log('redirectonpass: redirectform.js script started (non-AMD).'); // Debug log 1

        var enableCheckbox = $('#id_quizaccess_redirectonpass_enable');
        // Find the parent form item of the checkbox, then the next form item for the dropdown.
        // This assumes the checkbox and select are direct siblings within the form structure.
        var activitySelectRow = enableCheckbox.closest('.fitem').next('.fitem');

        console.log('redirectonpass: Checkbox element found:', enableCheckbox.length > 0); // Debug log 2
        console.log('redirectonpass: Dropdown container found:', activitySelectRow.length > 0); // Debug log 3

        // Function to toggle visibility.
        function toggleVisibility() {
            console.log('redirectonpass: toggleVisibility called. Checkbox checked:', enableCheckbox.is(':checked')); // Debug log 4
            if (enableCheckbox.is(':checked')) {
                activitySelectRow.show();
                // We don't need to explicitly enable/disable the select element itself
                // as Moodle's form submission handles disabled elements.
                console.log('redirectonpass: Dropdown shown.'); // Debug log 5
            } else {
                activitySelectRow.hide();
                console.log('redirectonpass: Dropdown hidden.'); // Debug log 6
            }
        }

        // Initial state on page load.
        toggleVisibility();

        // Attach event listener for checkbox change.
        enableCheckbox.on('change', toggleVisibility);
        console.log('redirectonpass: Change event listener attached to checkbox.'); // Debug log 7
    });
})(jQuery); // Pass jQuery to the function.
