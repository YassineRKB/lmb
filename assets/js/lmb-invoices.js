jQuery(document).ready(function($) {
    $('.lmb-invoices-widget').on('click', '.lmb-download-invoice', function() {
        const paymentId = $(this).data('payment-id');
        const button = $(this);
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        
        $.post(lmbAjax.ajaxurl, {
            action: 'lmb_generate_invoice_pdf',
            nonce: lmbAjax.nonce,
            payment_id: paymentId
        }, function(response) {
            if (response.success && response.data.pdf_url) {
                window.open(response.data.pdf_url, '_blank');
            } else {
                alert('Error generating invoice: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function() {
            alert('Failed to generate invoice. Please try again.');
        }).always(function() {
            button.prop('disabled', false).html(originalText);
        });
    });
});