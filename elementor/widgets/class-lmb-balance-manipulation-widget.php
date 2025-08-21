<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Balance_Manipulation_Widget extends Widget_Base {
    public function get_name() { return 'lmb_balance_manipulation'; }
    public function get_title() { return __('LMB Balance Manipulation', 'lmb-core'); }
    public function get_icon() { return 'eicon-coins'; }
    public function get_categories() { return ['lmb-2']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied. Administrator privileges required.', 'lmb-core') . '</p></div>';
            return;
        }

        wp_enqueue_script('jquery');
        ?>
        <div class="lmb-balance-manipulation-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-coins"></i> <?php esc_html_e('Balance Manipulation', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <!-- User Search Section -->
                <div class="lmb-search-section">
                    <h4><?php esc_html_e('Search User', 'lmb-core'); ?></h4>
                    <div class="lmb-search-form">
                        <input type="text" id="lmb-user-search" placeholder="<?php esc_attr_e('Enter user email or ID...', 'lmb-core'); ?>" class="lmb-input">
                        <button type="button" id="lmb-search-btn" class="lmb-btn lmb-btn-primary">
                            <i class="fas fa-search"></i> <?php esc_html_e('Search', 'lmb-core'); ?>
                        </button>
                    </div>
                    <div id="lmb-search-results" class="lmb-search-results"></div>
                </div>

                <!-- Balance Manipulation Section -->
                <div id="lmb-balance-section" class="lmb-balance-section" style="display: none;">
                    <h4><?php esc_html_e('Balance Management', 'lmb-core'); ?></h4>
                    
                    <div class="lmb-user-info">
                        <div id="lmb-user-details"></div>
                        <div class="lmb-current-balance">
                            <span class="lmb-balance-label"><?php esc_html_e('Current Balance:', 'lmb-core'); ?></span>
                            <span id="lmb-current-balance" class="lmb-balance-value">0</span>
                            <span class="lmb-balance-unit"><?php esc_html_e('points', 'lmb-core'); ?></span>
                        </div>
                    </div>

                    <div class="lmb-balance-form">
                        <div class="lmb-form-row">
                            <div class="lmb-form-group">
                                <label for="lmb-balance-action"><?php esc_html_e('Action', 'lmb-core'); ?></label>
                                <select id="lmb-balance-action" class="lmb-select">
                                    <option value="add"><?php esc_html_e('Add Points', 'lmb-core'); ?></option>
                                    <option value="subtract"><?php esc_html_e('Subtract Points', 'lmb-core'); ?></option>
                                    <option value="set"><?php esc_html_e('Set Balance', 'lmb-core'); ?></option>
                                </select>
                            </div>
                            
                            <div class="lmb-form-group">
                                <label for="lmb-balance-amount"><?php esc_html_e('Amount', 'lmb-core'); ?></label>
                                <input type="number" id="lmb-balance-amount" min="0" step="1" class="lmb-input" placeholder="0">
                            </div>
                        </div>

                        <div class="lmb-form-group">
                            <label for="lmb-balance-reason"><?php esc_html_e('Reason (optional)', 'lmb-core'); ?></label>
                            <textarea id="lmb-balance-reason" class="lmb-textarea" rows="3" placeholder="<?php esc_attr_e('Enter reason for balance change...', 'lmb-core'); ?>"></textarea>
                        </div>

                        <div class="lmb-form-actions">
                            <button type="button" id="lmb-update-balance-btn" class="lmb-btn lmb-btn-success">
                                <i class="fas fa-save"></i> <?php esc_html_e('Update Balance', 'lmb-core'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Balance History Section -->
                <div id="lmb-history-section" class="lmb-history-section" style="display: none;">
                    <h4><?php esc_html_e('Recent Balance Changes', 'lmb-core'); ?></h4>
                    <div id="lmb-balance-history" class="lmb-balance-history"></div>
                </div>
            </div>
        </div>

        <style>
        .lmb-balance-manipulation-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lmb-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .lmb-widget-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lmb-widget-content {
            padding: 20px;
        }
        .lmb-search-section,
        .lmb-balance-section,
        .lmb-history-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .lmb-search-form .lmb-input {
            flex: 1;
        }
        .lmb-search-results {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            min-height: 50px;
        }
        .lmb-user-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .lmb-current-balance {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .lmb-balance-value {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }
        .lmb-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .lmb-form-group {
            margin-bottom: 15px;
        }
        .lmb-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        .lmb-input, .lmb-select, .lmb-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .lmb-form-actions {
            text-align: center;
        }
        .lmb-balance-history {
            max-height: 300px;
            overflow-y: auto;
        }
        .lmb-history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-history-item:last-child {
            border-bottom: none;
        }
        .lmb-loading {
            text-align: center;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .lmb-form-row {
                grid-template-columns: 1fr;
            }
            .lmb-search-form {
                flex-direction: column;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let selectedUserId = null;

            // Search user
            $('#lmb-search-btn').on('click', function() {
                const searchTerm = $('#lmb-user-search').val().trim();
                if (!searchTerm) {
                    alert('<?php esc_js_e('Please enter a user email or ID', 'lmb-core'); ?>');
                    return;
                }

                $('#lmb-search-results').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_js_e('Searching...', 'lmb-core'); ?></div>');

                $.post(ajaxurl, {
                    action: 'lmb_search_user',
                    nonce: '<?php echo wp_create_nonce('lmb_balance_nonce'); ?>',
                    search_term: searchTerm
                }, function(response) {
                    if (response.success) {
                        const user = response.data.user;
                        selectedUserId = user.ID;
                        
                        $('#lmb-search-results').html(`
                            <div class="lmb-user-found">
                                <h5><i class="fas fa-user"></i> ${user.display_name}</h5>
                                <p><strong><?php esc_js_e('Email:', 'lmb-core'); ?></strong> ${user.user_email}</p>
                                <p><strong><?php esc_js_e('ID:', 'lmb-core'); ?></strong> ${user.ID}</p>
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
            $('#lmb-update-balance-btn').on('click', function() {
                if (!selectedUserId) {
                    alert('<?php esc_js_e('Please search and select a user first', 'lmb-core'); ?>');
                    return;
                }

                const action = $('#lmb-balance-action').val();
                const amount = parseInt($('#lmb-balance-amount').val());
                const reason = $('#lmb-balance-reason').val();

                if (!amount || amount < 0) {
                    alert('<?php esc_js_e('Please enter a valid amount', 'lmb-core'); ?>');
                    return;
                }

                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php esc_js_e('Updating...', 'lmb-core'); ?>');

                $.post(ajaxurl, {
                    action: 'lmb_update_balance',
                    nonce: '<?php echo wp_create_nonce('lmb_balance_nonce'); ?>',
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
                        alert('<?php esc_js_e('Balance updated successfully!', 'lmb-core'); ?>');
                    } else {
                        alert('<?php esc_js_e('Error:', 'lmb-core'); ?> ' + response.data.message);
                    }
                }).always(function() {
                    $('#lmb-update-balance-btn').prop('disabled', false).html('<i class="fas fa-save"></i> <?php esc_js_e('Update Balance', 'lmb-core'); ?>');
                });
            });

            function loadBalanceHistory(userId) {
                $('#lmb-balance-history').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_js_e('Loading history...', 'lmb-core'); ?></div>');
                
                $.post(ajaxurl, {
                    action: 'lmb_get_balance_history',
                    nonce: '<?php echo wp_create_nonce('lmb_balance_nonce'); ?>',
                    user_id: userId
                }, function(response) {
                    if (response.success) {
                        let historyHtml = '';
                        if (response.data.history.length > 0) {
                            response.data.history.forEach(function(item) {
                                historyHtml += `
                                    <div class="lmb-history-item">
                                        <div>
                                            <strong>${item.amount > 0 ? '+' : ''}${item.amount}</strong> points
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
                            historyHtml = '<p class="lmb-no-results"><?php esc_js_e('No balance history found.', 'lmb-core'); ?></p>';
                        }
                        $('#lmb-balance-history').html(historyHtml);
                    }
                });
            }
        });
        </script>
        <?php
    }
}

// Add AJAX handlers
add_action('wp_ajax_lmb_search_user', function() {
    check_ajax_referer('lmb_balance_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied']);
    }

    $search_term = sanitize_text_field($_POST['search_term']);
    
    // Try to find user by ID first
    if (is_numeric($search_term)) {
        $user = get_user_by('ID', intval($search_term));
    } else {
        // Try by email
        $user = get_user_by('email', $search_term);
    }

    if (!$user) {
        wp_send_json_error(['message' => __('User not found', 'lmb-core')]);
    }

    $balance = class_exists('LMB_Points') ? LMB_Points::get_balance($user->ID) : 0;

    wp_send_json_success([
        'user' => [
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_email' => $user->user_email,
            'balance' => $balance
        ]
    ]);
});

add_action('wp_ajax_lmb_update_balance', function() {
    check_ajax_referer('lmb_balance_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied']);
    }

    $user_id = intval($_POST['user_id']);
    $action = sanitize_text_field($_POST['balance_action']);
    $amount = intval($_POST['amount']);
    $reason = sanitize_text_field($_POST['reason']);

    if (!$user_id || !$amount) {
        wp_send_json_error(['message' => __('Invalid parameters', 'lmb-core')]);
    }

    if (!class_exists('LMB_Points')) {
        wp_send_json_error(['message' => __('Points system not available', 'lmb-core')]);
    }

    $admin_user = wp_get_current_user();
    $reason = $reason ?: sprintf(__('Balance %s by admin %s', 'lmb-core'), $action, $admin_user->display_name);

    $current_balance = LMB_Points::get_balance($user_id);
    
    switch ($action) {
        case 'add':
            $new_balance = LMB_Points::add($user_id, $amount, $reason);
            break;
        case 'subtract':
            $new_balance = LMB_Points::deduct($user_id, $amount, $reason);
            if ($new_balance === false) {
                wp_send_json_error(['message' => __('Insufficient balance', 'lmb-core')]);
            }
            break;
        case 'set':
            $new_balance = LMB_Points::set_balance($user_id, $amount, $reason);
            break;
        default:
            wp_send_json_error(['message' => __('Invalid action', 'lmb-core')]);
    }

    // Log the change
    if (class_exists('LMB_Ad_Manager')) {
        LMB_Ad_Manager::log_activity(sprintf(
            'Admin %s (%d) %s %d points for user %d. New balance: %d',
            $admin_user->display_name,
            $admin_user->ID,
            $action === 'set' ? 'set balance to' : $action . 'ed',
            $amount,
            $user_id,
            $new_balance
        ));
    }

    wp_send_json_success(['new_balance' => $new_balance]);
});