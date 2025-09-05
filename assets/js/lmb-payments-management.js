// FILE: assets/js/lmb-payments-management.js
jQuery(document).ready(function($) {
    
    $('.lmb-payments-management').each(function() {
        const widget = $(this);
        const tableBody = widget.find('.lmb-data-table tbody');
        const paginationContainer = widget.find('.lmb-pagination-container');
        const filtersForm = widget.find('#lmb-payments-filters-form');

        const debounce = (func, delay) => {
            let timeout;
            return function(...args) {
                const context = this;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), delay);
            };
        };

        const fetchPayments = (page = 1) => {
            tableBody.html('<tr><td colspan="7" style="text-align:center;"><i class="fas fa-spinner fa-spin"></i> Loading payments...</td></tr>');
            
            const formData = filtersForm.serialize();

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_fetch_payments',
                nonce: lmb_ajax_params.nonce,
                paged: page,
                filters: formData
            }).done(function(response) {
                if (response.success) {
                    tableBody.html(response.data.html);
                    paginationContainer.html(response.data.pagination);
                } else {
                    tableBody.html('<tr><td colspan="7" style="text-align:center;">' + (response.data.message || 'Impossible de charger les paiements.') + '</td></tr>');
                    paginationContainer.empty();
                }
            }).fail(function() {
                tableBody.html('<tr><td colspan="7" style="text-align:center;">Une erreur s\'est produite. Veuillez réessayer.</td></tr>');
                paginationContainer.empty();
            });
        };

        const debouncedFetch = debounce(() => fetchPayments(1), 500);
        filtersForm.on('keyup change', 'input, select', debouncedFetch);
        
        filtersForm.on('reset', function() {
            setTimeout(() => fetchPayments(1), 1);
        });

        paginationContainer.on('click', 'a.page-numbers', function(e) {
            e.preventDefault();
            const urlString = $(this).attr('href');
            if (!urlString) return;
            const url = new URL(urlString, window.location.origin);
            const page = url.searchParams.get('paged') || 1;
            fetchPayments(page);
        });
        
        // Handle Approve/Deny actions
        tableBody.on('click', '.lmb-payment-action-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const paymentId = button.data('id');
            const paymentAction = button.data('action');
            let reason = '';

            if(paymentAction === 'deny') {
                reason = prompt('Veuillez fournir une raison pour refuser cette preuve de paiement:');
                if (reason === null) return; // User cancelled
            } else {
                if (!confirm('Êtes-vous sûr de vouloir approuver ce paiement?')) return;
            }

            button.closest('.lmb-actions-cell').html('<i class="fas fa-spinner fa-spin"></i>');

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_payment_action', // Reusing existing handler
                nonce: lmb_ajax_params.nonce,
                payment_id: paymentId,
                payment_action: paymentAction,
                reason: reason
            }).done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    fetchPayments(1); // Refresh the table
                } else {
                    showLMBModal('error', response.data.message || 'Une erreur s\'est produite.');
                    fetchPayments(1); // Refresh to restore buttons
                }
            }).fail(function() {
                showLMBModal('error', 'Une erreur serveur s\'est produite.');
                fetchPayments(1); // Refresh to restore buttons
            });
        });

        fetchPayments();
    });
});