jQuery(document).ready(function($) {
    // --- User List Widget ---
    const userListWidget = $('#lmb-user-list-widget');
    if (userListWidget.length) {
        const userFiltersForm = $('#lmb-user-filters-form');
        const userListContainer = $('#lmb-user-list-container');

        const fetchUsers = (page = 1) => {
            const formData = userFiltersForm.serialize() + '&action=lmb_fetch_users&nonce=' + lmb_ajax_params.nonce + '&page=' + page;
            userListContainer.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading users...</div>');
            $.post(lmb_ajax_params.ajaxurl, formData, function(response) {
                if (response.success) {
                    userListContainer.html(response.data.html);
                } else {
                    userListContainer.html('<div class="lmb-notice lmb-notice-error"><p>Could not load users.</p></div>');
                }
            });
        };

        userFiltersForm.on('submit', function(e) {
            e.preventDefault();
            fetchUsers();
        });

        userFiltersForm.on('reset', function() {
            setTimeout(fetchUsers, 100);
        });

        userListContainer.on('click', '.lmb-pagination a', function(e) {
            e.preventDefault();
            const page = new URL($(this).attr('href')).searchParams.get('paged');
            fetchUsers(page);
        });

        fetchUsers(); // Initial load
    }

    // --- Legal Ads List Widget ---
    const adsListWidget = $('#lmb-legal-ads-list-widget');
    if (adsListWidget.length) {
        const adsFiltersForm = $('#lmb-ads-filters-form');
        const adsListContainer = $('#lmb-ads-list-container');

        const fetchAds = (page = 1) => {
            const formData = adsFiltersForm.serialize() + '&action=lmb_fetch_ads&nonce=' + lmb_ajax_params.nonce + '&page=' + page;
            adsListContainer.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading ads...</div>');
            $.post(lmb_ajax_params.ajaxurl, formData, function(response) {
                if (response.success) {
                    adsListContainer.html(response.data.html);
                } else {
                    adsListContainer.html('<div class="lmb-notice lmb-notice-error"><p>Could not load ads.</p></div>');
                }
            });
        };

        adsFiltersForm.on('submit', function(e) {
            e.preventDefault();
            fetchAds();
        });
        
        adsFiltersForm.on('reset', function() {
            setTimeout(fetchAds, 100);
        });

        adsListContainer.on('click', '.lmb-pagination a', function(e) {
            e.preventDefault();
            const page = new URL($(this).attr('href')).searchParams.get('paged');
            fetchAds(page);
        });

        // Handle direct approve/deny from the list
        adsListContainer.on('click', '.lmb-ad-action', function(e) {
            e.preventDefault();
            const button = $(this);
            const adId = button.data('id');
            const adAction = button.data('action');
            let reason = '';

            if (adAction === 'deny') {
                reason = prompt('Reason for denial:');
                if (reason === null) return;
            }

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_ad_status_change',
                nonce: lmb_ajax_params.nonce,
                ad_id: adId,
                ad_action: adAction,
                reason: reason
            }).done(function() {
                fetchAds($('.lmb-pagination .current').text() || 1); // Refresh current page
            });
        });

        fetchAds(); // Initial load
    }
});