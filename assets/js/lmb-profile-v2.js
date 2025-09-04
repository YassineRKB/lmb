// FILE: assets/js/lmb-profile-v2.js
jQuery(document).ready(function($) {
    $('.lmb-profile-v2-widget').each(function() {
        const widget = $(this);
        const userId = widget.data('user-id');
        const mainForm = widget.find('#lmb-profile-details-form');
        const passwordForm = widget.find('#lmb-password-change-form');
        const profileResponse = widget.find('#profile-response');
        const passwordResponse = widget.find('#password-response');

        // --- NEW: Handle Client Type Toggle for Admins ---
        mainForm.on('change', 'select[name="lmb_client_type"]', function() {
            const type = $(this).val();
            const regularFields = widget.find('#lmb-profile-regular-fields');
            const professionalFields = widget.find('#lmb-profile-professional-fields');

            if (type === 'regular') {
                regularFields.show();
                professionalFields.hide();
            } else {
                regularFields.hide();
                professionalFields.show();
            }
        });

        // Handle Profile Details Update
        mainForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            profileResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Saving...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_update_profile_v2',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    profileResponse.addClass('success').text('Profile updated successfully! Refreshing...').show();
                    // Refresh after a short delay to see the message
                    setTimeout(() => location.reload(), 1500);
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
                action: 'lmb_update_password_v2',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    passwordResponse.addClass('success').text('Password updated successfully!').show();
                    passwordForm[0].reset();
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