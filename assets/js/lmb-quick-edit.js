// FILE: assets/js/lmb-quick-edit.js

(function($) {
    $(document).ready(function() {
        if (typeof inlineEditPost === 'undefined') {
            return;
        }

        // Store a copy of the original WordPress inlineEditPost.edit function
        var wp_inline_edit = inlineEditPost.edit;

        /**
         * Custom quickEdit function to override the default WordPress behavior.
         * This function is responsible for populating our custom fields.
         * @param {int|string} id - The Post ID being edited.
         */
        inlineEditPost.edit = function(id) {
            // Call the original WordPress function first
            wp_inline_edit.apply(this, arguments);

            // Get the Post ID
            var post_id = 0;
            if (typeof(id) === 'object') {
                post_id = parseInt(this.getId(id));
            } else {
                post_id = parseInt(id);
            }

            if (post_id > 0) {
                // Find the post row element
                var $post_row = $('#post-' + post_id);
                // Find the quick edit row element
                var $quick_edit_row = $('#edit-' + post_id);
                
                // Get the current date value from the custom column link
                var $date_link = $post_row.find('.column-lmb_date .lmb-date-quick-edit');
                var current_date = $date_link.attr('data-post-date'); // YYYY-MM-DDTHH:MM:SS format

                // Populate the new datetime-local input field
                if (current_date) {
                    // The datetime-local input usually expects YYYY-MM-DDTHH:MM (no seconds)
                    // We must strip the seconds for compatibility
                    var formatted_date_time = current_date.substring(0, 16); 
                    
                    $quick_edit_row.find('.lmb_post_date_input').val(formatted_date_time);
                }
            }
        };

        /**
         * Intercepts the Quick Edit form submission to inject our custom AJAX action.
         */
        $('#the-list').on('click', '.save', function(e) {
            var $this = $(this);
            var $quick_edit = $this.closest('#edit-box');
            var post_id = $quick_edit.attr('id').replace('edit-', '');
            var post_type = $quick_edit.find('input[name="_post_type"]').val();
            
            // Only process our CPT
            if (post_type !== 'lmb_legal_ad') {
                return;
            }
            
            // Check if our custom field exists in the quick edit form
            var $date_input = $quick_edit.find('.lmb_post_date_input');
            if ($date_input.length === 0) {
                return; // Not our quick edit form, let WP handle it
            }

            // Prevent default save action so we can handle the date update via AJAX
            e.preventDefault();
            
            var new_date = $date_input.val();
            var $notice = $quick_edit.find('.lmb-date-notice');

            // 1. Basic validation
            if (!new_date) {
                $notice.text(lmb_quick_edit_vars.date_error_message).show();
                return;
            }

            // 2. Prepare data for AJAX
            var data = {
                action: 'lmb_update_ad_date', // This must match the PHP AJAX hook
                post_id: post_id,
                new_date: new_date,
                security: lmb_quick_edit_vars.nonce
            };

            // 3. Perform AJAX request
            $this.prop('disabled', true).text('Sauvegarde...');
            $notice.slideUp(200);

            $.post(lmb_quick_edit_vars.ajax_url, data, function(response) {
                $this.prop('disabled', false).text('Mettre à Jour');
                
                if (response.success) {
                    // Update the date displayed in the list table column
                    var $post_row = $('#post-' + post_id);
                    var $date_link = $post_row.find('.column-lmb_date .lmb-date-quick-edit');
                    
                    // The new display date is returned by the server
                    $date_link.text(response.data.new_display_date);
                    // Update the data attribute for subsequent quick edits
                    $date_link.attr('data-post-date', response.data.new_date_for_input);
                    
                    // Close the quick edit form
                    $quick_edit.find('.cancel').trigger('click');

                } else {
                    // Show error message
                    $notice.text(response.data || lmb_quick_edit_vars.error_message).slideDown(200);
                }
            }, 'json').fail(function() {
                $this.prop('disabled', false).text('Mettre à Jour');
                $notice.text(lmb_quick_edit_vars.error_message).slideDown(200);
            });
        });

    });
})(jQuery);