// FILE: assets/js/lmb-maintenance.js

jQuery(document).ready(function($) {
    const $button = $('#lmb-manual-cleanup-btn');
    const $status = $('#lmb-cleanup-status');

    $button.on('click', function() {
        if (!confirm('Êtes-vous sûr de vouloir exécuter le nettoyage des fichiers immédiatement ? Cela peut prendre quelques secondes.')) {
            return;
        }

        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Nettoyage en cours...');
        $status.text('Traitement en cours, veuillez patienter...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_trigger_manual_cleanup',
            nonce: lmb_ajax_params.nonce // Use the global nonce
        }).done(function(response) {
            if (response.success) {
                $status.css('color', 'green').text(response.data.message);
            } else {
                $status.css('color', 'red').text(response.data.message || 'Erreur inconnue lors de l\'exécution.');
            }
        }).fail(function() {
            $status.css('color', 'red').text('Erreur de communication serveur. Vérifiez les logs PHP.');
        }).always(function() {
            $button.prop('disabled', false).html('<i class="fas fa-broom"></i> Exécuter le Nettoyage Immédiat');
        });
    });
});