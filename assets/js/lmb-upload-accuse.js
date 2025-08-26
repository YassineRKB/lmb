jQuery(document).ready(function($) {
    const widget = $('.lmb-upload-accuse-widget');
    if (!widget.length) return;

    const container = $('#lmb-pending-accuse-list-container');
    let mediaFrame;

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

    // Handle the "Upload" button click
    container.on('click', '.lmb-upload-accuse-btn', function() {
        const adId = $(this).data('ad-id');
        const $button = $(this);

        // If the media frame already exists, reopen it.
        if (mediaFrame) {
            mediaFrame.off('select'); // Clear previous event handlers
        } else {
            // Create the media frame.
            mediaFrame = wp.media({
                title: 'Select or Upload Accuse Document',
                button: { text: 'Use this Document' },
                multiple: false,
                library: { type: ['application/pdf', 'image/jpeg', 'image/png'] }
            });
        }
        
        // When a file is selected, run a callback.
        mediaFrame.on('select', function() {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            // Fire an AJAX request to attach the file to the ad
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_attach_accuse_to_ad',
                nonce: lmb_ajax_params.nonce,
                ad_id: adId,
                attachment_id: attachment.id
            }).done(function(response) {
                if (response.success) {
                    if (typeof showLMBModal === 'function') {
                        showLMBModal('success', response.data.message);
                    } else {
                        alert(response.data.message);
                    }
                    fetchPendingAds(1); // Refresh the list
                } else {
                    if (typeof showLMBModal === 'function') {
                        showLMBModal('error', response.data.message);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                    $button.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload');
                }
            });
        });

        // Finally, open the modal.
        mediaFrame.open();
    });

    // Initial load
    fetchPendingAds(1);
});