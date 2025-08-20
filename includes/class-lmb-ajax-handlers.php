<?php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {
    public static function init() {
        // Balance history AJAX handler
        add_action('wp_ajax_lmb_get_balance_history', [__CLASS__, 'get_balance_history']);
    }

    public static function get_balance_history() {
        check_ajax_referer('lmb_balance_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied']);
        }

        $user_id = intval($_POST['user_id']);
        
        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid user ID']);
        }

        $transactions = LMB_Points::get_transactions($user_id, 10);
        
        $history = [];
        foreach ($transactions as $transaction) {
            $history[] = [
                'amount' => intval($transaction->amount),
                'balance_after' => intval($transaction->balance_after),
                'reason' => esc_html($transaction->reason),
                'created_at' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($transaction->created_at))
            ];
        }

        wp_send_json_success(['history' => $history]);
    }
}