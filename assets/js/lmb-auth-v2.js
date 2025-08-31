jQuery(document).ready(function($) {
    $('.lmb-auth-v2-widget').each(function() {
        const widget = $(this);
        const loginForm = widget.find('#lmb-login-form');
        const signupForm = widget.find('#lmb-signup-form');
        const loginResponse = loginForm.find('.lmb-form-response');
        const signupResponse = signupForm.find('.lmb-form-response');

        // Main Tabs
        widget.on('click', '.lmb-auth-tab-btn', function() {
            const btn = $(this);
            widget.find('.lmb-auth-tab-btn.active').removeClass('active');
            btn.addClass('active');
            widget.find('.lmb-auth-form.active').removeClass('active');
            widget.find('#lmb-' + btn.data('form') + '-form').addClass('active');
            loginResponse.hide().text('');
            signupResponse.hide().text('');
        });

        // Signup Type Toggle
        signupForm.on('click', '.lmb-signup-toggle-btn', function() {
            const btn = $(this);
            signupForm.find('.lmb-signup-toggle-btn.active').removeClass('active');
            btn.addClass('active');
            const type = btn.data('type');
            signupForm.find('input[name="signup_type"]').val(type);

            if (type === 'regular') {
                $('#lmb-signup-regular-fields').show();
                $('#lmb-signup-professional-fields').hide();
            } else {
                $('#lmb-signup-regular-fields').hide();
                $('#lmb-signup-professional-fields').show();
            }
        });
        
        // --- AJAX for Login Form ---
        loginForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            loginResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Logging In...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_login_v2',
                nonce: lmb_ajax_params.nonce,
                username: $('#login-email').val(),
                password: $('#login-password').val(),
            }).done(function(response) {
                if (response.success) {
                    loginResponse.addClass('success').text('Login successful! Redirecting...').show();
                    window.location.href = response.data.redirect_url;
                } else {
                    loginResponse.addClass('error').text(response.data.message || 'An unknown error occurred.').show();
                }
            }).fail(function() {
                loginResponse.addClass('error').text('Request failed. Please check your connection.').show();
            }).always(function() {
                submitBtn.html('Login').prop('disabled', false);
            });
        });

        // --- AJAX for Signup Form ---
        signupForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            signupResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating Account...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_signup_v2',
                nonce: lmb_ajax_params.nonce,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    signupResponse.addClass('success').text('Registration successful! Please wait for admin approval.').show();
                    signupForm[0].reset();
                } else {
                    signupResponse.addClass('error').text(response.data.message || 'An unknown error occurred.').show();
                }
            }).fail(function() {
                 signupResponse.addClass('error').text('Request failed. Please check your connection.').show();
            }).always(function() {
                submitBtn.html('Create Account').prop('disabled', false);
            });
        });
    });
});