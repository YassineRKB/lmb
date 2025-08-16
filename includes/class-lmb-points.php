<?php
if (!defined('ABSPATH')) { 
    exit; 
}

class LMB_Points {
    const META_KEY = 'lmb_points_balance';
    const TRANSACTION_CREDIT = 'credit';
    const TRANSACTION_DEBIT = 'debit';
    
    /**
     * Initialize the points system
     */
    public static function init() {
        add_action('wp_loaded', [__CLASS__, 'setup_hooks']);
    }
    
    public static function setup_hooks() {
        // Add user points column to admin users list
        add_filter('manage_users_columns', [__CLASS__, 'add_points_column']);
        add_filter('manage_users_custom_column', [__CLASS__, 'show_points_column'], 10, 3);
        add_filter('manage_users_sortable_columns', [__CLASS__, 'sortable_points_column']);
        add_action('pre_get_users', [__CLASS__, 'sort_by_points']);
    }

    /**
     * Get user's points balance
     */
    public static function get($user_id) {
        return max(0, (int) get_user_meta($user_id, self::META_KEY, true));
    }

    /**
     * Alias for get() for backward compatibility
     */
    public static function get_points($user_id) {
        return self::get($user_id);
    }

    /**
     * Set user's points balance directly
     */
    public static function set($user_id, $value, $reason = 'Balance set') {
        $old_balance = self::get($user_id);
        $new_balance = max(0, (int) $value);
        
        update_user_meta($user_id, self::META_KEY, $new_balance);
        self::create_ledger_entry($user_id, $new_balance - $old_balance, $reason, $old_balance, $new_balance);
        
        do_action('lmb_points_changed', $user_id, $new_balance, $new_balance - $old_balance, $reason);
        
        return $new_balance;
    }

    /**
     * Add points to user's balance (can be negative to subtract)
     */
    public static function add($user_id, $delta, $reason = '') {
        if ($delta == 0) {
            return self::get($user_id);
        }

        global $wpdb;
        
        $delta = (int) $delta;
        $old_balance = self::get($user_id);
        $new_balance = max(0, $old_balance + $delta);
        
        // Start transaction for data consistency
        $wpdb->query('START TRANSACTION');
        
        try {
            // Update user meta
            $meta_updated = update_user_meta($user_id, self::META_KEY, $new_balance);
            
            if ($meta_updated === false) {
                throw new Exception('Failed to update user points balance');
            }
            
            // Log transaction to custom table
            $log_result = $wpdb->insert(
                $wpdb->prefix . 'lmb_points_transactions',
                [
                    'user_id' => $user_id,
                    'amount' => $delta,
                    'balance_before' => $old_balance,
                    'balance_after' => $new_balance,
                    'reason' => $reason,
                    'transaction_type' => $delta > 0 ? self::TRANSACTION_CREDIT : self::TRANSACTION_DEBIT,
                    'reference' => null
                ],
                ['%d', '%d', '%d', '%d', '%s', '%s', '%s']
            );
            
            if ($log_result === false) {
                throw new Exception('Failed to log points transaction');
            }
            
            // Also create legacy ledger entry for backward compatibility
            self::create_ledger_entry($user_id, $delta, $reason, $old_balance, $new_balance);
            
            $wpdb->query('COMMIT');
            
            do_action('lmb_points_changed', $user_id, $new_balance, $delta, $reason);
            
            return $new_balance;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            LMB_Error_Handler::handle_points_error($e->getMessage(), $user_id, $delta, $reason);
            return false;
        }
    }

    /**
     * Subtract points from user's balance
     */
    public static function deduct($user_id, $amount, $reason = '') {
        return self::add($user_id, -abs((int) $amount), $reason);
    }

    /**
     * Check if user has enough points
     */
    public static function has_sufficient($user_id, $required_points) {
        return self::get($user_id) >= (int) $required_points;
    }

    /**
     * Transfer points between users
     */
    public static function transfer($from_user_id, $to_user_id, $amount, $reason = 'Points transfer') {
        $amount = abs((int) $amount);
        
        if (!self::has_sufficient($from_user_id, $amount)) {
            return new WP_Error('insufficient_points', __('Insufficient points for transfer.', 'lmb-core'));
        }

        $from_user = get_userdata($from_user_id);
        $to_user = get_userdata($to_user_id);
        
        if (!$from_user || !$to_user) {
            return new WP_Error('invalid_user', __('Invalid user ID.', 'lmb-core'));
        }

        // Perform the transfer
        self::deduct($from_user_id, $amount, sprintf('Transfer to %s: %s', $to_user->display_name, $reason));
        self::add($to_user_id, $amount, sprintf('Transfer from %s: %s', $from_user->display_name, $reason));

        do_action('lmb_points_transferred', $from_user_id, $to_user_id, $amount, $reason);

        return true;
    }

