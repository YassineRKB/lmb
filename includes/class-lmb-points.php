<?php
if (!defined('ABSPATH')) { 
    exit; 
}

class LMB_Points {
    const META_KEY = 'lmb_points_balance';
    
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
        self::log($user_id, $new_balance - $old_balance, $reason, $old_balance, $new_balance);
        
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

        $old_balance = self::get($user_id);
        $new_balance = max(0, $old_balance + (int) $delta);
        
        update_user_meta($user_id, self::META_KEY, $new_balance);
        self::log($user_id, (int) $delta, $reason, $old_balance, $new_balance);
        
        do_action('lmb_points_changed', $user_id, $new_balance, (int) $delta, $reason);
        
        return $new_balance;
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
            ];
        }

        return $history;
    }

    /**
     * Log points transaction
     */
    protected static function log($user_id, $delta, $reason, $old_balance = null, $new_balance = null) {
        if ($old_balance === null) {
            $old_balance = self::get($user_id) - $delta;
        }
        if ($new_balance === null) {
            $new_balance = self::get($user_id);
        }

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
     * Get total points in the system
     */
    public static function get_total_points() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::META_KEY
        );
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
     * Bulk operations for points
     */
    public static function bulk_add_points($user_ids, $points, $reason = 'Bulk points addition') {
        $results = [];
        foreach ($user_ids as $user_id) {
            $results[$user_id] = self::add($user_id, $points, $reason);
        }
        return $results;
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