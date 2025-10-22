jQuery(document).ready(function($) {
    const widget = $('.lmb-upload-newspaper-widget');
    if (!widget.length) return;

    const step1 = $('#lmb-un-step-1');
    const step2 = $('#lmb-un-step-2');
    const progressContainer = $('#lmb-un-progress-container');
    
    const fetchForm = $('#lmb-fetch-eligible-ads-form');
    const uploadForm = $('#lmb-final-upload-form');
    
    const tableBody = $('#lmb-eligible-ads-tbody');
    const adsCount = $('#lmb-ads-count');
    const selectAllCheckbox = $('#lmb-select-all-ads');
    const backBtn = $('#lmb-un-back-btn');

    // Handle Step 1: Fetching eligible ads
    fetchForm.on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        const journalNo = $(this).find('#lmb-journal-no').val();
        
        // Add loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Recherche...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_fetch_eligible_ads_for_newspaper',
            nonce: lmb_ajax_params.nonce,
            filters: formData
       }).done(function(response) {
            if (response.success) {
                tableBody.html(response.data.html);
                adsCount.text(response.data.count);
                step1.hide();
                step2.show();
                // FIX: Do not force select all. Let the server-side logic control which boxes are checked.
                selectAllCheckbox.prop('checked', false); 
            } else {
                alert('Erreur: ' + response.data.message);
            }
        }).fail(function() {
            alert('Une erreur de serveur s\'est produite.');
        }).always(function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-search"></i> Rechercher les Annonces Éligibles');
        });
    });

    // Handle Step 2: Final Upload and Association
    uploadForm.on('submit', function(e) {
        e.preventDefault();

        const selectedAds = tableBody.find('input.lmb-ad-checkbox:checked');
        const adIds = selectedAds.map(function() {
            return $(this).val();
        }).get();

        if (adIds.length === 0) {
            alert('Veuillez sélectionner au moins une annonce à associer.');
            return;
        }

        // --- START: NEW CONFIRMATION LOGIC ---
        const replacementRows = selectedAds.filter(function() {
            return $(this).closest('tr').data('status') === 'replacement';
        });

        if (replacementRows.length > 0) {
            const confirmation = confirm(
                'ATTENTION !\n\n' +
                'Vous êtes sur le point de remplacer le journal final pour ' + replacementRows.length + ' annonce(s).\n\n' +
                'Êtes-vous sûr de vouloir continuer ? Cette action est irréversible.'
            );
            if (!confirmation) {
                return; // Stop if the user cancels
            }
        }
        // --- END: NEW CONFIRMATION LOGIC ---

        const fileInput = $('#lmb-final-pdf-upload')[0];
        if (fileInput.files.length === 0) {
            alert('Veuillez sélectionner un fichier PDF.');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'lmb_associate_final_newspaper');
        formData.append('nonce', lmb_ajax_params.nonce);
        formData.append('journal_no', fetchForm.find('#lmb-journal-no').val());
        formData.append('start_date', fetchForm.find('#lmb-start-date').val());
        formData.append('end_date', fetchForm.find('#lmb-end-date').val());
        formData.append('newspaper_pdf', fileInput.files[0]);
        adIds.forEach(adId => {
            formData.append('ad_ids[]', adId);
        });

        step2.hide();
        progressContainer.show();
        const progressBar = $('#lmb-progress-bar-inner');
        const progressText = $('#lmb-progress-text');
        
        // (The rest of the AJAX call remains exactly the same)
        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        progressBar.css('width', percentComplete + '%');
                        progressText.text('Téléchargement: ' + Math.round(percentComplete) + '%');
                    }
                }, false);
                return xhr;
            },
            beforeSend: function() {
                progressBar.css('width', '0%');
                progressText.text('Initialisation du téléchargement...');
            },
            success: function(response) {
                if (response.success) {
                    progressText.text('Association terminée avec succès!');
                    progressBar.addClass('bg-success');
                    alert(response.data.message);
                    location.reload(); 
                } else {
                    progressText.text('Erreur: ' + response.data.message);
                    progressBar.addClass('bg-danger');
                    alert('Erreur: ' + response.data.message);
                    progressContainer.hide();
                    step2.show();
                }
            },
            error: function() {
                progressText.text('Une erreur de serveur critique s\'est produite.');
                progressBar.addClass('bg-danger');
                alert('Une erreur de serveur critique s\'est produite.');
                progressContainer.hide();
                step2.show();
            }
        });
    });

    // Toggle all checkboxes
    selectAllCheckbox.on('change', function() {
        tableBody.find('input.lmb-ad-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Handle back button
    backBtn.on('click', function() {
        step2.hide();
        step1.show();
        tableBody.empty();
        uploadForm[0].reset();
    });
});