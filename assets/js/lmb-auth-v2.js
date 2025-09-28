// FILE: assets/js/lmb-auth-v2.js
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
            const professionalFields = $('#lmb-signup-professional-fields');
            const regularFields = $('#lmb-signup-regular-fields');
            
            signupForm.find('.lmb-signup-toggle-btn.active').removeClass('active');
            btn.addClass('active');
            const type = btn.data('type');
            signupForm.find('input[name="signup_type"]').val(type);

            if (type === 'regular') {
                regularFields.show();
                professionalFields.hide();
                professionalFields.find('input').prop('required', false); // Remove required
                regularFields.find('input').prop('required', true);   // Add required
            } else {
                regularFields.hide();
                professionalFields.show();
                professionalFields.find('input').prop('required', true);    // Add required
                regularFields.find('input').prop('required', false);  // Remove required
            }
        });
        
        // --- AJAX for Login Form ---
        loginForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            loginResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Connexion en cours...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_login_v2',
                nonce: lmb_ajax_params.nonce,
                username: $('#login-email').val(),
                password: $('#login-password').val(),
            }).done(function(response) {
                if (response.success) {
                    loginResponse.addClass('success').text('Connexion réussie ! Redirection...').show();
                    window.location.href = response.data.redirect_url;
                } else {
                    loginResponse.addClass('error').text(response.data.message || 'Une erreur inconnue s\'est produite.').show();
                }
            }).fail(function() {
                loginResponse.addClass('error').text('Demande échouée. Veuillez attendre l\'approbation de l\'administrateur.').show();
            }).always(function() {
                submitBtn.html('Se connecter').prop('disabled', false);
            });
        });

        // --- AJAX for Signup Form ---
        signupForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            signupResponse.removeClass('error success').hide();
            
            // Phone validation
            const phoneInput = signupForm.find('input[type="tel"]:visible');
            const phoneRegex = /^[0-9]{10}$/;
            if (!phoneRegex.test(phoneInput.val())) {
                signupResponse.addClass('error').text('Le numéro de téléphone doit contenir 10 chiffres.').show();
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Création du compte...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_signup_v2',
                nonce: lmb_ajax_params.nonce,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    signupResponse.addClass('success').text('Inscription réussie ! Veuillez attendre l\'approbation de l\'administrateur.').show();
                    signupForm[0].reset();
                } else {
                    signupResponse.addClass('error').text(response.data.message || 'Une erreur inconnue s\'est produite.').show();
                }
            }).fail(function() {
                 signupResponse.addClass('error').text('Demande échouée. Veuillez vérifier votre connexion.').show();
            }).always(function() {
                submitBtn.html('Créer un Compte').prop('disabled', false);
            });
        });
    });
});