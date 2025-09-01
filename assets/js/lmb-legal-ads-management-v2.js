jQuery(document).ready(function($) {
    const widget = $('.lmb-legal-ads-management-v2');
    if (!widget.length) return;

    const form = widget.find('#lmb-ads-filters-form-v2');
    const tableBody = widget.find('.lmb-ads-table-v2 tbody');
    const paginationContainer = widget.find('.lmb-pagination-container');
    let debounceTimer;

    // --- Main function to fetch and render ads via AJAX ---
    function fetchAds(page = 1) {
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

    // Handle real-time filtering with a debounce to prevent excessive AJAX calls
    form.on('input change', 'input, select', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            fetchAds(1); // Reset to page 1 on any new filter
        }, 500);
    });

    // Handle the filter reset button
    form.on('reset', function(e) {
        e.preventDefault();
        form.find('input[type="text"], input[type="date"], select').val('');
        fetchAds(1);
    });

    // Handle clicking on a table row to navigate to the edit page
    tableBody.on('click', 'tr.clickable-row', function(e) {
        // Prevent navigation if a button or link inside the row was the target
        if ($(e.target).closest('button, a, .lmb-actions-cell').length > 0) {
            return;
        }
        const href = $(this).data('href');
        if (href && href !== '#') {
            window.location.href = href;
        }
    });
    
    // Handle pagination clicks
    paginationContainer.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const urlParams = new URLSearchParams(href.split('?')[1]);
        const page = urlParams.get('paged') || 1;
        fetchAds(page);
    });

    // Handle Approve/Deny action buttons
    tableBody.on('click', '.lmb-ad-action', function(e) {
        e.stopPropagation(); // Prevents the row click event
        const button = $(this);
        const adId = button.data('id');
        const action = button.data('action');
        const reason = (action === 'deny') ? prompt('Reason for denial:') : '';

        if (action === 'deny' && reason === null) return; // User cancelled prompt

        button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_ad_status_change',
            nonce: lmb_ajax_params.nonce,
            ad_id: adId,
            status: action,
            reason: reason
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                fetchAds($('.lmb-pagination .current').text() || 1);
            } else {
                showLMBModal('error', response.data ? response.data.message : 'An error occurred.');
                button.html(action === 'approve' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>').prop('disabled', false);
            }
        });
    });

    // Handle Generate Accuse button click
    tableBody.on('click', '.lmb-generate-accuse-btn', function(e) {
        e.stopPropagation(); // Prevents the row click event
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
                fetchAds($('.lmb-pagination .current').text() || 1);
            } else {
                showLMBModal('error', response.data.message);
                button.html('<i class="fas fa-receipt"></i>').prop('disabled', false);
            }
        }).fail(function() {
            showLMBModal('error', 'An unknown server error occurred.');
            button.html('<i class="fas fa-receipt"></i>').prop('disabled', false);
        });
    });

    // Handle Upload Temporary Journal button click
    tableBody.on('click', '.lmb-upload-journal-btn', function(e) {
        e.stopPropagation(); // Prevents the row click event
        const button = $(this);
        const adId = button.data('id');
        const journalCell = button.closest('tr').find('.journal-cell');
        
        // Prompt for the Journal Number before opening file dialog
        const journalNo = prompt("Please enter the Journal N° for this temporary newspaper:");
        if (!journalNo) {
            return; // Exit if the user cancels or enters nothing
        }
        
        const fileInput = $('<input type="file" accept="application/pdf" style="display: none;">');
        
        fileInput.on('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                const formData = new FormData();
                formData.append('journal_file', file);
                formData.append('action', 'lmb_admin_upload_temporary_journal');
                formData.append('nonce', lmb_ajax_params.nonce);
                formData.append('ad_id', adId);
                formData.append('journal_no', journalNo); // Add journal number to the request
                
                const originalContent = journalCell.html();
                journalCell.html('<i class="fas fa-spinner fa-spin"></i> Uploading...');
                button.prop('disabled', true);
                
                $.ajax({
                    url: lmb_ajax_params.ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                }).done(function(response) {
                    if (response.success) {
                        showLMBModal('success', response.data.message);
                        // After a successful upload, ask the admin if they want to generate the accuse
                        if (confirm("Temporary journal uploaded. Do you want to generate the accuse now?")) {
                            // Find the accuse button in the same row and trigger a click
                            const accuseButton = button.closest('tr').find('.lmb-generate-accuse-btn');
                             if (accuseButton.length) {
                                accuseButton.click();
                            } else {
                                // If no button (e.g., accuse already exists), just refresh the table
                                fetchAds($('.lmb-pagination .current').text() || 1);
                            }
                        } else {
                            fetchAds($('.lmb-pagination .current').text() || 1);
                        }
                    } else {
                        showLMBModal('error', response.data.message);
                        journalCell.html(originalContent);
                        button.prop('disabled', false);
                    }
                }).fail(function() {
                    showLMBModal('error', 'An unexpected server error occurred.');
                    journalCell.html(originalContent);
                    button.prop('disabled', false);
                });
            }
        });
        
        fileInput.click();
    });

    // --- Initial Load ---
    fetchAds(1);
});