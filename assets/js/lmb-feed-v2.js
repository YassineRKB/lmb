jQuery(document).ready(function($) {
    
    // Find each instance of the Feed V2 widget
    $('.lmb-feed-v2-widget').each(function() {
        const widget = $(this);
        const feedListContainer = widget.find('.feed-list');
        const limit = widget.data('limit') || 10; // Get the limit from Elementor control

        // Function to fetch and render the feed
        const fetchFeed = () => {
            // The placeholder is already in the HTML, so we just wait for the response.
            
            $.post(lmb_ajax_params.ajaxurl, {
                action: 'lmb_fetch_feed_v2', // This is the new backend action we need to create
                nonce: lmb_ajax_params.nonce,
                limit: limit
            }).done(function(response) {
                if (response.success && response.data.html) {
                    feedListContainer.html(response.data.html);
                } else {
                    feedListContainer.html('<div class="feed-item-placeholder" style="text-align: center; padding: 20px;">' + (response.data.message || 'Could not load feed.') + '</div>');
                }
            }).fail(function() {
                feedListContainer.html('<div class="feed-item-placeholder" style="text-align: center; padding: 20px;">An error occurred. Please try again.</div>');
            });
        };

        // Initial load of the feed
        fetchFeed();
    });
});