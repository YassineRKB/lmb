// FILE: assets/js/lmb-balance-manipulation.js
jQuery(document).ready(function($) {
    const widget = $('.lmb-balance-manipulation-widget');
    if (!widget.length) return;

    const nonce = lmb_ajax_params.nonce; // Using the global nonce
    const resultsContainer = $('#lmb-search-results');
    const balanceSection = $('#lmb-balance-section');
    const historySection = $('#lmb-history-section');
    const currentBalanceEl = $('#lmb-current-balance');
    const historyContainer = $('#lmb-balance-history');

    // Search for users
    widget.on('submit', '#lmb-user-search-form', function(e) {
        e.preventDefault();
        const searchTerm = $('#lmb-user-search-term').val().trim();

        if (!searchTerm) {
            resultsContainer.html('<p class="lmb-notice lmb-notice-error">Please enter a search term.</p>');
            return;
        }

        resultsContainer.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');
        balanceSection.hide();
        historySection.hide();

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_search_user',
            nonce: nonce,
            search_term: searchTerm
        }).done(function(response) {
            if (response.success) {
                let userListHtml = '<div class="lmb-user-results-list">';
                response.data.users.forEach(user => {
                    userListHtml += `<div class="lmb-user-result-item" data-user-id="${user.ID}" data-user-name="${escape(user.display_name)}">
                        <strong>${user.display_name}</strong> (ID: ${user.ID})<br>
                        <small>${user.user_email}</small>
                    </div>`;
                });
                userListHtml += '</div>';
                resultsContainer.html(userListHtml);
            } else {
                resultsContainer.html(`<p class="lmb-notice lmb-notice-error">${response.data.message}</p>`);
            }
        }).fail(function() {
            resultsContainer.html('<p class="lmb-notice lmb-notice-error">A server error occurred.</p>');
        });
    });

    // Handle clicking on a user in the search results
    resultsContainer.on('click', '.lmb-user-result-item', function() {
        const userId = $(this).data('userId');
        const userName = unescape($(this).data('userName'));
        
        $('.lmb-user-result-item').removeClass('selected');
        $(this).addClass('selected');

        $('#lmb-user-id').val(userId);
        $('#lmb-selected-user-name').text(userName);
        balanceSection.show();
        historySection.show();
        
        loadBalanceAndHistory(userId);
    });

    // Update balance
    widget.on('submit', '#lmb-balance-form', function(e) {
        e.preventDefault();
        const button = $('#lmb-update-balance-btn');
        const userId = $('#lmb-user-id').val();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_update_balance',
            nonce: nonce,
            user_id: userId,
            balance_action: $('#lmb-balance-action').val(),
            amount: $('#lmb-balance-amount').val(),
            reason: $('#lmb-balance-reason').val()
        }).done(function(response) {
            if (response.success) {
                if (typeof showLMBModal === 'function') {
                    showLMBModal('success', response.data.message);
                } else {
                    alert(response.data.message);
                }
                currentBalanceEl.text(response.data.new_balance);
                loadBalanceAndHistory(userId);
            } else {
                 if (typeof showLMBModal === 'function') {
                    showLMBModal('error', response.data.message);
                } else {
                    alert('Error: ' + response.data.message);
                }
            }
        }).fail(function() {
             if (typeof showLMBModal === 'function') {
                showLMBModal('error', 'An unknown server error occurred.');
            } else {
                alert('An unknown server error occurred.');
            }
        }).always(function() {
            button.prop('disabled', false).html('<i class="fas fa-save"></i> Update Balance');
        });
    });

    function loadBalanceAndHistory(userId) {
        currentBalanceEl.html('<i class="fas fa-spinner fa-spin"></i>');
        historyContainer.html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading history...</div>');
        
        $.post(lmb_ajax_params.ajaxurl, {
            action: 'lmb_get_balance_history',
            nonce: nonce,
            user_id: userId
        }).done(function(response) {
            if (response.success) {
                const history = response.data.history;
                currentBalanceEl.text(response.data.current_balance);
                
                if (history && history.length > 0) {
                    let historyHtml = history.map(item => `
                        <div class="lmb-history-item">
                            <div>
                                <strong class="${item.amount > 0 ? 'credit' : 'debit'}">${item.amount > 0 ? '+' : ''}${item.amount} points</strong>
                                <br><small>${item.reason || 'No reason specified'}</small>
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
            }
        });
    }

    // --- NEW: Listen for the custom event from the subscribe widget ---
    $(document).on('lmb:balanceUpdated', function(event, data) {
        // Check if a user is currently selected in this widget
        const currentUserId = $('#lmb-user-id').val();
        
        // If the updated user is the one we are currently viewing, refresh the history
        if (currentUserId && parseInt(currentUserId) === parseInt(data.user_id)) {
            loadBalanceAndHistory(currentUserId);
        }
    });
});