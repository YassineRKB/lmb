jQuery(document).ready(function($) {
    // Quick status change for ads
    $('.lmb-quick-approve, .lmb-quick-deny').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const postId = button.data('post-id');
        const newStatus = button.hasClass('lmb-quick-approve') ? 'published' : 'denied';
        
        button.prop('disabled', true);
        
        $.ajax({
            url: lmbAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'lmb_quick_status_change',
                post_id: postId,
                new_status: newStatus,
                nonce: lmbAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert(lmbAdmin.strings.error_occurred);
                button.prop('disabled', false);
            }
        });
    });
});