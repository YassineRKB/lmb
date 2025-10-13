jQuery(document).ready(function($) {
    const tableBody = $('#lmb-ad-types-tbody');

    function loadAdTypes() {
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_ad_types',
            nonce: lmb_ajax_params.nonce
        }, function(response) {
            if (response.success) {
                tableBody.empty();
                response.data.forEach(function(type) {
                    tableBody.append(
                        `<tr data-id="${type.id}">
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
        if (confirm('Êtes-vous sûr de vouloir supprimer ce type d\'annonce ?')) {
            const adTypeId = $(this).closest('tr').data('id');
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
        row.find('.lmb-ad-type-name').hide();
        row.find('.lmb-edit-ad-type-name').show().focus();
        row.find('.lmb-edit-btn').hide();
        row.find('.lmb-save-btn').show();
    });

    tableBody.on('click', '.lmb-save-btn', function() {
        const row = $(this).closest('tr');
        const adTypeId = row.data('id');
        const newName = row.find('.lmb-edit-ad-type-name').val();

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_update_ad_type',
            id: adTypeId,
            name: newName,
            nonce: lmb_ajax_params.nonce
        }, function(response) {
            if (response.success) {
                loadAdTypes();
            }
        });
    });

    loadAdTypes();
});