jQuery(document).ready(function($) {
    const widget = $('#lmb-balance-manipulation-widget');
    if (!widget.length) return;

    // Search for user
    widget.on('submit', '#lmb-user-search-form', function(e) {
        e.preventDefault(); // Prevent page reload
        const searchTerm = $('#lmb-user-search-term').val().trim();
        const resultsContainer = $('#lmb-search-results');
        const balanceSection = $('#lmb-balance-section');
        const historySection = $('#lmb-history-section');

        if (!searchTerm) {
            resultsContainer.html('<div class="lmb-notice lmb-notice-error"><p>Please enter a search term.</p></div>');
            return;
        }

        resultsContainer.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        balanceSection.hide();
        historySection.hide();

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_search_user',
            nonce: lmb_ajax_params.nonce,
            search_term: searchTerm
        }).done(function(response) {
            if (response.success) {
                const user = response.data.user;
                resultsContainer.html(`<div class="lmb-notice lmb-notice-success"><p>Found user: ${user.display_name}</p></div>`);
                $('#lmb-user-id').val(user.ID);
                $('#lmb-user-details').html(`<h5>${user.display_name} (ID: ${user.ID})</h5><p>${user.user_email}</p>`);
                $('#lmb-current-balance').text(user.balance);
                balanceSection.show();
                historySection.show();
                loadBalanceHistory(user.ID);
            } else {
                resultsContainer.html(`<div class="lmb-notice lmb-notice-error"><p>${response.data.message}</p></div>`);
            }
        }).fail(function() {
            resultsContainer.html('<div class="lmb-notice lmb-notice-error"><p>Server error occurred.</p></div>');
        });
    });

    // Update balance
    widget.on('submit', '#lmb-balance-form', function(e) {
        e.preventDefault(); // Prevent page reload
        const button = $('#lmb-update-balance-btn');
        const userId = $('#lmb-user-id').val();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_update_balance',
            nonce: lmb_ajax_params.nonce,
            user_id: userId,
            balance_action: $('#lmb-balance-action').val(),
            amount: $('#lmb-balance-amount').val(),
            reason: $('#lmb-balance-reason').val()
        }).done(function(response) {
            if (response.success) {
                $('#lmb-current-balance').text(response.data.new_balance);
                alert(response.data.message);
                loadBalanceHistory(userId);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).fail(function() {
            alert('An unknown server error occurred.');
        }).always(function() {
            button.prop('disabled', false).html('<i class="fas fa-save"></i> Update Balance');
        });
    });

    function loadBalanceHistory(userId) {
        const historyContainer = $('#lmb-balance-history');
        historyContainer.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading history...</div>');
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_balance_history',
            nonce: lmb_ajax_params.nonce,
            user_id: userId
        }).done(function(response) {
            if (response.success && response.data.history.length > 0) {
                let historyHtml = response.data.history.map(item => `
                    <div class="lmb-history-item">
                        <div>
                            <strong>${item.amount > 0 ? '+' : ''}${item.amount}</strong> points
                            <br><small>${item.reason}</small>
                        </div>
                        <div>
                            <small>${item.created_at}</small>
                            <br><small>New Balance: ${item.balance_after}</small>
                        </div>
                    </div>`).join('');
                historyContainer.html(historyHtml);
            } else {
                historyContainer.html('<p>No balance history found for this user.</p>');
            }
        });
    }
});