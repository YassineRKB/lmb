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
            balance_after int(11) NOT NULL,
            reason varchar(255) NOT NULL DEFAULT '',
            transaction_type enum('credit','debit') NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
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
        
        // Clean up orphaned postmeta
        $wpdb->query("
            DELETE pm FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.ID IS NULL
            AND pm.meta_key LIKE 'lmb_%'
        ");
        
        // Optimize tables
        $tables = [
            $wpdb->prefix . 'lmb_points_transactions',
            $wpdb->posts,
            $wpdb->postmeta,
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
        
        LMB_Error_Handler::log_error('Daily maintenance completed');
    }
}