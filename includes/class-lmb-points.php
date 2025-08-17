<?php
if (!defined('ABSPATH')) exit;

class LMB_Points {
    const META_KEY = 'lmb_points_balance';
    const META_COST_PER_AD = 'lmb_cost_per_ad';
    
    /**
     * Get user's points balance
     */
    public static function get($user_id) {
        return self::get_balance($user_id);
    }
    
    /**
     * Set user's points balance
     */
    public static function set($user_id, $points, $reason = '') {
        $old_balance = self::get_balance($user_id);
        $new_balance = max(0, (int) $points);
        
        update_user_meta($user_id, self::META_KEY, $new_balance);
        
        // Log transaction
        self::log_transaction($user_id, $new_balance - $old_balance, $new_balance, $reason);
        
        do_action('lmb_points_changed', $user_id, $new_balance, $new_balance - $old_balance, $reason);
        
        return $new_balance;
    }
    
    /**
     * Add points to user's balance
     */
    public static function add($user_id, $points, $reason = '') {
        $current = self::get_balance($user_id);
        return self::set($user_id, $current + (int) $points, $reason);
    }
    
    /**
     * Deduct points from user's balance
     */
    public static function deduct($user_id, $points, $reason = '') {
        $current = self::get_balance($user_id);
        $points = (int) $points;
        
        if ($current < $points) {
            return false; // Insufficient balance
        }
        
        return self::set($user_id, $current - $points, $reason);
    }

    public static function get_balance($user_id) {
        return (int) get_user_meta($user_id, self::META_KEY, true);
    }
    
    public static function set_balance($user_id, $points) {
        return self::set($user_id, $points);
    }
    
    public static function set_cost_per_ad($user_id, $points) {
        update_user_meta($user_id, self::META_COST_PER_AD, (int)$points);
    }
    
    public static function get_cost_per_ad($user_id) {
        $v = get_user_meta($user_id, self::META_COST_PER_AD, true);
        return $v === '' ? 0 : (int)$v;
    }
    
    /**
     * Log points transaction
     */
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
    
    /**
     * Get user's transaction history
     */
    public static function get_transactions($user_id, $limit = 50) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'lmb_points_transactions';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d",
            $user_id,
            $limit
        ));
    }
    
    /**
     * Get total points in system
     */
    public static function get_total_points() {
        global $wpdb;
        
        return (int) $wpdb->get_var(
            "SELECT SUM(CAST(meta_value AS SIGNED)) FROM {$wpdb->usermeta} 
             WHERE meta_key = '" . self::META_KEY . "'"
        );
    }
}