    /**
     * Get points history for a user
     */
    public static function get_history($user_id, $limit = 50) {
        // Try to get from custom table first
        $transactions = self::get_transaction_history($user_id, $limit);
        
        if (!empty($transactions)) {
            $history = [];
            foreach ($transactions as $transaction) {
                $history[] = [
                    'date' => $transaction->created_at,
                    'delta' => $transaction->amount,
                    'reason' => $transaction->reason,
                    'balance_after' => $transaction->balance_after,
                    'balance_before' => $transaction->balance_before,
                    'type' => $transaction->transaction_type
                ];
            }
            return $history;
        }
        
        // Fallback to legacy method
        $posts = get_posts([
            'post_type' => 'lmb_points_ledger',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => 'lmb_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $history = [];
        foreach ($posts as $post) {
            $history[] = [
                'date' => $post->post_date,
                'delta' => get_post_meta($post->ID, 'lmb_delta', true),
                'reason' => get_post_meta($post->ID, 'lmb_reason', true),
                'balance_after' => get_post_meta($post->ID, 'lmb_balance', true),
                'balance_before' => get_post_meta($post->ID, 'lmb_balance_before', true),
                'type' => null // Legacy entries don't have type
            ];
        }

        return $history;
    }

    /**
     * Create legacy ledger entry for backward compatibility
     */
    private static function create_ledger_entry($user_id, $delta, $reason, $old_balance, $new_balance) {
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : 'Unknown User';

        wp_insert_post([
            'post_type' => 'lmb_points_ledger',
            'post_status' => 'publish',
            'post_title' => sprintf(
                '%s: %s%d points (%s)', 
                $user_name, 
                $delta > 0 ? '+' : '', 
                $delta,
                $reason ?: 'No reason provided'
            ),
            'meta_input' => [
                'lmb_user_id' => (int) $user_id,
                'lmb_delta' => (int) $delta,
                'lmb_reason' => sanitize_text_field($reason),
                'lmb_balance' => (int) $new_balance,
                'lmb_balance_before' => (int) $old_balance,
            ],
        ]);
    }

    /**
     * Get points transaction history from custom table
     */
    public static function get_transaction_history($user_id, $limit = 50) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}lmb_points_transactions 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        ));
    }

    /**
     * Get total points in the system
     */
    public static function get_total_points() {
        global $wpdb;
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(CAST(meta_value AS SIGNED)) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::META_KEY
        ));
    }
    
    /**
     * Get points statistics
     */
    public static function get_points_stats() {
        global $wpdb;
        
        $stats = [
            'total_points' => self::get_total_points(),
            'total_users_with_points' => 0,
            'average_balance' => 0,
            'total_transactions' => 0,
            'points_distributed_today' => 0,
            'points_spent_today' => 0
        ];
        
        // Users with points
        $stats['total_users_with_points'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND CAST(meta_value AS SIGNED) > 0",
            self::META_KEY
        ));
        
        // Average balance
        if ($stats['total_users_with_points'] > 0) {
            $stats['average_balance'] = round($stats['total_points'] / $stats['total_users_with_points'], 2);
        }
        
        // Transaction stats (if custom table exists)
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}lmb_points_transactions'");
        if ($table_exists) {
            $stats['total_transactions'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}lmb_points_transactions"
            );
            
            $stats['points_distributed_today'] = (int) $wpdb->get_var(
                "SELECT SUM(amount) FROM {$wpdb->prefix}lmb_points_transactions 
                 WHERE transaction_type = 'credit' AND DATE(created_at) = CURDATE()"
            );
            
            $stats['points_spent_today'] = abs((int) $wpdb->get_var(
                "SELECT SUM(amount) FROM {$wpdb->prefix}lmb_points_transactions 
                 WHERE transaction_type = 'debit' AND DATE(created_at) = CURDATE()"
            ));
        }
        
        return $stats;
    }
    
    /**
     * Bulk add points to multiple users
     */
    public static function bulk_add_points($user_ids, $points, $reason = 'Bulk points addition') {
        $results = [];
        foreach ($user_ids as $user_id) {
            $result = self::add($user_id, $points, $reason);
            $results[$user_id] = $result !== false ? $result : 'failed';
        }
        return $results;
    }
    
    /**
     * Get user's points rank
     */
    public static function get_user_rank($user_id) {
        global $wpdb;
        
        $user_points = self::get($user_id);
        
        $rank = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) + 1 FROM {$wpdb->usermeta} 
             WHERE meta_key = %s AND CAST(meta_value AS SIGNED) > %d",
            self::META_KEY,
            $user_points
        ));
        
        return (int) $rank;
    }

    /**
     * Get top users by points
     */
    public static function get_leaderboard($limit = 10) {
        return get_users([
            'meta_key' => self::META_KEY,
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'number' => $limit,
            'fields' => ['ID', 'display_name', 'user_email']
        ]);
    }

    /**
     * Admin columns - Add points column
     */
    public static function add_points_column($columns) {
        $columns['lmb_points'] = __('Points', 'lmb-core');
        return $columns;
    }

    /**
     * Admin columns - Show points value
     */
    public static function show_points_column($value, $column_name, $user_id) {
        if ($column_name === 'lmb_points') {
            $points = self::get($user_id);
            return '<strong>' . number_format($points) . '</strong>';
        }
        return $value;
    }

    /**
     * Make points column sortable
     */
    public static function sortable_points_column($columns) {
        $columns['lmb_points'] = 'lmb_points';
        return $columns;
    }

    /**
     * Handle sorting by points
     */
    public static function sort_by_points($user_query) {
        if (!is_admin()) {
            return;
        }

        $orderby = $user_query->get('orderby');
        if ($orderby === 'lmb_points') {
            $user_query->set('meta_key', self::META_KEY);
            $user_query->set('orderby', 'meta_value_num');
        }
    }

    /**
     * Award points for specific actions
     */
    public static function award_for_action($user_id, $action, $custom_amount = null) {
        $amounts = apply_filters('lmb_points_action_amounts', [
            'registration' => 10,
            'profile_complete' => 5,
            'first_ad' => 20,
            'referral' => 50,
        ]);

        $amount = $custom_amount !== null ? $custom_amount : ($amounts[$action] ?? 0);
        
        if ($amount > 0) {
            return self::add($user_id, $amount, sprintf('Awarded for: %s', $action));
        }

        return false;
    }

    /**
     * Get formatted points string
     */
    public static function format_points($points) {
        return sprintf(
            _n('%s point', '%s points', $points, 'lmb-core'),
            number_format_i18n($points)
        );
    }
}

// Initialize the points system
LMB_Points::init();