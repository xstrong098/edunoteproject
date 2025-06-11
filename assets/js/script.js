/**
 * EduNote - Main JavaScript File
 */

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    });
    
    if ($('#content').length) {
        $('#content').summernote({
            height: 300,
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "italic", "underline", "clear"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ]
        });
    }   
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
        $('.delete-confirm').on('click', function(e) {
        if (!confirm('Sei sicuro di voler eliminare questo elemento? Questa azione non può essere annullata.')) {
            e.preventDefault();
        }
    });
        $('.note-summary').each(function() {
        var summary = $(this).text();
        if (summary.length > 150 && !summary.endsWith('...')) {
            $(this).append('<span class="ai-summary-badge">AI</span>');
        }
    });
});