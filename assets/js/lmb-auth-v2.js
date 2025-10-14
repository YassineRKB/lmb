// FILE: assets/js/lmb-auth-v2.js (ADDED: Password Confirmation)
jQuery(document).ready(function($) {
    $('.lmb-auth-v2-widget').each(function() {
        const widget = $(this);
        const loginForm = widget.find('#lmb-login-form');
        const signupForm = widget.find('#lmb-signup-form');
        const loginResponse = loginForm.find('.lmb-form-response');
        const signupResponse = signupForm.find('.lmb-form-response');
        
        // Containers for type-specific fields
        const professionalFields = $('#lmb-signup-professional-fields');
        const regularFields = $('#lmb-signup-regular-fields');
        const commonFields = signupForm.find('.lmb-common-fields');
        const submitBtn = signupForm.find('button[type="submit"]');

        // --- PASSWORD VISIBILITY TOGGLE ---
        widget.on('click', '.toggle-password', function() {
            const icon = $(this);
            const input = icon.prev('input');
            const type = input.attr('type') === 'password' ? 'text' : 'password';
            input.attr('type', type);

            // Toggle eye icon
            icon.toggleClass('fa-eye fa-eye-slash');
        });

        // --- INITIAL SETUP (Disable All Type-Specific Fields) ---
        // We ensure all type-specific fields start as disabled to prevent serialization conflict.
        regularFields.find(':input').prop('disabled', true).prop('required', false);
        professionalFields.find(':input').prop('disabled', true).prop('required', false);
        
        // Function to update the active step state
        const updateSignupStep = (type) => {
            // Step 1: Handle required and visibility based on type
            if (type === 'regular') {
                regularFields.show().find(':input').prop('disabled', false).prop('required', true);
                professionalFields.hide().find(':input').prop('disabled', true).prop('required', false);
            } else { // professional
                regularFields.hide().find(':input').prop('disabled', true).prop('required', false);
                professionalFields.show().find(':input').prop('disabled', false).prop('required', true);
            }
            
            // Update UI toggle buttons
            signupForm.find('.lmb-signup-toggle-btn').removeClass('active');
            signupForm.find('.lmb-signup-toggle-btn[data-type="' + type + '"]').addClass('active');
            signupForm.find('input[name="signup_type"]').val(type);
        };
        
        // --- Event Handlers ---

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
            const type = btn.data('type');
            updateSignupStep(type);
        });

        // Initialize with default state
        updateSignupStep('regular');
        
        // --- AJAX for Login Form (Unchanged) ---
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

        // --- AJAX for Signup Form (Password Confirmation & Phone Validation) ---
        signupForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            signupResponse.removeClass('error success').hide();
            
            // --- NEW: Password confirmation check ---
            const password = $('#signup-password').val();
            const passwordConfirm = $('#signup-password-confirm').val();

            if (password !== passwordConfirm) {
                signupResponse.addClass('error').text('Les mots de passe ne correspondent pas.').show();
                return; // Stop the submission
            }
            // --- End of new check ---

            // Phone validation FIX
            const phoneInput = signupForm.find('input[type="tel"]:visible');
            const rawPhoneNumber = phoneInput.val() || '';
            
            const cleanedPhoneNumber = rawPhoneNumber.replace(/\D/g, '').trim(); 
            const phoneRegex = /^[0-9]{10}$/; 
            
            if (!phoneRegex.test(cleanedPhoneNumber)) {
                signupResponse.addClass('error').text('Le numéro de téléphone doit contenir 10 chiffres (sans espaces ni caractères spéciaux).').show();
                return;
            }

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Création du compte...').prop('disabled', true);
            
            const formData = $(this).serialize();
            
            signupForm.find(':input').prop('disabled', false); 

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_signup_v2',
                nonce: lmb_ajax_params.nonce,
                form_data: formData,
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
                updateSignupStep('regular'); 
            });
        });
    });
});