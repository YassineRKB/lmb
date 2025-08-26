jQuery(document).ready(function($) {
    const widget = $('.lmb-packages-editor-widget');
    if (!widget.length) return;

    const form = $('#lmb-package-form');
    const packagesGrid = $('#lmb-packages-grid');
    const noPackagesMessage = widget.find('.lmb-no-packages');

    // Function to generate the HTML for a package card
    function createPackageCard(pkg) {
        const descriptionHtml = pkg.trimmed_description ? `<div class="lmb-package-description">${escapeHtml(pkg.trimmed_description)}</div>` : '';
        return `
            <div class="lmb-package-card" data-package-id="${pkg.id}" data-price="${pkg.price}" data-points="${pkg.points}" data-cost-per-ad="${pkg.cost_per_ad}" data-description="${escapeHtml(pkg.description)}">
                <div class="lmb-package-header">
                    <h5 class="lmb-package-title">${escapeHtml(pkg.name)}</h5>
                    <div class="lmb-package-price">${pkg.price} MAD</div>
                </div>
                <div class="lmb-package-details">
                    <div class="lmb-package-detail"><span>Points:</span><strong>${pkg.points}</strong></div>
                    <div class="lmb-package-detail"><span>Cost per Ad:</span><strong>${pkg.cost_per_ad} pts</strong></div>
                </div>
                ${descriptionHtml}
                <div class="lmb-package-actions">
                    <button class="lmb-btn lmb-btn-sm lmb-btn-primary lmb-edit-package"><i class="fas fa-edit"></i> Edit</button>
                    <button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-delete-package"><i class="fas fa-trash"></i> Delete</button>
                </div>
            </div>`;
    }

    // Save/Update package
    form.on('submit', function(e) {
        e.preventDefault();
        const button = $('#lmb-save-package-btn');
        const originalText = button.html();
        
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_save_package',
            nonce: lmb_ajax_params.nonce,
            package_id: $('#package-id').val(),
            name: $('#package-name').val(),
            price: $('#package-price').val(),
            points: $('#package-points').val(),
            cost_per_ad: $('#package-cost-per-ad').val(),
            description: $('#package-description').val()
        })
        .done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                const newCardHtml = createPackageCard(response.data.package);
                const existingCard = packagesGrid.find(`[data-package-id="${response.data.package.id}"]`);

                if (existingCard.length) { // It's an update
                    existingCard.replaceWith(newCardHtml);
                } else { // It's a new package
                    packagesGrid.append(newCardHtml);
                    if(noPackagesMessage.length) noPackagesMessage.remove();
                }
                resetForm();
            } else {
                showLMBModal('error', response.data.message);
            }
        }).fail(function() {
            showLMBModal('error', 'A server error occurred.');
        }).always(function() {
            button.prop('disabled', false).html(originalText);
        });
    });

    // Populate form for editing
    packagesGrid.on('click', '.lmb-edit-package', function() {
        const card = $(this).closest('.lmb-package-card');
        
        $('#package-id').val(card.data('package-id'));
        $('#package-name').val(card.find('.lmb-package-title').text());
        $('#package-price').val(card.data('price'));
        $('#package-points').val(card.data('points'));
        $('#package-cost-per-ad').val(card.data('cost-per-ad'));
        $('#package-description').val(card.data('description'));
        
        $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> Update Package');
        $('#lmb-cancel-edit-btn').show();
        
        $('html, body').animate({ scrollTop: widget.offset().top - 50 }, 300);
    });

    function resetForm() {
        form[0].reset();
        $('#package-id').val('');
        $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> Save Package');
        $('#lmb-cancel-edit-btn').hide();
    }
    
    // Cancel edit
    widget.on('click', '#lmb-cancel-edit-btn', resetForm);

    // Delete package
    packagesGrid.on('click', '.lmb-delete-package', function() {
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
                showLMBModal('success', response.data.message);
                card.fadeOut(400, function() { 
                    $(this).remove(); 
                    if (packagesGrid.children().length === 0) {
                        packagesGrid.html('<div class="lmb-no-packages"><p>No packages found.</p></div>');
                    }
                });
            } else {
                showLMBModal('error', response.data.message);
                button.prop('disabled', false).html('<i class="fas fa-trash"></i> Delete');
            }
        });
    });
    
    // Helper to escape HTML to prevent XSS issues when re-rendering data
    function escapeHtml(text) {
        if (typeof text !== 'string') return '';
        var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});