jQuery(document).ready(function($) {
    $('.lmb-download-receipt').on('click', function() {
        const adId = $(this).data('ad-id');
        const adType = $(this).data('ad-type');
        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

        $.post(lmbAjax.ajaxurl, {
            action: 'lmb_generate_receipt_pdf',
            nonce: lmbAjax.nonce,
            ad_id: adId,
            ad_type: adType
        }, function(response) {
            if (response.success && response.data.pdf_url) {
                window.open(response.data.pdf_url, '_blank');
            } else {
                alert('Error generating receipt: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function() {
            alert('Failed to generate receipt. Please try again.');
        }).always(function() {
            button.prop('disabled', false).html(originalText);
        });
    });
});