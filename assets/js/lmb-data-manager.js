jQuery(document).ready(function($) {
    const page = $('.lmb-data-manager-page');
    if (!page.length) return;

    const filterForm = $('#lmb-dm-filters');
    const resultsForm = $('#lmb-dm-results-form');
    const tableBody = $('#lmb-dm-results-tbody');
    const selectAll = $('#lmb-dm-select-all');
    const paginationContainer = $('.lmb-dm-pagination');

    /**
     * Main function to fetch ads with filters and pagination.
     */
    function fetchAds(page = 1) {
        const submitBtn = filterForm.find('button[type="submit"]');
        submitBtn.prop('disabled', true).text('Recherche...');
        tableBody.html('<tr><td colspan="6" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Chargement des annonces...</td></tr>');

        $.post(ajaxurl, {
            action: 'lmb_fetch_manageable_ads',
            nonce: lmb_ajax_params.nonce,
            paged: page,
            filters: filterForm.serialize()
        }).done(function(response) {
            if (response.success) {
                tableBody.html(response.data.html);
                paginationContainer.html(response.data.pagination);
            } else {
                tableBody.html('<tr><td colspan="6">Une erreur s\'est produite.</td></tr>');
                paginationContainer.html('');
            }
        }).always(function() {
            submitBtn.prop('disabled', false).text('Rechercher');
        });
    }

    // Handle filter submission
    filterForm.on('submit', function(e) {
        e.preventDefault();
        fetchAds(1); // Always go to page 1 on a new search
    });

    // Handle filter reset
    filterForm.on('reset', function() {
        setTimeout(() => fetchAds(1), 1);
    });
    
    // Handle pagination clicks
    paginationContainer.on('click', 'a.page-numbers', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        const pageNumMatch = href.match(/paged=(\d+)/);
        const page = pageNumMatch ? pageNumMatch[1] : 1;
        fetchAds(page);
    });

    // --- (The rest of the JS for bulk actions and individual actions remains unchanged) ---

    // Handle bulk actions
    resultsForm.on('submit', function(e) {
        e.preventDefault();
        const action = $('#lmb-dm-bulk-action-select').val();
        const selectedAds = tableBody.find('input[name="ad_ids[]"]:checked').map(function() { return $(this).val(); }).get();

        if (action === '-1' || selectedAds.length === 0) {
            alert('Veuillez sélectionner une action et au moins une annonce.');
            return;
        }

        if (confirm('Êtes-vous sûr de vouloir appliquer cette action à ' + selectedAds.length + ' annonce(s) ?')) {
            if (action === 'dissociate') {
                $.post(ajaxurl, {
                    action: 'lmb_dissociate_ads',
                    nonce: lmb_ajax_params.nonce,
                    ad_ids: selectedAds
                }).done(function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        fetchAds(1); // Refresh the table
                    } else {
                        alert('Erreur: ' + response.data.message);
                    }
                });
            }
        }
    });

    // Handle individual action: Set new journal number
    tableBody.on('click', '.lmb-dm-set-journal-no', function() {
        const btn = $(this);
        const row = btn.closest('tr');
        const adId = row.data('ad-id');
        const newJournalNo = row.find('.lmb-dm-new-journal-no').val();

        if (newJournalNo.trim() === '') {
            alert('Veuillez entrer un nouveau numéro de journal.');
            return;
        }

        btn.prop('disabled', true);
        $.post(ajaxurl, {
            action: 'lmb_update_ad_journal_no',
            nonce: lmb_ajax_params.nonce,
            ad_id: adId,
            new_journal_no: newJournalNo
        }).done(function(response) {
            if (response.success) {
                alert(response.data.message);
                fetchAds(1); // Refresh
            } else {
                alert('Erreur: ' + response.data.message);
            }
        }).always(function() {
            btn.prop('disabled', false);
        });
    });

    // Handle "Select All" checkbox
    selectAll.on('change', function() {
        tableBody.find('input[name="ad_ids[]"]').prop('checked', $(this).is(':checked'));
    });

    // --- Initial Load ---
    fetchAds(1);
});