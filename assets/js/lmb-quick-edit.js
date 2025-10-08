jQuery(document).ready(function ($) {
    // Use event delegation for dynamically loaded content
    $('#the-list').on('click', '.lmb-save-ad-date', function () {
        var $button = $(this);
        var postId = $button.data('postid');
        var nonce = $button.data('nonce');
        var newDate = $('#lmb-ad-date-' + postId).val();
        var $spinner = $button.siblings('.spinner');

        if (!newDate) {
            alert('Please select a date.');
            return;
        }

        $spinner.addClass('is-active');
        $button.prop('disabled', true);

        $.ajax({
            url: lmb_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'lmb_update_ad_date',
                post_id: postId,
                new_date: newDate,
                nonce: nonce
            },
            success: function (response) {
                if (response.success) {
                    // Provide visual feedback
                    $('#lmb-ad-date-' + postId).css('border-color', 'green');
                    // Update the original date column text
                    var newDisplayDate = response.data.new_date_formatted;
                    $('#post-' + postId + ' .date column-date').text(newDisplayDate);
                } else {
                    $('#lmb-ad-date-' + postId).css('border-color', 'red');
                    alert('Error: ' + response.data.message);
                }
            },
            error: function () {
                $('#lmb-ad-date-' + postId).css('border-color', 'red');
                alert('An unknown error occurred.');
            },
            complete: function () {
                $spinner.removeClass('is-active');
                $button.prop('disabled', false);
            }
        });
    });
});