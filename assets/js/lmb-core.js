/**
 * LMB Core Frontend AJAX-powered JavaScript
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

            button.text('Submitting...').prop('disabled', true);

            $.post(lmbAjax.ajaxurl, {
                action: 'lmb_submit_for_review',
                nonce: lmbAjax.nonce,
                ad_id: adId
            }).done(function(response) {
                if (response.success) {
                    adItem.removeClass('status-draft').addClass('status-pending_review');
                    adItem.find('.lmb-ad-status').text('pending review');
                    button.parent().html('<span>Awaiting review</span>');
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                    button.text('Submit for Review').prop('disabled', false);
                }
            }).fail(function() {
                alert('An unknown error occurred. Please try again.');
                button.text('Submit for Review').prop('disabled', false);
            });
        });

        // Handle "Get Invoice" button click
        $('.lmb-pricing-table').on('click', '.lmb-get-invoice-btn', function(e) {
            e.preventDefault();
            var button = $(this);
            var pkgId = button.data('pkg-id');
            var originalText = button.text();

            button.text('Generating...').prop('disabled', true);

            $.post(lmbAjax.ajaxurl, {
                action: 'lmb_generate_package_invoice',
                nonce: lmbAjax.nonce,
                pkg_id: pkgId
            }).done(function(response) {
                if (response.success && response.data.pdf_url) {
                    window.open(response.data.pdf_url, '_blank');
                    button.text(originalText).prop('disabled', false);
                } else {
                    alert('Error: ' + (response.data.message || 'Could not generate invoice.'));
                    button.text(originalText).prop('disabled', false);
                }
            }).fail(function() {
                alert('An unknown error occurred while generating the invoice.');
                button.text(originalText).prop('disabled', false);
            });
        });

    });

})(jQuery);