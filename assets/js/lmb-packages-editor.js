// FILE: assets/js/lmb-packages-editor.js
jQuery(document).ready(function($) {
    const widget = $('.lmb-packages-editor');
    if (!widget.length) return;

    const form = $('#lmb-package-form');
    const packageList = $('#lmb-packages-list');
    const clearBtn = $('#lmb-clear-form-btn');

    /**
     * Renders the HTML for a single package card from a data object.
     * @param {object} pkg The package data object.
     * @returns {string} The HTML string for the package card.
     */
    function renderPackageCard(pkg) {
        const description = pkg.description ? `<p>${pkg.trimmed_description}</p>` : '';
        const visibility = pkg.client_visible ?
            '<span class="lmb-package-card-visibility"><i class="fas fa-eye"></i> Visible</span>' :
            '<span class="lmb-package-card-visibility"><i class="fas fa-eye-slash"></i> Caché</span>';

        return `
            <div class="lmb-package-card" data-package-id="${pkg.id}">
                <div class="lmb-package-card-header">
                    <div>
                        <h4 class="lmb-package-card-title">${pkg.name}</h4>
                        ${visibility}
                    </div>
                    <div class="lmb-package-card-actions">
                        <button class="lmb-edit-package-btn lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-pencil-alt"></i></button>
                        <button class="lmb-delete-package-btn lmb-btn lmb-btn-sm lmb-btn-danger"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="lmb-package-card-body">
                    ${description}
                    <div class="lmb-package-card-details">
                        <span><strong>Price:</strong> ${pkg.price} MAD</span>
                        <span><strong>Points:</strong> ${pkg.points}</span>
                        <span><strong>Cost/Ad:</strong> ${pkg.cost_per_ad}</span>
                    </div>
                </div>
            </div>
        `;
    }

    // Handle Form Submission (Create/Update)
    form.on('submit', function(e) {
        e.preventDefault();

        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        const data = {
            action: 'lmb_save_package',
            nonce: lmb_ajax_params.nonce,
            package_id: $('#package_id').val(),
            name: $('#package_name').val(),
            price: $('#package_price').val(),
            points: $('#package_points').val(),
            cost_per_ad: $('#package_cost').val(),
            description: $('#package_desc').val(),
            client_visible: $('#package_client_visible').is(':checked') ? '1' : '0',
        };

        $.post(lmb_ajax_params.ajaxurl, data)
            .done(function(response) {
                if (response.success) {
                    showLMBModal('success', response.data.message);
                    const packageId = response.data.package.id;
                    const existingCard = packageList.find(`.lmb-package-card[data-package-id="${packageId}"]`);
                    const newCardHtml = renderPackageCard(response.data.package);

                    if (existingCard.length) {
                        existingCard.replaceWith(newCardHtml);
                    } else {
                        // To handle the case where the list might be empty initially
                        const noPackagesMessage = packageList.find('p');
                        if (noPackagesMessage.length) {
                            noPackagesMessage.remove();
                        }
                        packageList.append(newCardHtml);
                    }
                    clearForm();
                } else {
                    showLMBModal('error', response.data.message);
                }
            })
            .fail(function() {
                showLMBModal('error', 'An error occurred while saving.');
            })
            .always(function() {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Package');
            });
    });

    // Handle Edit Button Click
    packageList.on('click', '.lmb-edit-package-btn', function() {
        const card = $(this).closest('.lmb-package-card');
        const packageId = card.data('package-id');

        // Fetch full package data to populate the form accurately
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_package_data',
            nonce: lmb_ajax_params.nonce,
            package_id: packageId
        }).done(function(response) {
            if (response.success) {
                const pkg = response.data.package;
                $('#package_id').val(pkg.id);
                $('#package_name').val(pkg.name);
                $('#package_price').val(pkg.price);
                $('#package_points').val(pkg.points);
                $('#package_cost').val(pkg.cost_per_ad);
                $('#package_desc').val(pkg.description);
                $('#package_client_visible').prop('checked', pkg.client_visible);

                $('html, body').animate({ scrollTop: form.offset().top - 50 }, 300);
            } else {
                showLMBModal('error', response.data.message || 'Could not fetch package data.');
            }
        }).fail(function() {
            showLMBModal('error', 'An error occurred while fetching package data.');
        });
    });

    // Handle Delete Button Click
    packageList.on('click', '.lmb-delete-package-btn', function() {
        if (!confirm('Are you sure you want to delete this package?')) return;

        const card = $(this).closest('.lmb-package-card');
        const packageId = card.data('package-id');
        card.css('opacity', 0.5);

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_delete_package',
            nonce: lmb_ajax_params.nonce,
            package_id: packageId,
        }).done(function(response) {
            if (response.success) {
                showLMBModal('success', response.data.message);
                card.fadeOut(300, function() { $(this).remove(); });
            } else {
                showLMBModal('error', response.data.message);
                card.css('opacity', 1);
            }
        }).fail(function() {
            showLMBModal('error', 'An error occurred during deletion.');
            card.css('opacity', 1);
        });
    });

    // Clear Form Button
    clearBtn.on('click', clearForm);

    function clearForm() {
        form[0].reset();
        $('#package_id').val('');
        $('#package_client_visible').prop('checked', true); // Ensure checkbox resets to checked
    }
});