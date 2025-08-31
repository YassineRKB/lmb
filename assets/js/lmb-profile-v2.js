jQuery(document).ready(function($) {
    $('.lmb-profile-v2-widget').each(function() {
        const widget = $(this);
        const userId = widget.data('user-id');
        const profileForm = widget.find('#lmb-profile-details-form');
        const passwordForm = widget.find('#lmb-password-change-form');
        const profileResponse = widget.find('#profile-response');
        const passwordResponse = widget.find('#password-response');

        // Handle Profile Details Update
        profileForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            profileResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_update_profile_v2', // We will create this action next
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    profileResponse.addClass('success').text('Profile updated successfully!').show();
                } else {
                    profileResponse.addClass('error').text(response.data.message || 'An unknown error occurred.').show();
                }
            }).fail(function() {
                profileResponse.addClass('error').text('Request failed. Please check your connection.').show();
            }).always(function() {
                submitBtn.html('Save Changes').prop('disabled', false);
            });
        });

        // Handle Password Change
        passwordForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            passwordResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_update_password_v2', // We will create this action next
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    passwordResponse.addClass('success').text('Password updated successfully!').show();
                    passwordForm[0].reset(); // Clear the form on success
                } else {
                    passwordResponse.addClass('error').text(response.data.message || 'An unknown error occurred.').show();
                }
            }).fail(function() {
                passwordResponse.addClass('error').text('Request failed. Please check your connection.').show();
            }).always(function() {
                submitBtn.html('Update Password').prop('disabled', false);
            });
        });
    });
});