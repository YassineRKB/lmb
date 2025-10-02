// FILE: assets/js/lmb-profile-v2.js
jQuery(document).ready(function($) {
    $('.lmb-profile-v2-widget').each(function() {
        const widget = $(this);
        const userId = widget.data('user-id');
        
        // Form & Response Div Selectors
        const mainForm = widget.find('#lmb-profile-details-form');
        const passwordForm = widget.find('#lmb-password-change-form');
        const balanceForm = widget.find('#lmb-balance-manipulation-form'); // New form
        
        const profileResponse = widget.find('#profile-response');
        const passwordResponse = widget.find('#password-response');
        const balanceResponse = widget.find('#balance-response'); // New response div

        // --- Handle Client Type Toggle for Admins ---
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
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Enregistrement...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_update_profile_v2',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    profileResponse.addClass('success').text('Profil mis à jour avec succès ! Actualisation...').show();
                    // Refresh after a short delay to see the message
                    setTimeout(() => location.reload(), 1500);
                } else {
                    profileResponse.addClass('error').text(response.data.message || 'Une erreur inconnue s\'est produite.').show();
                }
            }).fail(function() {
                profileResponse.addClass('error').text('Demande échouée. Veuillez vérifier votre connexion.').show();
            }).always(function() {
                submitBtn.html('Enregistrer les Modifications').prop('disabled', false);
            });
        });

        // Handle Password Change
        passwordForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            passwordResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Mise à jour...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_update_password_v2',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    passwordResponse.addClass('success').text('Mot de passe mis à jour avec succès !').show();
                    passwordForm[0].reset();
                } else {
                    passwordResponse.addClass('error').text(response.data.message || 'Une erreur inconnue s\'est produite.').show();
                }
            }).fail(function() {
                passwordResponse.addClass('error').text('Demande échouée. Veuillez vérifier votre connexion.').show();
            }).always(function() {
                submitBtn.html('Mettre à Jour le Mot de Passe').prop('disabled', false);
            });
        });
        
        // --- NEW: Handle Balance Manipulation ---
        balanceForm.on('submit', function(e) {
            e.preventDefault();
            const submitBtn = $(this).find('button[type="submit"]');
            balanceResponse.removeClass('error success').hide();
            submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Application...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_manipulate_balance',
                nonce: lmb_ajax_params.nonce,
                user_id: userId,
                form_data: $(this).serialize(),
            }).done(function(response) {
                if (response.success) {
                    balanceResponse.addClass('success').text(response.data.message || 'Solde mis à jour. Actualisation...').show();
                    // Refresh after a short delay to see the message and updated stats
                    setTimeout(() => location.reload(), 1500);
                } else {
                    balanceResponse.addClass('error').text(response.data.message || 'Une erreur inconnue s\'est produite.').show();
                }
            }).fail(function() {
                balanceResponse.addClass('error').text('Demande échouée. Veuillez vérifier votre connexion.').show();
            }).always(function() {
                submitBtn.html('Appliquer la Modification').prop('disabled', false);
            });
        });
    });
});