jQuery(document).ready(function($) {
    const widget = $('.lmb-legal-ads-management-v2');
    if (!widget.length) return;

    const form = widget.find('#lmb-ads-filters-form-v2');
    const tableBody = widget.find('.lmb-ads-table-v2 tbody');
    // We will add a dedicated pagination container in the widget's PHP later.
    const paginationContainer = widget.find('.lmb-pagination-container'); 
    let debounceTimer;

    // --- Function to fetch and render ads ---
    function fetchAds(page = 1) {
        // Show loading state
        tableBody.html('<tr><td colspan="10" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading ads...</td></tr>');
        
        const formData = form.serialize();
        const data = {
            action: 'lmb_fetch_ads_v2', // We will create this backend action next
            nonce: lmb_ajax_params.nonce,
            paged: page,
            filters: formData
        };

        $.post(lmb_ajax_params.ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    tableBody.html(response.data.html);
                    // paginationContainer.html(response.data.pagination); // Will be handled later
                } else {
                    tableBody.html('<tr><td colspan="10" style="text-align:center;">' + (response.data.message || 'No ads found.') + '</td></tr>');
                }
            })
            .fail(function() {
                tableBody.html('<tr><td colspan="10" style="text-align:center;">An error occurred while fetching data.</td></tr>');
            });
    }

    // --- Event Handlers ---

    // Handle real-time filtering with debounce
    form.on('input change', 'input, select', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            fetchAds(1); // Reset to page 1 on new filter
        }, 500); // 500ms delay after user stops typing
    });

    // Handle Reset Button
    form.on('reset', function(e) {
        e.preventDefault();
        form.find('input[type="text"], input[type="date"], select').val('');
        fetchAds(1);
    });

    // Handle Row Click (delegated)
    tableBody.on('click', 'tr.clickable-row', function(e) {
        if ($(e.target).closest('button, a, .lmb-actions-cell').length > 0) {
            return;
        }
        const href = $(this).data('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    });

    // Handle Action Buttons (delegated)
    tableBody.on('click', '.lmb-ad-action', function() {
        const button = $(this);
        const adId = button.data('id');
        const adAction = button.data('action');
        // Add AJAX logic for approve/deny/etc. here
        alert(`Action: ${adAction} on Ad ID: ${adId}`);
    });

    // --- Initial Load ---
    fetchAds(1);
});

