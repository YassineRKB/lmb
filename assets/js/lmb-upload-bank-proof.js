jQuery(document).ready(function($) {
    $('#lmb-upload-proof-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'lmb_upload_bank_proof');
        formData.append('nonce', lmb_ajax_params.nonce);

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            if (response.success) {
                alert('Success: Your proof has been submitted for review.');
                form[0].reset();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('An unexpected server error occurred.');
        }).always(function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Submit for Verification');
        });
    });
});