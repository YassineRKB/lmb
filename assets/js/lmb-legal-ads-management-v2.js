// FILE: assets/js/lmb-legal-ads-management-v2.js

(function($) {
    'use strict';

    /**
     * This function initializes all the logic for a single instance of the widget.
     * @param {jQuery} $scope The jQuery object representing the widget's wrapper element.
     */
    const initLegalAdsManagementWidget = function($scope) {
        const widget = $scope;
        // Prevent re-initialization if the widget is re-rendered.
        if (widget.data('lmb-widget-initialized')) {
            return;
        }
        widget.data('lmb-widget-initialized', true);

        const form = widget.find('#lamv2-ads-filters-form');
        const tableBody = widget.find('.lamv2-data-table tbody');
        const paginationContainer = widget.find('.lamv2-pagination-container');
        
        // Bulk action elements
        const checkAll = widget.find('.lamv2-check-all');
        const bulkActionsBar = widget.find('.lamv2-bulk-actions');
        const bulkActionSelect = widget.find('#lamv2-bulk-action-select');
        const bulkApplyButton = widget.find('#lamv2-bulk-action-apply');
        const bulkCountSpan = widget.find('#lamv2-bulk-selected-count');

        // Modal elements
        const uploadModal = widget.find('#lamv2-upload-journal-modal');
        const uploadForm = uploadModal.find('#lamv2-upload-journal-form');
        // Renamed ID input for bulk
        const adIdsInput = uploadModal.find('#lamv2-journal-ad-ids');
        const uploadTargetInfo = uploadModal.find('#lamv2-upload-target-info');


        let debounceTimer;

        // Utility function to get selected IDs and update count
        function getSelectedAdIds() {
            const ids = tableBody.find('.lamv2-ad-checkbox:checked').map(function() {
                return $(this).data('id');
            }).get();
            bulkCountSpan.text(` (${ids.length} sélectionné${ids.length > 1 ? 's' : ''})`);
            
            if (ids.length > 0) {
                bulkActionsBar.removeClass('hidden');
            } else {
                bulkActionsBar.addClass('hidden');
            }
            return ids;
        }
        
        // Main function to fetch and render ads via AJAX
        function fetchAds(page = 1) {
            // Updated colspan to 11 to account for the new checkbox column
            tableBody.html('<tr><td colspan="11" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading ads...</td></tr>');
            bulkActionsBar.addClass('hidden'); // Hide bulk actions on refresh

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
                        // Re-attach event listener for new checkboxes
                        tableBody.find('.lamv2-ad-checkbox').on('change', getSelectedAdIds);
                        checkAll.prop('checked', false); // Uncheck master checkbox
                        bulkActionsBar.addClass('hidden');
                    } else {
                        // Updated colspan to 11
                        tableBody.html('<tr><td colspan="11" style="text-align:center;">' + (response.data.message || 'Aucune annonce trouvée.') + '</td></tr>');
                        paginationContainer.html('');
                    }
                })
                .fail(function() {
                    // Updated colspan to 11
                    tableBody.html('<tr><td colspan="11" style="text-align:center;">Une erreur s\'est produite lors de la récupération des données.</td></tr>');
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
            // Checkbox column interaction is handled separately
            if ($(e.target).is('.lamv2-ad-checkbox') || $(e.target).closest('button, a, .lamv2-actions-cell').length > 0) {
                return;
            }
            const href = $(this).data('href');
            if (href && href !== '#') {
                window.location.href = href;
            }
        });
        
        // --- NEW: Check All / Individual Checkbox Handlers ---
        checkAll.on('change', function() {
            const isChecked = $(this).prop('checked');
            tableBody.find('.lamv2-ad-checkbox').prop('checked', isChecked).trigger('change');
        });
        
        tableBody.on('change', '.lamv2-ad-checkbox', function() {
            getSelectedAdIds();
        });
        // --- END NEW ---

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
            const reason = (action === 'deny') ? prompt('Raison du refus:') : '';

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
                    showLMBModal('error', 'Une erreur serveur inconnue s\'est produite.');
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
            adIdsInput.val(adId);
            uploadTargetInfo.text(`Application pour: Réf ID ${adId}`);
            uploadModal.removeClass('hidden');
        });
        
        // Single clean ad association action
        tableBody.on('click', '.lmb-clean-ad-btn', function(e) {
            e.stopPropagation();
            const button = $(this);
            const adId = button.data('id');
            
            if (!confirm('ATTENTION: Voulez-vous vraiment nettoyer l\'association du journal pour cette annonce ? Cela supprimera les liens (temporel ou final) et l\'Accusé PDF')) {
                return;
            }

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            // Send as array for unified PHP handling
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_clean_ad_association',
                nonce: lmb_ajax_params.nonce,
                ad_ids: [adId], 
            }).done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    const currentPage = paginationContainer.find('.current').text() || 1;
                    fetchAds(currentPage); 
                } else {
                    showLMBModal('error', response.data.message || 'Une erreur s\'est produite lors du nettoyage.');
                    button.html('<i class="fas fa-broom"></i>').prop('disabled', false);
                }
            }).fail(function() {
                showLMBModal('error', 'Une erreur serveur s\'est produite lors du nettoyage.');
                button.html('<i class="fas fa-broom"></i>').prop('disabled', false);
            });
        });


        // --- NEW: Bulk Action Handler ---
        bulkApplyButton.on('click', function() {
            const selectedAction = bulkActionSelect.val();
            const selectedIds = getSelectedAdIds();
            const button = $(this);
            
            if (selectedIds.length === 0 || selectedAction === '') {
                showLMBModal('error', 'Veuillez sélectionner au moins une annonce et une action.');
                return;
            }
            
            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            if (selectedAction === 'bulk_clean') {
                if (!confirm(`Voulez-vous vraiment nettoyer l'association de journal/accusé pour les ${selectedIds.length} annonces sélectionnées ?`)) {
                    button.html('Appliquer').prop('disabled', false);
                    return;
                }
                
                $.post(lmb_ajax_params.ajaxurl, {
                    action: 'lmb_clean_ad_association',
                    nonce: lmb_ajax_params.nonce,
                    ad_ids: selectedIds
                }).done(function(response) {
                    if (response.success) {
                        showLMBModal('success', response.data.message);
                    } else {
                        showLMBModal('error', response.data.message || 'La procédure de nettoyage groupé a échoué.');
                    }
                    fetchAds($('.lamv2-pagination-container .current').text() || 1);
                }).fail(function() {
                    showLMBModal('error', 'Une erreur serveur s\'est produite lors du nettoyage groupé.');
                    button.html('Appliquer').prop('disabled', false);
                }).always(function() {
                    button.html('Appliquer').prop('disabled', false);
                    bulkActionSelect.val('');
                });
                
            } else if (selectedAction === 'bulk_upload_journal') {
                // Open the modal for bulk upload
                adIdsInput.val(selectedIds.join(','));
                uploadTargetInfo.text(`Application pour ${selectedIds.length} annonces sélectionnées.`);
                uploadModal.removeClass('hidden');
                button.html('Appliquer').prop('disabled', false); // Restore button state after modal opens
                bulkActionSelect.val('');
            }
        });
        
        uploadModal.on('click', '.lamv2-modal-close', function() {
            uploadModal.addClass('hidden');
            uploadForm[0].reset();
            // Clear selections when modal closes
            tableBody.find('.lamv2-ad-checkbox').prop('checked', false);
            checkAll.prop('checked', false);
            getSelectedAdIds(); // Recalculate and hide bar
        });

        uploadForm.on('submit', function(e) {
            e.preventDefault();
            const submitButton = $(this).find('button[type="submit"]');
            const formData = new FormData(this);
            formData.append('action', 'lmb_admin_upload_temporary_journal');
            formData.append('nonce', lmb_ajax_params.nonce);
            
            // The ad_ids are already in the form via adIdsInput
            
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
                    // Clear selections and refresh
                    fetchAds($('.lamv2-pagination-container .current').text() || 1);
                } else {
                    showLMBModal('error', 'Téléchargement Échoué: ' + response.data.message);
                }
            }).fail(function() {
                showLMBModal('error', 'Une erreur serveur inattendue s\'est produite pendant le téléchargement.');
            }).always(function() {
                submitButton.html('Télécharger et Appliquer').prop('disabled', false);
            });
        });

        // --- Initial Load ---
        // Need to ensure fetchAds runs once to populate the table.
        fetchAds(1);
    };

    /**
     * Elementor's Frontend Hook
     * This ensures the script runs for each widget instance, even when loaded via AJAX in the editor.
     */
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/lmb_legal_ads_management_v2.default', initLegalAdsManagementWidget);
    });

})(jQuery);