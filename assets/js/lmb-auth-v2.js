// FILE: assets/js/lmb-auth-v2.js (FIXED: Phone Validation)
jQuery(document).ready(function($) {
    $('.lmb-auth-v2-widget').each(function() {
        const widget = $(this);
        const loginForm = widget.find('#lmb-login-form');
        const signupForm = widget.find('#lmb-signup-form');
        const loginResponse = loginForm.find('.lmb-form-response');
        const signupResponse = signupForm.find('.lmb-form-response');
        
        // Containers
        const professionalFields = $('#lmb-signup-professional-fields');
        const regularFields = $('#lmb-signup-regular-fields');
        const commonFields = signupForm.find('.lmb-common-fields');
        const submitBtn = signupForm.find('button[type="submit"]');

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
            // Ensure common fields remain enabled/required
            // Note: Common fields (email/password) are assumed to not have a shared container class here, 
            // but the inputs are still checked and set to enabled if they are not part of the type-specific containers.
            
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

        // --- AJAX for Signup Form (Phone Validation FIX) ---
        signupForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            signupResponse.removeClass('error success').hide();
            
            // Phone validation FIX
            const phoneInput = signupForm.find('input[type="tel"]:visible');
            const rawPhoneNumber = phoneInput.val() || '';
            
            // CLEANING: Remove all non-digit characters and trim whitespace
            const cleanedPhoneNumber = rawPhoneNumber.replace(/\D/g, '').trim(); 
            const phoneRegex = /^[0-9]{10}$/; 
            
            // Test the cleaned value
            if (!phoneRegex.test(cleanedPhoneNumber)) {
                signupResponse.addClass('error').text('Le numéro de téléphone doit contenir 10 chiffres (sans espaces ni caractères spéciaux).').show();
                return;
            }
            // End Phone validation FIX

            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Création du compte...').prop('disabled', true);
            
            // FIX: The current active field set is guaranteed to be enabled. 
            // Since the *inactive* field set is disabled by updateSignupStep, 
            // the serialization is now clean and includes only the intended fields.
            const formData = $(this).serialize();
            
            // Re-enable ALL fields immediately after serialization (crucial for user experience)
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
                // Re-initialize state to 'regular' after submission completion
                updateSignupStep('regular'); 
            });
        });
    });
});