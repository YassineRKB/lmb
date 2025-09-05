// FILE: assets/js/lmb-ads-directory-v2.js
jQuery(document).ready(function($) {
    $('.lmb-ads-directory-v2').each(function() {
        const widget = $(this);
        const form = widget.find('.lmb-filters-form');
        const tableBody = widget.find('.lmb-data-table tbody');
        const paginationContainer = widget.find('.lmb-pagination-container');
        let debounceTimer;

        function fetchDirectoryAds(page = 1) {
            tableBody.html('<tr><td colspan="5" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading ads...</td></tr>');

            const formData = form.serialize();
            const data = {
                action: 'lmb_fetch_public_ads',
                nonce: lmb_ajax_params.nonce,
                paged: page,
                filters: formData
            };

            $.post(lmb_ajax_params.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        tableBody.html(response.data.html);
                        paginationContainer.html(response.data.pagination);
                    } else {
                        tableBody.html('<tr><td colspan="5" style="text-align:center;">' + (response.data.message || 'Aucune annonce trouvée.') + '</td></tr>');
                        paginationContainer.html('');
                    }
                })
                .fail(function() {
                    tableBody.html('<tr><td colspan="5" style="text-align:center;">Une erreur s\'est produite lors de la récupération des données.</td></tr>');
                    paginationContainer.html('');
                });
        }

        form.on('submit', function(e) {
            e.preventDefault();
            fetchDirectoryAds(1);
        });
        
        form.on('input change', 'input, select', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetchDirectoryAds(1);
            }, 500);
        });

        form.on('reset', function() {
            setTimeout(() => fetchDirectoryAds(1), 1);
        });

        paginationContainer.on('click', 'a.page-numbers', function(e) {
            e.preventDefault();
            const href = $(this).attr('href');
            if (!href) return;
            let page = 1;
            try {
                const url = new URL(href, window.location.origin);
                page = url.searchParams.get('paged') || 1;
            } catch (error) {
                const pageNumMatch = href.match(/paged=(\d+)/);
                if (pageNumMatch && pageNumMatch[1]) {
                    page = pageNumMatch[1];
                }
            }
            fetchDirectoryAds(page);
        });

        // Initial Load
        fetchDirectoryAds(1);
    });
});