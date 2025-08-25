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
        // Handle "Get Invoice" button click
        $('body').on('click', '.lmb-get-invoice-btn', function(e) {
            e.preventDefault();
            const button = $(this);
            const pkgId = button.data('pkg-id');
            const originalText = button.text();

            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_generate_package_invoice',
                nonce: lmb_ajax_params.nonce,
                pkg_id: pkgId
            }).done(function(response) {
                if (response.success && response.data.pdf_url) {
                    window.open(response.data.pdf_url, '_blank');
                    showLMBModal('success', 'Your invoice has been generated successfully!');
                } else {
                    showLMBModal('error', response.data.message || 'Could not generate invoice.');
                }
            }).fail(function() {
                showLMBModal('error', 'An unknown server error occurred.');
            }).always(function() {
                button.text(originalText).prop('disabled', false);
            });
        });
    });

})(jQuery);