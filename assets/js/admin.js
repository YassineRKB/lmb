jQuery(document).ready(function($) {
    // Handle Ad Approve/Deny actions
    $('.lmb-ad-action').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var ad_id = button.data('id');
        var ad_action = button.data('action');
        var reason = '';

        if (ad_action === 'deny') {
            reason = prompt('Please provide a reason for denial (optional):', '');
            if (reason === null) return; // User cancelled
        }

        button.closest('.lmb_actions').html('Processing...');

        $.post(lmbAdmin.ajaxurl, {
            action: 'lmb_ad_status_change',
            nonce: lmbAdmin.nonce,
            ad_id: ad_id,
            ad_action: ad_action,
            reason: reason
        }).done(function() {
            location.reload();
        }).fail(function(response) {
            alert('Error: ' + response.responseJSON.data.message);
            location.reload();
        });
    });

    // Handle Payment Approve/Reject actions
    $('.lmb-payment-action').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var payment_id = button.data('id');
        var payment_action = button.data('action');
        var reason = '';

        if (payment_action === 'reject') {
            reason = prompt('Please provide a reason for rejection:', '');
            if (reason === null) return;
        }
        
        button.closest('td').html('Processing...');

        $.post(lmbAdmin.ajaxurl, {
            action: 'lmb_payment_action',
            nonce: lmbAdmin.nonce,
            payment_id: payment_id,
            payment_action: payment_action,
            reason: reason
        }).done(function() {
            location.reload();
        }).fail(function(response) {
            alert('Error: ' + response.responseJSON.data.message);
            location.reload();
        });
    });
});