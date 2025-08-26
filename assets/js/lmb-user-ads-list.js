jQuery(document).ready(function($) {
    const widget = $('.lmb-user-ads-list-widget');
    if (!widget.length) return;

    const container = widget.find('#lmb-user-ads-list-container');
    const paginationContainer = widget.find('#lmb-user-ads-pagination');
    let currentStatus = 'draft'; // Default tab

    function fetchAds(page = 1) {
        container.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_user_get_ads',
            nonce: lmb_ajax_params.nonce,
            page: page,
            status: currentStatus
        }).done(function(response) {
            if (response.success && response.data.html) {
                container.html(response.data.html);
                renderPagination(response.data.max_pages, page);
            } else {
                container.html('<div class="lmb-empty-state"><i class="fas fa-file-alt fa-3x"></i><h4>No Ads Found</h4><p>There are no ads with this status.</p></div>');
                paginationContainer.empty();
            }
        });
    }

    function renderPagination(maxPages, currentPage) {
        paginationContainer.empty();
        if (maxPages > 1) {
            for (let i = 1; i <= maxPages; i++) {
                paginationContainer.append(`<button class="page-btn ${i === parseInt(currentPage) ? 'active' : ''}" data-page="${i}">${i}</button>`);
            }
        }
    }

    // Tab switching
    widget.on('click', '.lmb-tab-btn', function() {
        const button = $(this);
        currentStatus = button.data('status');
        widget.find('.lmb-tab-btn').removeClass('active');
        button.addClass('active');
        fetchAds(1);
    });

    // Handle pagination clicks
    paginationContainer.on('click', '.page-btn', function() {
        fetchAds($(this).data('page'));
    });

    // Handle "Submit for Review" button click
    container.on('click', '.lmb-submit-review', function() {
        const adId = $(this).data('id');
        const button = $(this);
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_user_submit_for_review',
            nonce: lmb_ajax_params.nonce,
            ad_id: adId
        }).done(function(response) {
            if (response.success) {
                fetchAds(1); // Refresh the current tab
            } else {
                alert(response.data.message);
                button.prop('disabled', false).html('Submit for Review');
            }
        });
    });

    // Initial load
    fetchAds();
});