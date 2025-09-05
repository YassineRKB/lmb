// FILE: assets/js/lmb-upload-newspaper.js
jQuery(document).ready(function($) {
    $('#lmb-upload-newspaper-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'lmb_upload_newspaper');
        formData.append('nonce', lmb_ajax_params.nonce);

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Téléchargement...');

        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                form[0].reset();
            } else {
                showLMBModal('error', response.data.message);
            }
        }).fail(function() {
            showLMBModal('error', 'Une erreur serveur inattendue s\'est produite.');
        }).always(function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-upload"></i> Télécharger Journal');
        });
    });
});