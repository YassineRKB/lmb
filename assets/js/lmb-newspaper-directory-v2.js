// FILE: assets/js/lmb-newspaper-directory-v2.js
jQuery(document).ready(function($) {
    $('.lmb-newspaper-directory-v2').each(function() {
        const widget = $(this);
        const form = widget.find('.lmb-filters-form');
        const tableBody = widget.find('.lmb-data-table tbody'); // CORRECTED SELECTOR
        const paginationContainer = widget.find('.lmb-pagination-container');
        let debounceTimer;

        function fetchNewspapers(page = 1) {
            // Updated loading message for a table
            tableBody.html('<tr><td colspan="4" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading newspapers...</td></tr>');

            const formData = form.serialize();
            const data = {
                action: 'lmb_fetch_newspapers_v2',
                nonce: lmb_ajax_params.nonce,
                paged: page,
                filters: formData
            };

            $.post(lmb_ajax_params.ajaxurl, data)
                .done(function(response) {
                    if (response.success) {
                        tableBody.html(response.data.html); // This now inserts <tr> elements
                        paginationContainer.html(response.data.pagination);
                    } else {
                        tableBody.html('<tr><td colspan="4" style="text-align:center;">' + (response.data.message || 'No newspapers found.') + '</td></tr>');
                        paginationContainer.html('');
                    }
                })
                .fail(function() {
                    tableBody.html('<tr><td colspan="4" style="text-align:center;">An error occurred while fetching data.</td></tr>');
                    paginationContainer.html('');
                });
        }

        form.on('submit', function(e) {
            e.preventDefault();
            fetchNewspapers(1);
        });

        form.on('keyup', 'input[name="s"]', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetchNewspapers(1);
            }, 500);
        });
        
        form.on('reset', function() {
            setTimeout(() => {
                form.find('input[name="s"]').val('');
                fetchNewspapers(1);
            }, 1);
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
            fetchNewspapers(page);
        });

        // Initial Load
        fetchNewspapers(1);
    });
});