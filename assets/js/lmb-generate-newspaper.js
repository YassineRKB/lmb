// FILE: assets/js/lmb-generate-newspaper.js
jQuery(document).ready(function($) {
    const widget = $('.lmb-generate-newspaper-widget');
    if (!widget.length) return;

    const filtersForm = $('#lmb-newspaper-filters-form');
    const tableBody = $('#lmb-ads-to-include-table tbody');
    const visualizeBtn = $('#lmb-visualize-btn');
    const approveBtn = $('#lmb-approve-btn');
    const backBtn = $('#lmb-back-to-selection-btn');
    const selectAllCheckbox = $('#select-all-ads');

    const step1 = $('#step-1-filters');
    const step2 = $('#step-2-preview');
    const previewArea = $('#lmb-newspaper-preview-area');

    let currentSelectedAds = [];
    let debounceTimer;

    // --- STEP 1: Fetch and Select Ads ---

    function fetchAds() {
        const journalNo = $('#journal_no').val();
        const dateStart = $('#date_start').val();
        const dateEnd = $('#date_end').val();

        if (!journalNo || !dateStart || !dateEnd) {
            tableBody.html('<tr><td colspan="6" style="text-align:center;">Veuillez définir le numéro de journal et la période de dates.</td></tr>');
            visualizeBtn.prop('disabled', true);
            return;
        }

        tableBody.html('<tr><td colspan="6" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Chargement des annonces...</td></tr>');
        
        const formData = filtersForm.serialize();

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_fetch_eligible_ads', // New AJAX action
            nonce: lmb_ajax_params.nonce,
            filters: formData
        }).done(function(response) {
            if (response.success && response.data.ads.length > 0) {
                renderAdsTable(response.data.ads);
                currentSelectedAds = response.data.ads.map(ad => ad.ID); // Select all by default
                visualizeBtn.prop('disabled', false);
            } else {
                tableBody.html('<tr><td colspan="6" style="text-align:center;">' + (response.data.message || 'Aucune annonce publiée trouvée dans cette période.') + '</td></tr>');
                visualizeBtn.prop('disabled', true);
                currentSelectedAds = [];
            }
        }).fail(function() {
            tableBody.html('<tr><td colspan="6" style="text-align:center;">Erreur de communication avec le serveur.</td></tr>');
            visualizeBtn.prop('disabled', true);
            currentSelectedAds = [];
        });
    }

    function renderAdsTable(ads) {
        let html = '';
        ads.forEach(ad => {
            html += `
                <tr data-ad-id="${ad.ID}">
                    <td><input type="checkbox" class="ad-selector" data-ad-id="${ad.ID}" checked></td>
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
    
    // Auto-fetch on input change
    filtersForm.on('input change', 'input:not(#select-all-ads), select', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchAds, 800);
    });

    // Handle single ad selection update
    tableBody.on('change', '.ad-selector', function() {
        const adId = $(this).data('ad-id');
        if ($(this).is(':checked')) {
            if (!currentSelectedAds.includes(adId)) currentSelectedAds.push(adId);
        } else {
            currentSelectedAds = currentSelectedAds.filter(id => id !== adId);
        }
        visualizeBtn.prop('disabled', currentSelectedAds.length === 0);
        
        // Update select-all state
        const allChecked = tableBody.find('.ad-selector').length === currentSelectedAds.length;
        selectAllCheckbox.prop('checked', allChecked);
    });
    
    // Handle select all/none
    selectAllCheckbox.on('change', function() {
        const isChecked = $(this).is(':checked');
        tableBody.find('.ad-selector').prop('checked', isChecked).trigger('change', { silent: true });
        
        if (isChecked) {
            currentSelectedAds = tableBody.find('.ad-selector').map(function() {
                return $(this).data('ad-id');
            }).get();
        } else {
            currentSelectedAds = [];
        }
        visualizeBtn.prop('disabled', currentSelectedAds.length === 0);
    });


    // --- STEP 2: Visualize ---

    visualizeBtn.on('click', function() {
        if (currentSelectedAds.length === 0) {
            showLMBModal('error', 'Veuillez sélectionner au moins une annonce à inclure.');
            return;
        }

        const btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> Préparation...').prop('disabled', true);

        const journalNo = $('#journal_no').val();
        const dateStart = $('#date_start').val();
        const dateEnd = $('#date_end').val();

        // New AJAX action to generate HTML for preview
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_generate_newspaper_preview', 
            nonce: lmb_ajax_params.nonce,
            ad_ids: currentSelectedAds,
            journal_no: journalNo,
            date_start: dateStart,
            date_end: dateEnd
        }).done(function(response) {
            if (response.success && response.data.html) {
                // Load HTML into an iframe for clean preview rendering
                previewArea.html(`<iframe id="lmb-newspaper-preview-iframe" src="about:blank"></iframe>`);
                const iframe = $('#lmb-newspaper-preview-iframe').get(0);
                const doc = iframe.contentWindow.document;
                
                doc.open();
                doc.write(response.data.html);
                doc.close();
                
                step1.hide();
                step2.fadeIn(300);
            } else {
                showLMBModal('error', response.data.message || 'Échec de la génération du journal de prévisualisation.');
            }
        }).fail(function() {
            showLMBModal('error', 'Erreur serveur lors de la visualisation.');
        }).always(function() {
            btn.html('<i class="fas fa-eye"></i> Visualiser le Journal').prop('disabled', false);
        });
    });

    // Back to Selection
    backBtn.on('click', function() {
        step2.hide();
        step1.fadeIn(300);
        previewArea.empty();
    });

    // --- STEP 3: Approve and Publish ---

    approveBtn.on('click', function() {
        const btn = $(this);
        if (!confirm('ATTENTION: Confirmez-vous la publication du Journal ? Cette action est irréversible et associera le Journal FINAL à toutes les annonces sélectionnées.')) return;
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Publication...').prop('disabled', true);
        
        const journalNo = $('#journal_no').val();
        const dateStart = $('#date_start').val();
        const dateEnd = $('#date_end').val();

        // Use a hidden form field to pass the generated HTML for final PDF generation
        const finalHTML = previewArea.find('iframe').contents().find('html').prop('outerHTML');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_approve_and_publish_newspaper', // New finalization action
            nonce: lmb_ajax_params.nonce,
            ad_ids: currentSelectedAds,
            journal_no: journalNo,
            date_start: dateStart,
            date_end: dateEnd,
            final_html_content: finalHTML
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                // Reset UI to start new process
                step2.hide();
                step1.fadeIn(300);
                filtersForm[0].reset();
                fetchAds(); 
            } else {
                showLMBModal('error', response.data.message || 'Échec de l\'approbation et de la publication.');
            }
        }).fail(function() {
            showLMBModal('error', 'Erreur serveur lors de la publication.');
        }).always(function() {
            btn.html('<i class="fas fa-check-circle"></i> Approuver et Publier le Journal').prop('disabled', false);
        });
    });
    
    // Initial load check (if dates are pre-filled)
    fetchAds();
});
