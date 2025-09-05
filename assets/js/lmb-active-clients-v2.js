// FILE: assets/js/lmb-active-clients-v2.js
jQuery(document).ready(function($) {
    
    $('.lmb-active-clients-v2').each(function() {
        const widget = $(this);
        const tableBody = widget.find('.lmb-data-table tbody');
        const paginationContainer = widget.find('.lmb-pagination-container');
        const filtersForm = widget.find('#lmb-active-clients-filters');
        const clientsPerPage = widget.data('per-page');

        const debounce = (func, delay) => {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        };

        const fetchClients = (page = 1) => {
            tableBody.html('<tr><td colspan="7" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading clients...</td></tr>');
            
            const formData = filtersForm.serialize();

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_fetch_active_clients_v2',
                nonce: lmb_ajax_params.nonce,
                paged: page,
                per_page: clientsPerPage,
                filters: formData
            }).done(function(response) {
                if (response.success) {
                    tableBody.html(response.data.html);
                    paginationContainer.html(response.data.pagination);
                } else {
                    tableBody.html('<tr><td colspan="7" style="text-align:center;">' + (response.data.message || 'Impossible de charger les clients.') + '</td></tr>');
                    paginationContainer.empty();
                }
            }).fail(function() {
                tableBody.html('<tr><td colspan="7" style="text-align:center;">Une erreur s\'est produite. Veuillez réessayer.</td></tr>');
                paginationContainer.empty();
            });
        };

        const debouncedFetch = debounce(() => fetchClients(1), 500);
        filtersForm.on('keyup change', 'input, select', function(e) {
            e.preventDefault();
            debouncedFetch();
        });
        
        filtersForm.on('reset', function() {
            setTimeout(() => fetchClients(1), 1);
        });

        paginationContainer.on('click', 'a.page-numbers', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'), window.location.origin);
            const page = url.searchParams.get('paged') || 1;
            fetchClients(page);
        });

        // --- MODIFIED: Handle Lock User action with Modal ---
        tableBody.on('click', '.lmb-lock-user-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const row = button.closest('tr');
            const userId = button.data('user-id');

            if (!confirm('Are you sure you want to lock this user? Their account will be deactivated.')) {
            if (!confirm('Êtes-vous sûr de vouloir verrouiller cet utilisateur ? Son compte sera désactivé.')) {
                return;
            }
            
            row.css('opacity', 0.5);
            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_lock_active_client_v2',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
            }).done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message || 'L\'utilisateur a été verrouillé.');
                    row.fadeOut(400, function() { $(this).remove(); });
                } else {
                    showLMBModal('error', response.data.message || 'Une erreur s\'est produite.');
                    row.css('opacity', 1);
                    button.html('<i class="fas fa-user-lock"></i>').prop('disabled', false);
                }
            }).fail(function() {
                showLMBModal('error', 'Une erreur serveur s\'est produite. Veuillez réessayer.');
                row.css('opacity', 1);
                button.html('<i class="fas fa-user-lock"></i>').prop('disabled', false);
            });
        });

        fetchClients();
    });
});