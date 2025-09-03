jQuery(document).ready(function($) {

    /**
     * This function initializes all the logic for a single instance of the widget.
     * @param {jQuery} $scope The jQuery object representing the widget's wrapper element.
     */
    const initLegalAdsManagementWidget = function($scope) {
        const widget = $scope;
        if (!widget.length || widget.data('lmb-widget-initialized')) {
            return;
        }
        widget.data('lmb-widget-initialized', true);

        const form = widget.find('#lamv2-ads-filters-form');
        const tableBody = widget.find('.lamv2-data-table tbody');
        const paginationContainer = widget.find('.lamv2-pagination-container');

        // Modal elements
        const uploadModal = widget.find('#lamv2-upload-journal-modal');
        const uploadForm = uploadModal.find('#lamv2-upload-journal-form');
        const adIdInput = uploadModal.find('#lamv2-journal-ad-id');

        let debounceTimer;

        // Main function to fetch and render ads via AJAX
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
                        paginationContainer.html('');
                    }
                })
                .fail(function() {
                    tableBody.html('<tr><td colspan="10" style="text-align:center;">An error occurred while fetching data.</td></tr>');
                    paginationContainer.html('');
                });
        }

        // --- Event Handlers ---

        form.on('input change', 'input, select', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetchAds(1);
            }, 500);
        });

        form.on('reset', function() {
            setTimeout(() => fetchAds(1), 1);
        });

        tableBody.on('click', '.lamv2-clickable-row', function(e) {
            if ($(e.target).closest('button, a, .lamv2-actions-cell').length > 0) {
                return;
            }
            const href = $(this).data('href');
            if (href && href !== '#') {
                window.location.href = href;
            }
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
            fetchAds(page);
        });
        
        tableBody.on('click', '.lamv2-ad-action', function(e) {
            e.stopPropagation();
            const button = $(this);
            const adId = button.data('id');
            const action = button.data('action');
            const reason = (action === 'deny') ? prompt('Reason for denial:') : '';

            if (action === 'deny' && reason === null) return;

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_ad_status_change',
                nonce: lmb_ajax_params.nonce,
                ad_id: adId,
                ad_action: action,
                reason: reason
            }).done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    const currentPage = paginationContainer.find('.current').text() || 1;
                    fetchAds(currentPage);
                } else {
                    showLMBModal('error', response.data ? response.data.message : 'An error occurred.');
                    const originalIcon = action === 'approve' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
                    button.html(originalIcon).prop('disabled', false);
                }
            });
        });
        
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
                    fetchAds($('.lamv2-pagination-container .current').text() || 1);
                } else {
                    showLMBModal('error', response.data.message);
                    button.html('<i class="fas fa-receipt"></i>').prop('disabled', false);
                }
            }).fail(function() {
                showLMBModal('error', 'An unknown server error occurred.');
                button.html('<i class="fas fa-receipt"></i>').prop('disabled', false);
            });
        });

        tableBody.on('click', '.lmb-upload-journal-btn', function(e) {
            e.stopPropagation();
            const adId = $(this).data('id');
            adIdInput.val(adId);
            uploadModal.removeClass('hidden');
        });

        uploadModal.on('click', '.lamv2-modal-close', function() {
            uploadModal.addClass('hidden');
            uploadForm[0].reset();
        });

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
                    uploadModal.addClass('hidden');
                    uploadForm[0].reset();
                    fetchAds($('.lamv2-pagination-container .current').text() || 1);
                } else {
                    alert('Upload Failed: ' + response.data.message);
                }
            }).fail(function() {
                alert('An unexpected server error occurred during the upload.');
            }).always(function() {
                submitButton.html('Upload').prop('disabled', false);
            });
        });

        // --- Initial Load ---
        fetchAds(1);
    };

    /**
     * Elementor's Frontend Hook
     * This ensures the script runs for each widget instance, even when loaded via AJAX.
     */
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/lmb_legal_ads_management_v2.default', initLegalAdsManagementWidget);
    });

});

