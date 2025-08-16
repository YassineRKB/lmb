jQuery(document).ready(function($) {
    // Verify payment
    $('.lmb-verify-payment').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm(lmbPaymentVerifier.strings.confirm_verify)) {
            return;
        }
        
        const button = $(this);
        const paymentId = button.data('payment-id');
        const row = button.closest('tr');
        
        button.prop('disabled', true).text('Verifying...');
        
        $.ajax({
            url: lmbPaymentVerifier.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_verify_payment',
                payment_id: paymentId,
                nonce: lmbPaymentVerifier.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        row.remove();
                    });
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + 
                      lmbPaymentVerifier.strings.verified + 
                      ' Points added: ' + response.data.points_added + 
                      '</p></div>').insertAfter('.wrap h1');
                } else {
                    alert(response.data.message || lmbPaymentVerifier.strings.error);
                    button.prop('disabled', false).text('Verify');
                }
            },
            error: function() {
                alert(lmbPaymentVerifier.strings.error);
                button.prop('disabled', false).text('Verify');
            }
        });
    });
    
    // Reject payment
    $('.lmb-reject-payment').on('click', function(e) {
        e.preventDefault();
        
        const reason = prompt('Reason for rejection (optional):');
        if (reason === null) return; // User cancelled
        
        if (!confirm(lmbPaymentVerifier.strings.confirm_reject)) {
            return;
        }
        
        const button = $(this);
        const paymentId = button.data('payment-id');
        const row = button.closest('tr');
        
        button.prop('disabled', true).text('Rejecting...');
        
        $.ajax({
            url: lmbPaymentVerifier.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_reject_payment',
                payment_id: paymentId,
                reason: reason,
                nonce: lmbPaymentVerifier.nonce
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        row.remove();
                    });
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + 
                      lmbPaymentVerifier.strings.rejected + 
                      '</p></div>').insertAfter('.wrap h1');
                } else {
                    alert(response.data.message || lmbPaymentVerifier.strings.error);
                    button.prop('disabled', false).text('Reject');
                }
            },
            error: function() {
                alert(lmbPaymentVerifier.strings.error);
                button.prop('disabled', false).text('Reject');
            }
        });
    });
});