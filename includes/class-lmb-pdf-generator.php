<?php
if (!defined('ABSPATH')) exit;

class LMB_Points {
    const BALANCE_META_KEY = 'lmb_points_balance';
    const COST_META_KEY = 'lmb_cost_per_ad';
    
    public static function get_balance($user_id) {
        return (int) get_user_meta($user_id, self::BALANCE_META_KEY, true);
    }
    
    public static function set_balance($user_id, $points, $reason = 'Manual balance adjustment') {
        $old_balance = self::get_balance($user_id);
        $new_balance = max(0, (int) $points);
        
        update_user_meta($user_id, self::BALANCE_META_KEY, $new_balance);
        
        self::log_transaction($user_id, $new_balance - $old_balance, $new_balance, $reason);
        do_action('lmb_points_changed', $user_id, $new_balance, $new_balance - $old_balance, $reason);
        
        return $new_balance;
    }
    
    public static function add($user_id, $points, $reason = 'Points added') {
        $current = self::get_balance($user_id);
        return self::set_balance($user_id, $current + (int) $points, $reason);
    }
    
    public static function deduct($user_id, $points, $reason = 'Points deducted') {
        $current = self::get_balance($user_id);
        $points_to_deduct = (int) $points;
        
        if ($current < $points_to_deduct) {
            return false; // Insufficient balance
        }
        
        return self::set_balance($user_id, $current - $points_to_deduct, $reason);
    }

    public static function set_cost_per_ad($user_id, $cost) {
        update_user_meta($user_id, self::COST_META_KEY, (int)$cost);
    }
    
    public static function get_cost_per_ad($user_id) {
        $cost = get_user_meta($user_id, self::COST_META_KEY, true);
        return $cost !== '' ? (int)$cost : (int)get_option('lmb_default_cost_per_ad', 1);
    }
    
    private static function log_transaction($user_id, $amount, $balance_after, $reason) {
        global $wpdb;
        $table = $wpdb->prefix . 'lmb_points_transactions';
        $wpdb->insert($table, [
            'user_id' => $user_id,
            'amount' => $amount,
            'balance_after' => $balance_after,
            'reason' => $reason,
            'transaction_type' => $amount >= 0 ? 'credit' : 'debit',
            'created_at' => current_time('mysql')
        ]);
    }
    
    public static function get_transactions($user_id, $limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'lmb_points_transactions';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id, $limit
        ));
    }
}