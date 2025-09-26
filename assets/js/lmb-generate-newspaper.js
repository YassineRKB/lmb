// FILE: assets/js/lmb-generate-newspaper.js
jQuery(document).ready(function($) {
    // Injecting the alert to confirm script execution.
    // alert("sd"); 
    
    const widget = $('.lmb-generate-newspaper-widget');
    if (!widget.length) return;

    const filtersForm = $('#lmb-newspaper-filters-form');
    const tableBody = $('#lmb-ads-to-include-table tbody');
    const visualizeBtn = $('#lmb-visualize-btn');
    const selectAllCheckbox = $('#select-all-ads');
    const filterAdsBtn = $('#lmb-filter-ads-btn'); 
    
    const step1 = $('#step-1-filters');
    const step2 = $('#step-2-preview');
    const previewArea = $('#lmb-newspaper-preview-area');
    const previewControls = $('#lmb-preview-controls');
    
    let currentSelectedAds = [];
    
    // --- Core Functions ---

    /**
     * Fetches eligible ads based on the current form filters (Journal No, Start Date, End Date).
     */
    function fetchAds() {
        // Prevent form submission
        filtersForm.off('submit').on('submit', function(e) { e.preventDefault(); });
        
        const journalNo = $('#journal_no').val();
        const dateStart = $('#date_start').val();
        const dateEnd = $('#date_end').val();

        if (!journalNo || !dateStart || !dateEnd) {
            // Display standard prompt if filters are missing
            tableBody.html('<tr><td colspan="6" style="text-align:center;">Veuillez définir le numéro de journal et la période de dates requises.</td></tr>');
            visualizeBtn.prop('disabled', true);
            currentSelectedAds = [];
            return;
        }

        tableBody.html('<tr><td colspan="6" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Chargement des annonces...</td></tr>');
        visualizeBtn.prop('disabled', true);
        
        // Disable and show loading state on the filter button
        filterAdsBtn.prop('disabled', true).addClass('lmb-btn-loading').find('i').removeClass('fa-filter').addClass('fa-spinner fa-spin');
        
        const formData = filtersForm.serialize();

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_fetch_eligible_ads',
            nonce: lmb_ajax_params.nonce,
            filters: formData
        }).done(function(response) {
            if (response.success && response.data.ads && response.data.ads.length > 0) {
                renderAdsTable(response.data.ads);
                
                const fetchedIds = response.data.ads.map(ad => ad.ID.toString());
                
                // Keep only selected ads that are still in the filtered list
                currentSelectedAds = currentSelectedAds.filter(id => fetchedIds.includes(id.toString()));
                
                // If selection is empty after filtering, default to selecting all
                if (currentSelectedAds.length === 0) {
                     currentSelectedAds = fetchedIds;
                }
                
                // Apply selection to checkboxes
                tableBody.find('.ad-selector').each(function() {
                    const adId = $(this).data('ad-id').toString();
                    $(this).prop('checked', currentSelectedAds.includes(adId));
                });

                visualizeBtn.prop('disabled', currentSelectedAds.length === 0);
            } else {
                tableBody.html('<tr><td colspan="6" style="text-align:center;">' + (response.data.message || 'Aucune annonce publiée trouvée dans cette période.') + '</td></tr>');
                visualizeBtn.prop('disabled', true);
                currentSelectedAds = [];
            }
            // Update select-all header state
            updateSelectAllState();
        }).fail(function() {
            tableBody.html('<tr><td colspan="6" style="text-align:center;">Erreur de communication avec le serveur. Vérifiez les logs.</td></tr>');
            visualizeBtn.prop('disabled', true);
            currentSelectedAds = [];
        }).always(function() {
            // Reset filter button state
            filterAdsBtn.prop('disabled', false).removeClass('lmb-btn-loading').find('i').removeClass('fa-spinner fa-spin').addClass('fa-filter');
        });
    }

    /**
     * Renders the fetched ads into the table body.
     * @param {Array} ads - List of ad objects.
     */
    function renderAdsTable(ads) {
        let html = '';
        ads.forEach(ad => {
            html += `
                <tr data-ad-id="${ad.ID}">
                    <td><input type="checkbox" class="ad-selector" data-ad-id="${ad.ID}"></td>
                    <td>${ad.ID}</td>
                    <td>${ad.company_name}</td>
                    <td>${ad.ad_type}</td>
                    <td>${ad.approved_date}</td>
                    <td>${ad.journal_status}</td>
                </tr>
            `;
        });
        tableBody.html(html);
    }
    
    /**
     * Updates the state of the master "Select All" checkbox.
     */
    function updateSelectAllState() {
         const totalAds = tableBody.find('.ad-selector').length;
         const checkedAds = tableBody.find('.ad-selector:checked').length;
         
         if (totalAds > 0) {
            selectAllCheckbox.prop('checked', totalAds === checkedAds);
            selectAllCheckbox.prop('indeterminate', checkedAds > 0 && checkedAds < totalAds);
         } else {
             selectAllCheckbox.prop('checked', false).prop('indeterminate', false);
         }
    }


    // --- Event Handlers ---
    
    // NEW: Filter button click listener - Triggers data fetch
    filterAdsBtn.on('click', function() {
         fetchAds();
    });

    // Handle single ad selection update (delegated to table body)
    tableBody.on('change', '.ad-selector', function() {
        const adId = $(this).data('ad-id').toString();
        if ($(this).is(':checked')) {
            if (!currentSelectedAds.includes(adId)) currentSelectedAds.push(adId);
        } else {
            currentSelectedAds = currentSelectedAds.filter(id => id.toString() !== adId);
        }
        visualizeBtn.prop('disabled', currentSelectedAds.length === 0);
        updateSelectAllState();
    });
    
    // Handle select all/none
    selectAllCheckbox.on('change', function() {
        const isChecked = $(this).is(':checked');
        tableBody.find('.ad-selector').prop('checked', isChecked);
        
        currentSelectedAds = [];
        if (isChecked) {
            currentSelectedAds = tableBody.find('.ad-selector').map(function() {
                return $(this).data('ad-id').toString();
            }).get();
        }
        visualizeBtn.prop('disabled', currentSelectedAds.length === 0);
        updateSelectAllState();
    });

    // --- STEP 2: Visualize & Publish Workflow ---

    visualizeBtn.on('click', function() {
        if (currentSelectedAds.length === 0) {
            showLMBModal('error', 'Veuillez sélectionner au moins une annonce à inclure.');
            return;
        }
        if(!$('#journal_no').val()){
             showLMBModal('error', 'Veuillez saisir le numéro du journal.');
            return;
        }

        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Préparation...').prop('disabled', true);

        const journalNo = $('#journal_no').val();
        const dateStart = $('#date_start').val();
        const dateEnd = $('#date_end').val();

        // AJAX action to generate temporary PDF link
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_generate_newspaper_preview', 
            nonce: lmb_ajax_params.nonce,
            ad_ids: currentSelectedAds,
            journal_no: journalNo,
            date_start: dateStart,
            date_end: dateEnd
        }).done(function(response) {
            if (response.success && response.data.pdf_url) {
                // Open PDF in new tab
                window.open(response.data.pdf_url, '_blank');
                
                // Transition to Step 2
                step1.hide();
                
                previewArea.html(`
                    <p style="text-align:center; font-size: 16px; color: #155724;">
                        Le journal de prévisualisation (PDF) a été généré et ouvert dans un nouvel onglet.
                    </p>
                    <div class="lmb-preview-link" style="text-align: center; margin-top: 20px;">
                        <a href="${response.data.pdf_url}" target="_blank" class="lmb-btn lmb-btn-secondary lmb-btn-small" style="text-decoration: none;">
                            <i class="fas fa-file-pdf"></i> Ouvrir à Nouveau le PDF
                        </a>
                    </div>`);
                                  
                previewControls.html(`
                    <button type="button" id="lmb-publish-btn" class="lmb-btn lmb-btn-success lmb-btn-large"><i class="fas fa-check-circle"></i> Publier</button>
                    <button type="button" id="lmb-discard-btn" class="lmb-btn lmb-btn-danger lmb-btn-large"><i class="fas fa-times-circle"></i> Discard</button>
                `);

                step2.fadeIn(300);

            } else {
                showLMBModal('error', response.data.message || 'Échec de la génération du PDF de prévisualisation.');
            }
        }).fail(function() {
            showLMBModal('error', 'Erreur serveur lors de la visualisation.');
        }).always(function() {
            btn.html('<i class="fas fa-eye"></i> Visualiser le Journal').prop('disabled', false);
        });
    });

    // Handle Publish Button Click
    step2.on('click', '#lmb-publish-btn', function() {
        const btn = $(this);
        if (!confirm('ATTENTION: Confirmez-vous la publication du Journal ? Cette action est irréversible et associera le Journal FINAL à toutes les annonces sélectionnées.')) return;
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Publication...').prop('disabled', true);
        $('#lmb-discard-btn').prop('disabled', true);
        
        const journalNo = $('#journal_no').val();
        const dateStart = $('#date_start').val();
        const dateEnd = $('#date_end').val();

        // Finalization action
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_approve_and_publish_newspaper', 
            nonce: lmb_ajax_params.nonce,
            ad_ids: currentSelectedAds,
            journal_no: journalNo,
            date_start: dateStart,
            date_end: dateEnd
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                // Reset UI to step 1
                step2.hide();
                step1.fadeIn(300);
                filtersForm[0].reset();
                currentSelectedAds = [];
                // Reset table message
                tableBody.html('<tr><td colspan="6" style="text-align:center;">Veuillez définir la période et cliquer sur \'Filtrer\' pour charger les annonces.</td></tr>');
                visualizeBtn.prop('disabled', true);
            } else {
                showLMBModal('error', response.data.message || 'Échec de l\'approbation et de la publication.');
            }
        }).fail(function() {
            showLMBModal('error', 'Erreur serveur lors de la publication.');
        }).always(function() {
            btn.html('<i class="fas fa-check-circle"></i> Publier').prop('disabled', false);
            $('#lmb-discard-btn').prop('disabled', false);
        });
    });

    // Handle Discard Button Click
    step2.on('click', '#lmb-discard-btn', function() {
        // Reset UI to step 1 (selection screen)
        step2.hide();
        step1.fadeIn(300);
        previewArea.empty();
        previewControls.empty();
    });
    
    // Initial UI setup
    if (typeof lmb_ajax_params !== 'undefined') {
        tableBody.html('<tr><td colspan="6" style="text-align:center;">Veuillez définir la période et cliquer sur \'Filtrer\' pour charger les annonces.</td></tr>');
        visualizeBtn.prop('disabled', true);
    }
});
