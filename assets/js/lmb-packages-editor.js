jQuery(document).ready(function($) {
    const widget = $('.lmb-packages-editor-widget');
    if (!widget.length) return;

    // Save/Update package
    widget.on('submit', '#lmb-package-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const button = $('#lmb-save-package-btn');
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        const formData = {
            action: 'lmb_save_package',
            nonce: lmb_ajax_params.nonce,
            package_id: $('#package-id').val(),
            name: $('#package-name').val(),
            price: $('#package-price').val(),
            points: $('#package-points').val(),
            cost_per_ad: $('#package-cost-per-ad').val(),
            description: $('#package-description').val()
        };

        $.post(lmb_ajax_params.ajaxurl, formData)
        .done(function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload(); // Easiest way to show the updated list
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('A server error occurred.');
        }).always(function() {
            button.prop('disabled', false).html(originalText);
        });
    });

    // Populate form for editing
    widget.on('click', '.lmb-edit-package', function() {
        const card = $(this).closest('.lmb-package-card');
        
        $('#package-id').val(card.data('package-id'));
        $('#package-name').val(card.find('.lmb-package-title').text());
        $('#package-price').val(card.data('price'));
        $('#package-points').val(card.data('points'));
        $('#package-cost-per-ad').val(card.data('cost-per-ad'));
        $('#package-description').val(card.data('description'));
        
        $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> Update Package');
        $('#lmb-cancel-edit-btn').show();
        
        $('html, body').animate({ scrollTop: widget.offset().top - 50 }, 500);
    });

    // Cancel edit
    widget.on('click', '#lmb-cancel-edit-btn', function() {
        $('#lmb-package-form')[0].reset();
        $('#package-id').val('');
        $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> Save Package');
        $(this).hide();
    });

    // Delete package
    widget.on('click', '.lmb-delete-package', function() {
        if (!confirm('Are you sure you want to permanently delete this package?')) return;

        const button = $(this);
        const card = button.closest('.lmb-package-card');
        const packageId = card.data('package-id');
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_delete_package',
            nonce: lmb_ajax_params.nonce,
            package_id: packageId
        }).done(function(response) {
            if (response.success) {
                card.fadeOut(400, function() { $(this).remove(); });
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
                button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
            }
        }).fail(function() {
            alert('A server error occurred.');
            button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
        });
    });
});