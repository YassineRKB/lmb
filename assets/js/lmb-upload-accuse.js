jQuery(document).ready(function($) {
    $('#lmb-upload-accuse-form').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $messageContainer = $form.closest('.lmb-upload-accuse-widget').find('.lmb-upload-messages');
        var formData = new FormData($form[0]);

        $messageContainer.html('');
        $form.find('button[type="submit"]').prop('disabled', true).text('Uploading...');

        $.ajax({
            url: lmb_accuse_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $messageContainer.html('<div class="lmb-success"><h3>' + response.data.message + '</h3></div>');
                    $form[0].reset();
                } else {
                    $messageContainer.html('<div class="lmb-error"><h3>Error</h3><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $messageContainer.html('<div class="lmb-error"><h3>Error</h3><p>An unexpected error occurred. Please try again.</p></div>');
            },
            complete: function() {
                $form.find('button[type="submit"]').prop('disabled', false).text('Upload Accuse');
            }
        });
    });
});