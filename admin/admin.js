document.addEventListener('DOMContentLoaded', function() {
    var pageSelection = document.getElementById('page_selection');
    var selectedPages = document.getElementById('selected_pages');

    function toggleSelectedPagesField() {
        var selection = pageSelection.value;
        if (selection === 'specific' || selection === 'except') {
            selectedPages.closest('tr').style.display = '';
        } else {
            selectedPages.closest('tr').style.display = 'none';
        }
    }

    // Call on page load
    toggleSelectedPagesField();

    // Listen for changes
    pageSelection.addEventListener('change', toggleSelectedPagesField);
});
