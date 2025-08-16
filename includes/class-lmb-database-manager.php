<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database optimization and management
 */
class LMB_Database_Manager {
    
    public static function init() {
        add_action('wp_loaded', [__CLASS__, 'maybe_create_tables']);
        add_action('lmb_daily_maintenance', [__CLASS__, 'daily_maintenance']);
        
        // Schedule daily maintenance if not already scheduled
        if (!wp_next_scheduled('lmb_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'lmb_daily_maintenance');
        }
    }
    
    /**
     * Create custom tables for better performance
     */
    public static function create_custom_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Points transactions table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lmb_points_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            amount int(11) NOT NULL,
            balance_before int(11) NOT NULL DEFAULT 0,
            balance_after int(11) NOT NULL,
            reason varchar(255) NOT NULL DEFAULT '',
            transaction_type enum('credit','debit') NOT NULL,
            reference varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at),
            KEY transaction_type (transaction_type)
        ) $charset_collate;";
        
        // Form submissions log table
        $sql2 = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}lmb_form_submissions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            post_id bigint(20) DEFAULT NULL,
            form_type varchar(100) NOT NULL,
            submission_data longtext,
            status varchar(50) DEFAULT 'pending',
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY post_id (post_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        
        // Add indexes to existing tables
        self::add_performance_indexes();
    }
    
    /**
     * Add performance indexes
     */
    public static function add_performance_indexes() {
        global $wpdb;
        
        // Check if indexes exist before creating
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_lmb_status ON {$wpdb->postmeta} (meta_key, meta_value(50)) WHERE meta_key = 'lmb_status'",
            "CREATE INDEX IF NOT EXISTS idx_lmb_client_id ON {$wpdb->postmeta} (meta_key, meta_value) WHERE meta_key = 'lmb_client_id'",
            "CREATE INDEX IF NOT EXISTS idx_lmb_points ON {$wpdb->usermeta} (meta_key, meta_value) WHERE meta_key = 'lmb_points_balance'",
            "CREATE INDEX IF NOT EXISTS idx_lmb_ad_type ON {$wpdb->postmeta} (meta_key, meta_value(50)) WHERE meta_key = 'ad_type'"
        ];
        
        foreach ($indexes as $index_sql) {
            $wpdb->query($index_sql);
        }
    }
    
    /**
     * Maybe create tables on plugin load
     */
    public static function maybe_create_tables() {
        $db_version = get_option('lmb_db_version', '0');
        
        if (version_compare($db_version, LMB_CORE_VERSION, '<')) {
            self::create_custom_tables();
            update_option('lmb_db_version', LMB_CORE_VERSION);
        }
    }
    
    /**
     * Daily maintenance tasks
     */
    public static function daily_maintenance() {
        global $wpdb;
        
        // Clean up old form submissions (older than 90 days)
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}lmb_form_submissions 
             WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            90
        ));
        
        // Clean up old points transactions (keep last 1000 per user)
        $wpdb->query("
            DELETE t1 FROM {$wpdb->prefix}lmb_points_transactions t1
            INNER JOIN (
                SELECT user_id, id
                FROM {$wpdb->prefix}lmb_points_transactions t2
                WHERE (
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}lmb_points_transactions t3
                    WHERE t3.user_id = t2.user_id AND t3.id >= t2.id
                ) > 1000
            ) t4 ON t1.id = t4.id
        ");
        
        // Clean up orphaned postmeta
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE 'lmb_%'
        ");
        
        // Clean up orphaned usermeta
        $wpdb->query("
            DELETE um FROM {$wpdb->usermeta} um
            LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
            WHERE u.ID IS NULL
            AND um.meta_key LIKE 'lmb_%'
        ");
        
        // Optimize tables
        $tables = [
            $wpdb->prefix . 'lmb_points_transactions',
            $wpdb->prefix . 'lmb_form_submissions',
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->users,
            $wpdb->usermeta
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        LMB_Error_Handler::log_error('Daily maintenance completed');
    }
    
    /**
     * Get database statistics
     */
    public static function get_database_stats() {
        global $wpdb;
        
        $stats = [
            'posts' => [],
            'users_with_points' => 0,
            'total_points' => 0,
            'database_size' => 0
        ];
        
        // Get post counts by type
        $post_types = ['lmb_legal_ad', 'lmb_newspaper', 'lmb_points_ledger'];
        foreach ($post_types as $post_type) {
            $count = wp_count_posts($post_type);
            $stats['posts'][$post_type] = $count->publish + $count->draft + $count->pending;
        }
        
        // Get users with points
        $stats['users_with_points'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'lmb_points_balance' AND meta_value > 0"
        );
        
        // Get total points in system
        $stats['total_points'] = (int) $wpdb->get_var(
            "SELECT SUM(CAST(meta_value AS SIGNED)) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'lmb_points_balance'"
        );
        
        // Get database size
        $db_size = $wpdb->get_results($wpdb->prepare(
            "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS 'size'
             FROM information_schema.tables 
             WHERE table_schema = %s",
            DB_NAME
        ));
        
        $stats['database_size'] = $db_size[0]->size ?? 0;
        
        return $stats;
    }
    
    /**
     * Repair database issues
     */
    public static function repair_database() {
        global $wpdb;
        
        $results = [];
        
        // Fix missing ACF fields
        $ads_without_status = $wpdb->get_results(
            "SELECT p.ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'lmb_status'
             WHERE p.post_type = 'lmb_legal_ad' AND pm.meta_id IS NULL"
        );
        
        foreach ($ads_without_status as $ad) {
            update_field('lmb_status', 'draft', $ad->ID);
        }
        
        $results['fixed_missing_status'] = count($ads_without_status);
        
        // Fix users without points balance
        $users_without_points = $wpdb->get_results(
            "SELECT u.ID FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = 'lmb_points_balance'
             WHERE um.umeta_id IS NULL"
        );
        
        foreach ($users_without_points as $user) {
            update_user_meta($user->ID, 'lmb_points_balance', 0);
        }
        
        $results['fixed_missing_points'] = count($users_without_points);
        
        // Recalculate points balances
        $results['recalculated_balances'] = self::recalculate_all_balances();
        
        return $results;
    }
    
    /**
     * Recalculate all user point balances
     */
    private static function recalculate_all_balances() {
        global $wpdb;
        
        $users_with_transactions = $wpdb->get_results(
            "SELECT DISTINCT user_id FROM {$wpdb->prefix}lmb_points_transactions"
        );
        
        $recalculated = 0;
        
        foreach ($users_with_transactions as $user_data) {
            $user_id = $user_data->user_id;
            
            $balance = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}lmb_points_transactions 
                 WHERE user_id = %d ORDER BY created_at ASC",
                $user_id
            ));
            
            $balance = max(0, (int) $balance);
            update_user_meta($user_id, 'lmb_points_balance', $balance);
            $recalculated++;
        }
        
        return $recalculated;
    }
}

// Initialize database manager
LMB_Database_Manager::init();