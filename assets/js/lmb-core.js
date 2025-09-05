/**
 * LMB Core Frontend Global JavaScript
 */
(function($) {
    'use strict';

    // Global utility for showing a feedback modal
    window.showLMBModal = function(status, message) {
        $('#lmb-feedback-modal').remove();
        const icon = status === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';
        const modalHTML = `
            <div id="lmb-feedback-modal" class="lmb-modal-overlay">
                <div class="lmb-modal-content ${status}">
                    <div class="lmb-modal-icon">${icon}</div>
                    <p>${message}</p>
                    <button class="lmb-modal-close">&times;</button>
                </div>
            </div>`;
        $('body').append(modalHTML);
        const modal = $('#lmb-feedback-modal');
        modal.fadeIn(200);
        const timer = setTimeout(() => modal.fadeOut(400, () => modal.remove()), 5000);
        modal.on('click', '.lmb-modal-close, .lmb-modal-overlay', function() {
            clearTimeout(timer);
            modal.fadeOut(400, () => modal.remove());
        });
    }

    $(document).ready(function() {
        // --- MODIFICATION START ---
        // Changed selector from .lmb-get-invoice-btn to .lmb-subscribe-btn
        $('body').on('click', '.lmb-subscribe-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const pkgId = button.data('pkg-id');
            const originalText = button.text();

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                // This action now creates a pending payment, not a PDF
                action: 'lmb_generate_package_invoice',
                nonce: lmb_ajax_params.nonce,
                pkg_id: pkgId
            }).done(function(response) {
                // Updated success logic
                if (response.success) {
                    showLMBModal('success', 'Abonnement réussi ! Vous pouvez maintenant télécharger votre preuve de paiement.');
                    
                } else {
                    showLMBModal('error', response.data ? response.data.message : 'Impossible de s\'abonner au package.');
                }
            }).fail(function() {
                showLMBModal('error', 'Une erreur serveur inconnue s\'est produite.');
            }).always(function() {
                button.text(originalText).prop('disabled', false);
            });
        });
        // --- MODIFICATION END ---
    });

})(jQuery);