jQuery(document).ready(function($) {
    const widget = $('.lmb-admin-actions-widget');
    if (!widget.length) {
        return;
    }

    let currentTab = 'feed';
    let refreshInterval = null;

    function loadTabContent(tab, isInitialLoad = false) {
        if (!isInitialLoad) {
            $('#lmb-tab-content-area').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        }
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_load_admin_tab',
            nonce: lmb_ajax_params.nonce,
            tab: tab
        }, function(response) {
            if (response.success) {
                $('#lmb-tab-content-area').html(response.data.content);
                $('#pending-ads-count').text(response.data.pending_ads_count);
                $('#pending-payments-count').text(response.data.pending_payments_count);
            } else {
                $('#lmb-tab-content-area').html('<div class="lmb-notice lmb-notice-error"><p>Error loading content</p></div>');
            }
        });
    }

    function startAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(() => {
            loadTabContent(currentTab, true); // Silent refresh
        }, lmb_admin_settings.refresh_interval || 30000);
    }

    // Initial Load
    loadTabContent(currentTab, true);
    startAutoRefresh();

    // Tab switching
    widget.on('click', '.lmb-tab-btn', function() {
        const tab = $(this).data('tab');
        currentTab = tab;
        $('.lmb-tab-btn').removeClass('active');
        $(this).addClass('active');
        loadTabContent(tab);
        startAutoRefresh(); // Restart timer on tab switch
    });

    // Handle approve/deny actions for Ads
    widget.on('click', '.lmb-ad-action', function(e) {
        e.preventDefault();
        const button = $(this);
        const adId = button.data('id');
        const adAction = button.data('action');
        const item = button.closest('.lmb-feed-item');
        let reason = '';

        if (adAction === 'deny') {
            reason = prompt('Please provide a reason for denial:', '');
            if (reason === null) return;
        }

        button.closest('.lmb-feed-actions').html('Processing...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_ad_status_change',
            nonce: lmb_ajax_params.nonce,
            ad_id: adId,
            ad_action: adAction,
            reason: reason
        }).done(function(response) {
            if (response.success) {
                item.fadeOut(300, function() {
                    $(this).remove();
                    loadTabContent(currentTab, true); // Refresh tab content silently
                });
            } else {
                alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                loadTabContent(currentTab); // Full refresh to restore button
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            loadTabContent(currentTab);
        });
    });

    // Handle approve/reject actions for Payments
    widget.on('click', '.lmb-payment-action', function(e) {
        e.preventDefault();
        const button = $(this);
        const paymentId = button.data('id');
        const paymentAction = button.data('action');
        const item = button.closest('.lmb-feed-item');
        let reason = '';

        if (paymentAction === 'reject') {
            reason = prompt('Please provide a reason for rejection:', '');
            if (reason === null) return;
        }
        
        button.closest('.lmb-feed-actions').html('Processing...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_payment_action',
            nonce: lmb_ajax_params.nonce,
            payment_id: paymentId,
            payment_action: paymentAction,
            reason: reason
        }).done(function(response) {
             if (response.success) {
                item.fadeOut(300, function() {
                    $(this).remove();
                    loadTabContent(currentTab, true); // Refresh tab content silently
                });
            } else {
                alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                loadTabContent(currentTab);
            }
        }).fail(function() {
            alert('An unknown error occurred.');
            loadTabContent(currentTab);
        });
    });
});