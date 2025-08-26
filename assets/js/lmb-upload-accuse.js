jQuery(document).ready(function($) {
    const widget = $('.lmb-upload-accuse-widget');
    if (!widget.length) return;

    const container = $('#lmb-pending-accuse-list-container');

    function fetchPendingAds(page = 1) {
        container.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading ads...</div>');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_pending_accuse_ads',
            nonce: lmb_ajax_params.nonce,
            page: page
        }).done(function(response) {
            if (response.success) {
                container.html(response.data.html);
            } else {
                container.html('<div class="lmb-notice lmb-notice-error"><p>Could not load ads.</p></div>');
            }
        });
    }

    // Handle pagination clicks
    container.on('click', '.page-numbers', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const page = href ? new URLSearchParams(href.split('?')[1]).get('paged') : 1;
        fetchPendingAds(page);
    });

    // --- REWRITTEN EVENT HANDLER ---
    // Handle the submission of the individual upload forms
    container.on('submit', '.lmb-accuse-upload-form', function(e) {
        e.preventDefault(); // This is crucial to prevent page reload

        const $form = $(this);
        const $button = $form.find('button[type="submit"]');
        const formData = new FormData(this); // 'this' is the form element
        
        // Add the action and nonce for the AJAX request
        formData.append('action', 'lmb_upload_accuse');
        formData.append('nonce', lmb_ajax_params.nonce);

        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            success: function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    fetchPendingAds(1); // Refresh the list from page 1
                } else {
                    showLMBModal('error', response.data.message);
                    $button.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
                }
            },
            error: function() {
                showLMBModal('error', 'An unexpected server error occurred.');
                $button.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
            }
        });
    });

    // Initial load
    fetchPendingAds(1);
});