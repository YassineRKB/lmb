// FILE: assets/js/lmb-legal-ads-management-v2.js

jQuery(document).ready(function($) {
    const widget = $('.lmb-legal-ads-management-v2');
    if (!widget.length) return;

    const form = widget.find('#lmb-ads-filters-form-v2');
    const tableBody = widget.find('.lmb-ads-table-v2 tbody');
    const paginationContainer = widget.find('.lmb-pagination-container'); 
    let debounceTimer;

    // --- Function to fetch and render ads ---
    function fetchAds(page = 1) {
        // Show loading state
        tableBody.html('<tr><td colspan="10" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading ads...</td></tr>');
        
        const formData = form.serialize();
        const data = {
            action: 'lmb_fetch_ads_v2',
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

    // Handle Approve/Deny Action Buttons
    tableBody.on('click', '.lmb-ad-action', function(e) {
        // --- FIX: Stop the click from bubbling up to the table row ---
        e.stopPropagation(); 
        const button = $(this);
        // ... rest of the function is the same
    });
    
    // Handle Generate Accuse
    tableBody.on('click', '.lmb-generate-accuse-btn', function(e) {
        // --- FIX: Stop the click from bubbling up to the table row ---
        e.stopPropagation();
        const button = $(this);
        const adId = button.data('id');
        
        button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_admin_generate_accuse',
            nonce: lmb_ajax_params.nonce,
            ad_id: adId
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                fetchAds($('.lmb-pagination .current').text() || 1); // Refresh table
            } else {
                showLMBModal('error', response.data.message);
                button.html('<i class="fas fa-receipt"></i> Generate Accuse').prop('disabled', false);
            }
        });
    });

    // Handle Upload Temporary Journal
    tableBody.on('click', '.lmb-upload-journal-btn', function(e) {
        // --- FIX: Stop the click from bubbling up to the table row ---
        e.stopPropagation();
        const adId = $(this).data('id');
        const fileInput = $('<input type="file" accept="application/pdf" style="display: none;">');
        
        fileInput.on('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const formData = new FormData();
                formData.append('journal_file', file);
                formData.append('action', 'lmb_admin_upload_temporary_journal');
                formData.append('nonce', lmb_ajax_params.nonce);
                formData.append('ad_id', adId);
                
                $.ajax({
                    url: lmb_ajax_params.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                }).done(function(response) {
                    if (response.success) {
                        showLMBModal('success', response.data.message);
                        fetchAds($('.lmb-pagination .current').text() || 1);
                    } else {
                        showLMBModal('error', response.data.message);
                    }
                });
            }
        });
        
        fileInput.click();
    });

    // --- Initial Load ---
    fetchAds(1);
});