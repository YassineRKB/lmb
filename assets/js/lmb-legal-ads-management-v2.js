jQuery(document).ready(function($) {
    const widget = $('.lmb-legal-ads-management-v2');
    if (!widget.length) return;

    const form = widget.find('#lmb-ads-filters-form-v2');
    const tableBody = widget.find('.lmb-ads-table-v2 tbody');
    const paginationContainer = widget.find('.lmb-pagination-container');

    // --- Modal elements ---
    const uploadModal = widget.find('#lmb-upload-journal-modal');
    const uploadForm = uploadModal.find('#lmb-upload-journal-form');
    const adIdInput = uploadModal.find('#lmb-journal-ad-id');

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
                    paginationContainer.html(''); // Clear pagination on error
                }
            })
            .fail(function() {
                tableBody.html('<tr><td colspan="10" style="text-align:center;">An error occurred while fetching data.</td></tr>');
                paginationContainer.html('');
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

    // --- PAGINATION FIX ---
    // Handle pagination clicks (delegated from the container)
    paginationContainer.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        if (!href) return;

        let page = 1;
        try {
            // Create a full URL to easily parse the parameters
            const url = new URL(href, window.location.origin);
            page = url.searchParams.get('paged') || 1;
        } catch (error) {
            // Fallback for older browsers or unexpected URL formats
            const pageNumMatch = href.match(/paged=(\d+)/);
            if (pageNumMatch && pageNumMatch[1]) {
                page = pageNumMatch[1];
            }
        }
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

    // Handle Generate Accuse button (now used for manual generation if needed)
    tableBody.on('click', '.lmb-generate-accuse-btn', function(e) {
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

    // Handle Upload Temporary Journal button (opens the modal)
    tableBody.on('click', '.lmb-upload-journal-btn', function(e) {
        e.stopPropagation();
        const adId = $(this).data('id');
        adIdInput.val(adId);
        uploadModal.show();
    });

    // Handle closing the modal
    uploadModal.on('click', '.lmb-modal-close, .lmb-modal-overlay', function(e) {
        if ($(e.target).is('.lmb-modal-close, .lmb-modal-overlay')) {
            uploadModal.hide();
            uploadForm[0].reset();
        }
    });

    // Handle the modal form submission for temporary journal upload
    uploadForm.on('submit', function(e) {
        e.preventDefault();
        const submitButton = $(this).find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'lmb_admin_upload_temporary_journal');
        formData.append('nonce', lmb_ajax_params.nonce);

        submitButton.html('<i class="fas fa-spinner fa-spin"></i> Uploading...').prop('disabled', true);

        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                uploadModal.hide();
                uploadForm[0].reset();
                fetchAds($('.lmb-pagination .current').text() || 1);
            } else {
                alert('Upload Failed: ' + response.data.message); // Use a simple alert for errors within the modal context
            }
        }).fail(function() {
            alert('An unexpected server error occurred during the upload.');
        }).always(function() {
            submitButton.html('Upload').prop('disabled', false);
        });
    });

    // --- Initial Load ---
    fetchAds(1);
});