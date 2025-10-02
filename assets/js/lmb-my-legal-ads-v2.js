// FILE: assets/js/lmb-my-legal-ads-v2.js
jQuery(document).ready(function($) {
    
    // Find each instance of the V2 user widget on the page
    $('.lmb-my-legal-ads-v2').each(function() {
        const widget = $(this);
        const tableBody = widget.find('.lmb-my-ads-table-v2 tbody');
        const paginationContainer = widget.find('.lmb-pagination-container');
        const filtersForm = widget.find('form');
        
        // Read settings from the Elementor controls (passed via data attributes)
        const status = widget.data('status');
        const postsPerPage = widget.data('posts-per-page');

        // Debounce function to prevent firing AJAX on every keystroke
        const debounce = (func, delay) => {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        };

        // Main function to fetch and render ads
        const fetchAds = (page = 1) => {
            // Show loading state
            tableBody.html('<tr><td colspan="7" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading ads...</td></tr>');
            
            const formData = filtersForm.serialize();
            
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_fetch_my_ads_v2', // We will create this new action next
                nonce: lmb_ajax_params.nonce,
                status: status,
                posts_per_page: postsPerPage,
                paged: page,
                filters: formData
            }).done(function(response) {
                if (response.success) {
                    tableBody.html(response.data.html);
                    paginationContainer.html(response.data.pagination);
                } else {
                    tableBody.html('<tr><td colspan="7" style="text-align:center;">' + (response.data.message || 'Impossible de charger les annonces.') + '</td></tr>');
                    paginationContainer.empty();
                }
            }).fail(function() {
                tableBody.html('<tr><td colspan="7" style="text-align:center;">Une erreur s\'est produite. Veuillez réessayer.</td></tr>');
                paginationContainer.empty();
            });
        };

        // --- EVENT LISTENERS ---

        // Handle filtering (with a 500ms debounce)
        const debouncedFetch = debounce(fetchAds, 500);
        filtersForm.on('keyup change', 'input, select', function(e) {
            e.preventDefault();
            debouncedFetch(1); // Reset to page 1 on filter change
        });
        
        // Handle form reset
        filtersForm.on('reset', function() {
            setTimeout(() => fetchAds(1), 1); // Use timeout to allow form to clear first
        });

        // Handle pagination clicks
        paginationContainer.on('click', 'a.page-numbers', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'), window.location.origin);
            const page = url.searchParams.get('paged') || 1;
            fetchAds(page);
        });
        
        // Clickable row functionality (already present, just ensuring it's within the jQuery wrapper)
        tableBody.on('click', 'tr.clickable-row', function(e) {
            if ($(e.target).closest('button, a').length === 0) {
                const adUrl = $(this).data('href');
                if (adUrl) {
                    window.location.href = adUrl;
                }
            }
        });

        // Handle Draft Actions (Submit/Delete)
        tableBody.on('click', '.lmb-submit-ad-btn, .lmb-delete-ad-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const adId = button.data('ad-id');
            const isSubmit = button.hasClass('lmb-submit-ad-btn');
            const actionType = isSubmit ? 'lmb_submit_draft_ad_v2' : 'lmb_delete_draft_ad_v2';
            
            if (actionType === 'lmb_delete_draft_ad_v2' && !confirm('Êtes-vous sûr de vouloir supprimer ce brouillon ? Cela ne peut pas être annulé.')) {
                return;
            }

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: actionType,
                nonce: lmb_ajax_params.nonce,
                ad_id: adId
            }).done(function(response) {
                // This handles successful submissions (HTTP 200)
                if(response.success) {
                    if (typeof showLMBModal === 'function') {
                        showLMBModal('success', response.data.message || 'Opération réussie.');
                    } else {
                        alert(response.data.message || 'Opération réussie.');
                    }
                    // Refresh the entire page to update all widget instances.
                    setTimeout(() => location.reload(), 500);
                } else {
                    // This handles PHP errors returning a 200 status but success=false
                    const errorMessage = response.data.message || 'Une erreur s\'est produite.';
                    if (typeof showLMBModal === 'function') {
                        showLMBModal('error', errorMessage);
                    } else {
                        alert(errorMessage);
                    }
                }
            }).fail(function(jqXHR, textStatus, errorThrown) {
                 // CRITICAL FIX: This correctly captures the error from the PHP non-200 response (402 or 500)
                 let errorMessage = 'Une erreur de communication serveur s\'est produite.';
                 
                 if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                     // Extract the custom error message from the PHP JSON response
                     errorMessage = jqXHR.responseJSON.data.message;
                 } else if (textStatus === 'error' && errorThrown) {
                     errorMessage = 'Erreur: ' + errorThrown;
                 }
                 
                 // Display the message using the preferred method (Modal or Alert)
                 if (typeof showLMBModal === 'function') {
                    showLMBModal('error', errorMessage);
                 } else {
                    alert(errorMessage);
                 }
            }).always(function() {
                // Restore button state (using the ternary operator to check if it's the submit button)
                const originalHtml = isSubmit ? '<i class="fas fa-paper-plane"></i> Submit' : '<i class="fas fa-trash"></i> Delete';
                button.html(originalHtml).prop('disabled', false);
            });
        });

        // Initial load of ads
        fetchAds();
    });
});