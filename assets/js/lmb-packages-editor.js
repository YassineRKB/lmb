jQuery(document).ready(function($) {
    const widget = $('.lmb-packages-editor-widget');
    if (!widget.length) {
        return;
    }

    let editingPackageId = null;

    // Save package
    widget.on('submit', '#lmb-package-form', function(e) {
        e.preventDefault();
        
        const formData = {
            action: 'lmb_save_package',
            nonce: lmbAdmin.nonce,
            package_id: $('#package-id').val(),
            name: $('#package-name').val(),
            price: $('#package-price').val(),
            points: $('#package-points').val(),
            cost_per_ad: $('#package-cost-per-ad').val(),
            description: $('#package-description').val()
        };

        $('#lmb-save-package-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.post(lmbAdmin.ajaxurl, formData, function(response) {
            if (response.success) {
                location.reload(); // Reload to show updated packages
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            $('#lmb-save-package-btn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Package');
        });
    });

    // Edit package
    widget.on('click', '.lmb-edit-package', function() {
        const packageId = $(this).data('package-id');
        const card = $(this).closest('.lmb-package-card');
        
        $('#package-id').val(packageId);
        $('#package-name').val(card.find('.lmb-package-title').text());
        $('#package-price').val(card.data('price'));
        $('#package-points').val(card.data('points'));
        $('#package-cost-per-ad').val(card.data('cost-per-ad'));
        $('#package-description').val(card.data('description'));
        
        $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> Update Package');
        $('#lmb-cancel-edit-btn').show();
        
        editingPackageId = packageId;
        
        $('html, body').animate({
            scrollTop: $('.lmb-add-package-section').offset().top - 20
        }, 500);
    });

    // Cancel edit
    widget.on('click', '#lmb-cancel-edit-btn', function() {
        $('#lmb-package-form')[0].reset();
        $('#package-id').val('');
        $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> Save Package');
        $(this).hide();
        editingPackageId = null;
    });

    // Delete package
    widget.on('click', '.lmb-delete-package', function() {
        if (!confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
            return;
        }

        const packageId = $(this).data('package-id');
        const button = $(this);
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(lmbAdmin.ajaxurl, {
            action: 'lmb_delete_package',
            nonce: lmbAdmin.nonce,
            package_id: packageId
        }, function(response) {
            if (response.success) {
                button.closest('.lmb-package-card').fadeOut(300, function() {
                    $(this).remove();
                    if ($('.lmb-package-card').length === 0) {
                        $('#lmb-packages-grid').html('<div class="lmb-no-packages"><p>No packages found. Create your first package above.</p></div>');
                    }
                });
            } else {
                alert('Error: ' + response.data.message);
                button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
            }
        });
    });
});