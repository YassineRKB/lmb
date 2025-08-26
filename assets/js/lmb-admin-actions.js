jQuery(document).ready(function($) {
    const widget = $('.lmb-admin-actions-widget');
    if (!widget.length) return;

    let currentTab = 'feed';
    const contentArea = $('#lmb-tab-content-area');
    const paginationArea = $('#lmb-tab-pagination-area');

    function loadTabContent(tab, page = 1) {
        currentTab = tab;
        contentArea.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        paginationArea.empty();
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_load_admin_tab',
            nonce: lmb_ajax_params.nonce,
            tab: tab,
            page: page
        }).done(function(response) {
            if (response.success) {
                contentArea.html(response.data.content);
                paginationArea.html(response.data.pagination);
                $('#pending-ads-count').text(response.data.pending_ads_count);
                $('#pending-payments-count').text(response.data.pending_payments_count);
            } else {
                contentArea.html('<div class="lmb-notice lmb-notice-error"><p>Could not load content.</p></div>');
            }
        }).fail(function() {
            contentArea.html('<div class="lmb-notice lmb-notice-error"><p>A server error occurred.</p></div>');
        });
    }

    // Initial Load
    loadTabContent(currentTab);

    // Tab switching
    widget.on('click', '.lmb-tab-btn', function() {
        const tab = $(this).data('tab');
        $('.lmb-tab-btn').removeClass('active');
        $(this).addClass('active');
        loadTabContent(tab, 1);
    });

    // Pagination
    paginationArea.on('click', '.page-numbers', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const page = href ? new URLSearchParams(href.split('?')[1]).get('paged') : 1;
        loadTabContent(currentTab, page);
    });

    // Handle actions (approve, deny, reject)
    contentArea.on('click', '.lmb-ad-action, .lmb-payment-action', function(e) {
        // ... (this entire event handler block remains unchanged from the previous version)
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
            loadTabContent(currentTab, 1);
        }).fail(function() {
            alert('An error occurred. Please try again.');
            loadTabContent(currentTab, 1);
        });
    });
});