jQuery(document).ready(function($) {
    const widget = $('.lmb-balance-manipulation-widget');
    if (!widget.length) {
        return;
    }

    let selectedUserId = null;

    // Search user
    widget.on('click', '#lmb-search-btn', function() {
        const searchTerm = $('#lmb-user-search').val().trim();
        if (!searchTerm) {
            alert('Please enter a user email or ID');
            return;
        }

        $('#lmb-search-results').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');

        $.post(lmbAjax.ajaxurl, {
            action: 'lmb_search_user',
            nonce: lmbAjax.nonce,
            search_term: searchTerm
        }, function(response) {
            if (response.success) {
                const user = response.data.user;
                selectedUserId = user.ID;
                
                $('#lmb-search-results').html(`
                    <div class="lmb-user-found">
                        <h5><i class="fas fa-user"></i> ${user.display_name}</h5>
                        <p><strong>Email:</strong> ${user.user_email}</p>
                        <p><strong>ID:</strong> ${user.ID}</p>
                    </div>
                `);

                $('#lmb-user-details').html(`
                    <h5>${user.display_name} (ID: ${user.ID})</h5>
                    <p>${user.user_email}</p>
                `);

                $('#lmb-current-balance').text(user.balance);
                $('#lmb-balance-section, #lmb-history-section').show();
                
                loadBalanceHistory(user.ID);
            } else {
                $('#lmb-search-results').html(`<div class="lmb-notice lmb-notice-error"><p>${response.data.message}</p></div>`);
                $('#lmb-balance-section, #lmb-history-section').hide();
            }
        });
    });

    // Update balance
    widget.on('click', '#lmb-update-balance-btn', function() {
        const button = $(this);
        if (!selectedUserId) {
            alert('Please search and select a user first');
            return;
        }

        const action = $('#lmb-balance-action').val();
        const amount = parseInt($('#lmb-balance-amount').val());
        const reason = $('#lmb-balance-reason').val();

        if (isNaN(amount) || amount < 0) {
            alert('Please enter a valid amount');
            return;
        }

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.post(lmbAjax.ajaxurl, {
            action: 'lmb_update_balance',
            nonce: lmbAjax.nonce,
            user_id: selectedUserId,
            balance_action: action,
            amount: amount,
            reason: reason
        }, function(response) {
            if (response.success) {
                $('#lmb-current-balance').text(response.data.new_balance);
                $('#lmb-balance-amount').val('');
                $('#lmb-balance-reason').val('');
                loadBalanceHistory(selectedUserId);
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
        }).always(function() {
            button.prop('disabled', false).html('<i class="fas fa-save"></i> Update Balance');
        });
    });

    function loadBalanceHistory(userId) {
        $('#lmb-balance-history').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading history...</div>');
        
        $.post(lmbAjax.ajaxurl, {
            action: 'lmb_get_balance_history',
            nonce: lmbAjax.nonce,
            user_id: userId
        }, function(response) {
            if (response.success) {
                let historyHtml = '';
                if (response.data.history && response.data.history.length > 0) {
                    response.data.history.forEach(function(item) {
                        const amountClass = item.amount >= 0 ? 'positive' : 'negative';
                        const amountSign = item.amount >= 0 ? '+' : '';
                        historyHtml += `
                            <div class="lmb-history-item">
                                <div>
                                    <strong class="${amountClass}">${amountSign}${item.amount}</strong> points
                                    <br><small>${item.reason}</small>
                                </div>
                                <div>
                                    <small>${item.created_at}</small>
                                    <br><small>Balance: ${item.balance_after}</small>
                                </div>
                            </div>
                        `;
                    });
                } else {
                    historyHtml = '<p class="lmb-no-results">No balance history found.</p>';
                }
                $('#lmb-balance-history').html(historyHtml);
            }
        });
    }
});