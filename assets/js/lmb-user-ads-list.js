jQuery(document).ready(function($) {
    const container = $('#lmb-user-ads-list-container');
    if (!container.length) return;

    function fetchAds(page = 1) {
        container.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_user_get_ads',
            nonce: lmb_ajax_params.nonce,
            page: page
        }).done(function(response) {
            if (response.success && response.data.ads.length) {
                let html = '<div class="lmb-ads-list">';
                response.data.ads.forEach(ad => {
                    html += `
                        <div class="lmb-ad-item status-${ad.status}">
                            <h4>${ad.title}</h4>
                            <p>Status: ${ad.status.replace('_', ' ')} | Submitted: ${ad.date}</p>
                            ${ad.status === 'draft' ? `<button class="lmb-submit-review" data-id="${ad.ID}">Submit for Review</button>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                container.html(html);
                renderPagination(response.data.max_pages, page);
            } else {
                container.html('<p>No ads found.</p>');
            }
        });
    }

    function renderPagination(maxPages, currentPage) {
        const paginationContainer = $('#lmb-user-ads-pagination');
        paginationContainer.empty();
        if (maxPages > 1) {
            for (let i = 1; i <= maxPages; i++) {
                paginationContainer.append(`<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`);
            }
        }
    }

    container.on('click', '.lmb-submit-review', function() {
        const adId = $(this).data('id');
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_user_submit_for_review',
            nonce: lmb_ajax_params.nonce,
            ad_id: adId
        }).done(() => fetchAds(1));
    });

    $('#lmb-user-ads-pagination').on('click', '.page-btn', function() {
        fetchAds($(this).data('page'));
    });

    fetchAds();
});