/**
 * LMB Core Frontend Global JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {

        // Handle "Submit for Review" button click
        $('.lmb-user-ads-list').on('click', '.lmb-submit-for-review-btn', function(e) {
            e.preventDefault();
            var button = $(this);
            var adId = button.data('ad-id');
            var adItem = button.closest('.lmb-user-ad-item');
            var originalText = button.html();

            button.html('<i class="fas fa-spinner fa-spin"></i> Submitting...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_user_submit_for_review',
                nonce: lmb_ajax_params.nonce,
                ad_id: adId
            }).done(function(response) {
                if (response.success) {
                    adItem.removeClass('status-draft').addClass('status-pending_review');
                    adItem.find('.lmb-ad-status').text('Pending Review');
                    button.parent().html('<span>Awaiting review</span>');
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                    button.html(originalText).prop('disabled', false);
                }
            }).fail(function() {
                alert('An unknown error occurred. Please try again.');
                button.html(originalText).prop('disabled', false);
            });
        });

        // Handle "Get Invoice" button click
        $('.lmb-pricing-table').on('click', '.lmb-get-invoice-btn', function(e) {
            e.preventDefault();
            var button = $(this);
            var pkgId = button.data('pkg-id');
            var originalText = button.text();

            button.html('<i class="fas fa-spinner fa-spin"></i> Generating...').prop('disabled', true);

            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_generate_package_invoice',
                nonce: lmb_ajax_params.nonce,
                pkg_id: pkgId
            }).done(function(response) {
                if (response.success && response.data.pdf_url) {
                    window.open(response.data.pdf_url, '_blank');
                } else {
                    alert('Error: ' + (response.data.message || 'Could not generate invoice.'));
                }
            }).fail(function() {
                alert('An unknown error occurred while generating the invoice.');
            }).always(function() {
                button.text(originalText).prop('disabled', false);
            });
        });
    });

})(jQuery);