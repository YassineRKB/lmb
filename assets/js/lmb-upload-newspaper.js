jQuery(document).ready(function($) {
    $('#lmb-upload-newspaper-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const formData = new FormData(this);
        formData.append('action', 'lmb_upload_newspaper');
        formData.append('nonce', lmb_ajax_params.nonce);

        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

        $.ajax({
            url: lmb_ajax_params.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
        }).done(function(response) {
            if (response.success) {
                alert('Success: ' + response.data.message);
                form[0].reset();
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('An unexpected server error occurred.');
        }).always(function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-upload"></i> Upload Newspaper');
        });
    });
});