// FILE: assets/js/lmb-admin-editor.js
jQuery(document).ready(function($) {
    const feedbackDiv = $('#lmb-generator-feedback');
    const postId = $('#post_ID').val();

    if (!postId) return; // Exit if not on a post edit screen

    // Regenerate Text Button
    $('body').on('click', '#lmb-regenerate-text-btn', function() {
        const button = $(this);

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Regenerating...');
        feedbackDiv.html('');

        $.post(ajaxurl, {
            action: 'lmb_regenerate_ad_text',
            nonce: lmb_ajax_params.nonce,
            post_id: postId
        }).done(function(response) {
            if (response.success) {
                // Update the main WordPress editor (works for both classic and Gutenberg)
                if (wp.data && wp.data.dispatch('core/editor')) {
                    wp.data.dispatch('core/editor').editPost({ content: response.data.new_content });
                } else if (typeof tinyMCE !== 'undefined' && tinyMCE.get('content')) {
                    tinyMCE.get('content').setContent(response.data.new_content);
                }
                feedbackDiv.html('<p style="color:green;"><strong>Success:</strong> Ad content has been updated in the editor.</p>');
            } else {
                feedbackDiv.html('<p style="color:red;"><strong>Error:</strong> ' + response.data.message + '</p>');
            }
        }).fail(function() {
            feedbackDiv.html('<p style="color:red;"><strong>Error:</strong> A server error occurred.</p>');
        }).always(function() {
            button.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Regenerate Text from Template');
        });
    });

    // Generate PDF Button
    $('body').on('click', '#lmb-generate-pdf-btn', function() {
        const button = $(this);

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
        feedbackDiv.html('');

        $.post(ajaxurl, {
            action: 'lmb_admin_generate_pdf',
            nonce: lmb_ajax_params.nonce,
            post_id: postId
        }).done(function(response) {
            if (response.success) {
                // Display a link to the new PDF
                feedbackDiv.html('<p style="color:green;"><strong>Success:</strong> PDF generated. <a href="' + response.data.pdf_url + '" target="_blank">Open PDF</a></p>');
                
                // Also update the 'ad_pdf_url' custom field input if it exists
                const pdfUrlField = $('input[name="ad_pdf_url"]');
                if(pdfUrlField.length) {
                    pdfUrlField.val(response.data.pdf_url);
                }

            } else {
                feedbackDiv.html('<p style="color:red;"><strong>Error:</strong> ' + response.data.message + '</p>');
            }
        }).fail(function() {
            feedbackDiv.html('<p style="color:red;"><strong>Error:</strong> A server error occurred.</p>');
        }).always(function() {
            button.prop('disabled', false).html('<i class="fas fa-file-pdf"></i> Generate PDF');
        });
    });
});