jQuery(document).ready(function($) {
    const widget = $('.lmb-upload-bank-proof-widget');
    if (!widget.length) return;

    const container = $('#lmb-upload-proof-container');

    // Function to fetch and render the form/content
    function fetchFormContent() {
        container.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading pending invoices...</div>');
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_pending_invoices_form',
            nonce: lmb_ajax_params.nonce,
        }).done(function(response) {
            if (response.success) {
                container.html(response.data.html);
            } else {
                container.html('<div class="lmb-notice lmb-notice-error"><p>Could not load content.</p></div>');
            }
        });
    }

    // Handle form submission using event delegation
    container.on('submit', '#lmb-upload-proof-form', function(e) {
        e.preventDefault(); // Prevent page reload
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'lmb_upload_bank_proof');
        formData.append('nonce', lmb_ajax_params.nonce);

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                // --- FIX: Instead of reloading the page, we just refresh the widget's content ---
                fetchFormContent();
            } else {
                showLMBModal('error', response.data.message);
                submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit for Verification');
            }
        }).fail(function() {
            showLMBModal('error', 'An unexpected server error occurred.');
            submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit for Verification');
        });
    });

    // Initial load of the form
    fetchFormContent();
});