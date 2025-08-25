jQuery(document).ready(function($) {
    const widget = $('.lmb-admin-actions-widget');
    if (!widget.length) {
        return;
    }

    let currentTab = 'feed';

    function loadTabContent(tab) {
        $('#lmb-tab-content-area').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_load_admin_tab',
            nonce: lmb_ajax_params.nonce,
            tab: tab
        }, function(response) {
            if (response.success) {
                $('#lmb-tab-content-area').html(response.data.content);
                $('#pending-ads-count').text(response.data.pending_ads_count);
                $('#pending-payments-count').text(response.data.pending_payments_count);
            }
        });
    }

    // Initial Load
    loadTabContent(currentTab);

    // Tab switching
    widget.on('click', '.lmb-tab-btn', function() {
        const tab = $(this).data('tab');
        currentTab = tab;
        $('.lmb-tab-btn').removeClass('active');
        $(this).addClass('active');
        loadTabContent(tab);
    });

    // Handle approve/deny actions
    widget.on('click', '.lmb-ad-action, .lmb-payment-action', function(e) {
        e.preventDefault();
        const button = $(this);
        const id = button.data('id');
        const isPayment = button.hasClass('lmb-payment-action');
        const actionType = isPayment ? 'lmb_payment_action' : 'lmb_ad_status_change';
        const actionKey = isPayment ? 'payment_action' : 'ad_action';
        const idKey = isPayment ? 'payment_id' : 'ad_id';
        const actionValue = button.data('action');

        let reason = '';
        if (actionValue === 'deny' || actionValue === 'reject') {
            reason = prompt('Please provide a reason:', '');
            if (reason === null) return;
        }

        button.closest('.lmb-feed-actions').html('Processing...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: actionType,
            nonce: lmb_ajax_params.nonce,
            [idKey]: id,
            [actionKey]: actionValue,
            reason: reason
        }).done(function() {
            loadTabContent(currentTab);
        });
    });
});