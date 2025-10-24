jQuery(document).ready(function($) {
    const tableBody = $('#lmb-ad-types-tbody');
    const feedbackDiv = $('#lmb-ad-type-migration-feedback');

    function loadAdTypes() {
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_ad_types',
            nonce: lmb_ajax_params.nonce
        }, function(response) {
            if (response.success) {
                tableBody.empty();
                response.data.forEach(function(type) {
                    tableBody.append(
                        // Store the current name in a data attribute for comparison during save
                        `<tr data-id="${type.id}" data-name="${type.name}">
                            <td>${type.id}</td>
                            <td><span class="lmb-ad-type-name">${type.name}</span><input type="text" class="lmb-edit-ad-type-name" value="${type.name}" style="display:none;"></td>
                            <td>
                                <button class="button lmb-edit-btn">Modifier</button>
                                <button class="button lmb-save-btn" style="display:none;">Enregistrer</button>
                                <button class="button button-danger lmb-delete-btn">Supprimer</button>
                            </td>
                        </tr>`
                    );
                });
            }
        });
    }

    $('#lmb-add-ad-type-btn').on('click', function() {
        const newAdTypeName = $('#lmb-new-ad-type-name').val();
        if (newAdTypeName) {
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_add_ad_type',
                name: newAdTypeName,
                nonce: lmb_ajax_params.nonce
            }, function(response) {
                if (response.success) {
                    loadAdTypes();
                    $('#lmb-new-ad-type-name').val('');
                } else {
                    alert(response.data.message);
                }
            });
        }
    });

    tableBody.on('click', '.lmb-delete-btn', function() {
        const row = $(this).closest('tr');
        const adTypeName = row.data('name');
        if (confirm(`Êtes-vous sûr de vouloir supprimer le type d'annonce "${adTypeName}" ? Cela ne supprimera PAS les annonces qui l'utilisent.`)) {
            const adTypeId = row.data('id');
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_delete_ad_type',
                id: adTypeId,
                nonce: lmb_ajax_params.nonce
            }, function(response) {
                if (response.success) {
                    loadAdTypes();
                }
            });
        }
    });

    tableBody.on('click', '.lmb-edit-btn', function() {
        const row = $(this).closest('tr');
        // Store the current name as 'old_name' for the migration check later
        row.data('old_name', row.data('name'));
        
        row.find('.lmb-ad-type-name').hide();
        row.find('.lmb-edit-ad-type-name').show().focus();
        row.find('.lmb-edit-btn').hide();
        row.find('.lmb-save-btn').show();
    });

    tableBody.on('click', '.lmb-save-btn', function() {
        const row = $(this).closest('tr');
        const adTypeId = row.data('id');
        const newName = row.find('.lmb-edit-ad-type-name').val();
        const oldName = row.data('old_name'); // Retrieve the old name for migration

        if (newName === oldName) {
            // No change, just revert UI
             row.find('.lmb-edit-ad-type-name').hide();
             row.find('.lmb-ad-type-name').show().text(newName);
             row.find('.lmb-save-btn').hide();
             row.find('.lmb-edit-btn').show();
             return;
        }

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_update_ad_type',
            id: adTypeId,
            name: newName,
            old_name: oldName, // CRITICAL: Send old name for migration logic
            nonce: lmb_ajax_params.nonce
        }, function(response) {
            if (response.success) {
                loadAdTypes();
                // Handle success and show migration feedback
                if (response.data.migration_count > 0) {
                    feedbackDiv.removeClass('notice-error').addClass('notice-success').text(`${response.data.migration_count} annonces ont été migrées vers le nouveau type "${newName}".`).show();
                } else {
                     feedbackDiv.removeClass('notice-error notice-success').hide();
                }
            } else {
                alert(response.data.message);
                feedbackDiv.removeClass('notice-success').addClass('notice-error').text(`Erreur lors de la mise à jour: ${response.data.message}`).show();
            }
        });
    });
    
    // --- NEW REFRESH BUTTON LOGIC ---
    $('#lmb-refresh-ad-types-btn').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        $button.prop('disabled', true).find('i').removeClass('fa-sync-alt').addClass('fa-spinner fa-spin');
        feedbackDiv.removeClass('notice-success notice-error').addClass('notice-info').text('Actualisation en cours...').show();
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_refresh_ad_types',
            nonce: lmb_ajax_params.nonce,
        }, function(response) {
            if (response.success) {
                feedbackDiv.removeClass('notice-error notice-info').addClass('notice-success').text(response.data.message);
                loadAdTypes(); // Reload the list with the newly reconstructed data
            } else {
                feedbackDiv.removeClass('notice-success notice-info').addClass('notice-error').text(`Erreur d'actualisation: ${response.data.message}`);
            }
        }).fail(function() {
             feedbackDiv.removeClass('notice-success notice-info').addClass('notice-error').text('Erreur de communication avec le serveur.');
        }).always(function() {
             $button.prop('disabled', false).find('i').removeClass('fa-spinner fa-spin').addClass('fa-sync-alt');
        });
    });
    // --- END NEW REFRESH BUTTON LOGIC ---

    loadAdTypes();
});