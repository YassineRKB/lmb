// FILE: assets/js/lmb-admin-subscribe-user.js
jQuery(document).ready(function($) {
    const widget = $('.lmb-admin-subscribe-user-widget');
    if (!widget.length) return;

    const form = $('#lmb-admin-subscribe-form');

    form.on('submit', function(e) {
        e.preventDefault();

        const submitBtn = form.find('button[type="submit"]');
        const userId = $('#lmb_user_id').val();
        const packageId = $('#lmb-package-select').val();

        if (!packageId) {
            showLMBModal('error', 'Veuillez sélectionner un package.');
            return;
        }

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Souscription...');

        const data = {
            action: 'lmb_admin_subscribe_user_to_package',
            nonce: lmb_ajax_params.nonce,
            user_id: userId,
            package_id: packageId,
        };

        $.post(lmb_ajax_params.ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    form[0].reset(); // Clear the form

                    // Trigger a custom event that other widgets on the page can listen for.
                    // This will allow us to refresh the balance widget without a full page reload.
                    $(document).trigger('lmb:balanceUpdated', { user_id: userId });

                } else {
                    showLMBModal('error', response.data.message || 'Une erreur est survenue.');
                }
            })
            .fail(function() {
                showLMBModal('error', 'Une erreur de communication avec le serveur est survenue.');
            })
            .always(function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-check"></i> Souscrire l\'Utilisateur');
            });
    });
});