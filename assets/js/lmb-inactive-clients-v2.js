// FILE: assets/js/lmb-inactive-clients-v2.js
jQuery(document).ready(function($) {
    
    $('.lmb-inactive-clients-v2').each(function() {
        const widget = $(this);
        const clientList = widget.find('.lmb-inactive-clients-list');
        const paginationContainer = widget.find('.lmb-pagination-container');
        const searchInput = widget.find('#lmb-inactive-client-search');
        const resetButton = widget.find('#lmb-inactive-client-reset');
        const clientsPerPage = widget.data('per-page');

        const debounce = (func, delay) => {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        };

        const fetchClients = (page = 1, searchTerm = '') => {
            clientList.html('<div style="text-align:center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading clients...</div>');
            
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_fetch_inactive_clients_v2',
                nonce: lmb_ajax_params.nonce,
                paged: page,
                per_page: clientsPerPage,
                search_term: searchTerm
            }).done(function(response) {
                if (response.success) {
                    clientList.html(response.data.html);
                    paginationContainer.html(response.data.pagination);
                } else {
                    clientList.html('<div style="text-align:center; padding: 20px;">' + (response.data.message || 'Could not load clients.') + '</div>');
                    paginationContainer.empty();
                }
            }).fail(function() {
                clientList.html('<div style="text-align:center; padding: 20px;">An error occurred. Please try again.</div>');
                paginationContainer.empty();
            });
        };

        const debouncedFetch = debounce(() => fetchClients(1, searchInput.val()), 500);
        searchInput.on('keyup', debouncedFetch);
        
        resetButton.on('click', function() {
            searchInput.val('');
            fetchClients(1);
        });

        paginationContainer.on('click', 'a.page-numbers', function(e) {
            e.preventDefault();
            const url = new URL($(this).attr('href'), window.location.origin);
            const page = url.searchParams.get('paged') || 1;
            fetchClients(page, searchInput.val());
        });

        // --- MODIFIED: Handle Approve/Deny actions with modals ---
        clientList.on('click', '.lmb-client-action-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const card = button.closest('.lmb-client-card');
            const userId = button.data('user-id');
            const approvalAction = button.data('action');

            if (approvalAction === 'deny' && !confirm('Are you sure you want to deny and permanently delete this user? This action cannot be undone.')) {
                return;
            }

            card.css('opacity', 0.5);
            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_manage_inactive_client_v2',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                approval_action: approvalAction
            }).done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    card.fadeOut(400, function() { $(this).remove(); });
                } else {
                    showLMBModal('error', response.data.message || 'An error occurred.');
                    card.css('opacity', 1);
                    const originalText = approvalAction === 'approve' ? '<i class="fas fa-check"></i> Approve' : '<i class="fas fa-times"></i> Deny';
                    button.html(originalText).prop('disabled', false);
                }
            }).fail(function() {
                showLMBModal('error', 'A server error occurred. Please try again.');
                card.css('opacity', 1);
                const originalText = approvalAction === 'approve' ? '<i class="fas fa-check"></i> Approve' : '<i class="fas fa-times"></i> Deny';
                button.html(originalText).prop('disabled', false);
            });
        });

        fetchClients();
    });
});