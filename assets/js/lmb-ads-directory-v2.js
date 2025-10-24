// FILE: assets/js/lmb-ads-directory-v2.js
jQuery(document).ready(function($) {
    $('.lmb-ads-directory-v2').each(function() {
        const widget = $(this);
        const form = widget.find('.lmb-filters-form');
        const tableBody = widget.find('.lmb-data-table tbody');
        const paginationContainer = widget.find('.lmb-pagination-container');
        let debounceTimer;

        function fetchDirectoryAds(page = 1) {
            tableBody.html('<tr><td colspan="5" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Chargement des annonces...</td></tr>');
            paginationContainer.html(''); // Clear pagination during load

            // --- START TASK 2: NEW FILTER COLLECTION ---
            const filters = {
                smart_search: form.find('[name="smart_search"]').val(),
                filter_date: form.find('[name="filter_date"]').val(),
            };
            // --- END TASK 2: NEW FILTER COLLECTION ---
            
            const data = {
                action: 'lmb_fetch_public_ads',
                nonce: lmb_ajax_params.nonce,
                paged: page,
                filters: $.param(filters) // Send all filters
            };

            $.post(lmb_ajax_params.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        tableBody.html(response.data.html);
                        paginationContainer.html(response.data.pagination);
                        
                        // --- START TASK 3: FIX PAGINATION CLICK HANDLER ---
                        // Rebind the click event after new HTML is loaded
                        paginationContainer.off('click', 'a.page-numbers').on('click', 'a.page-numbers', function(e) {
                            e.preventDefault();
                            
                            const href = $(this).attr('href');
                            if (!href) return;
                            
                            // Extract page number from the clean base: '?paged=X'
                            const pageNumMatch = href.match(/paged=(\d+)/);
                            if (pageNumMatch && pageNumMatch[1]) {
                                fetchDirectoryAds(parseInt(pageNumMatch[1]));
                            }
                        });
                        // --- END TASK 3: FIX PAGINATION CLICK HANDLER ---
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

        form.off('submit').on('submit', function(e) {
            e.preventDefault();
            fetchDirectoryAds(1); // On submit, always go to page 1
        });
        
        // --- MODIFIED INPUT CHANGE HANDLER ---
        // 1. Debounce for Smart Search
        form.off('input change', 'input, select').on('input', 'input[name="smart_search"]', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetchDirectoryAds(1);
            }, 500);
        });
        
        // 2. Immediate refresh for Date Change
        form.on('change', 'input[name="filter_date"]', function() {
             fetchDirectoryAds(1);
        });
        
        // 3. Reset Handler (re-bind to the form)
        form.on('reset', function() {
            setTimeout(() => fetchDirectoryAds(1), 50);
        });
        
        // Initial Load
        fetchDirectoryAds(1);
    });
});