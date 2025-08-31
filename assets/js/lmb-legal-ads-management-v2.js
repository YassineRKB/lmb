jQuery(document).ready(function($) {
    const widget = $('.lmb-legal-ads-management-v2');
    if (!widget.length) return;

    const table = widget.find('.lmb-ads-table-v2');
    if (table.length) {
        table.on('click', 'tr.clickable-row', function(e) {
            // Prevent navigation if the click was on a button, link, or the actions cell itself
            if ($(e.target).closest('button, a, .lmb-actions-cell').length > 0) {
                return;
            }

            const href = $(this).data('href');
            if (href && href !== '#') {
                // In a real scenario, you'd navigate
                // window.location.href = href; 
                console.log('Navigating to: ' + href);
                alert('Simulating navigation to: ' + href);
            }
        });
    }
});
