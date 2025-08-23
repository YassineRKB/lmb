<?php
if (!defined('ABSPATH')) exit;

class LMB_Database_Manager {
    public static function init() {
        add_action('wp_loaded', [__CLASS__, 'maybe_create_tables']);
        add_action('lmb_daily_maintenance', [__CLASS__, 'daily_maintenance']);
        if (!wp_next_scheduled('lmb_daily_maintenance')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'lmb_daily_maintenance');
        }
    }

    /**
     * Called from register_activation_hook in lmb-core.php
     */
    public static function create_custom_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Points transactions table (kept for backwards-compat)
        $points = $wpdb->prefix . 'lmb_points_transactions';
        $sql_points = "CREATE TABLE {$points} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(18,4) NOT NULL DEFAULT 0,
            balance_after DECIMAL(18,4) NOT NULL DEFAULT 0,
            reason VARCHAR(255) NOT NULL,
            meta LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        // NEW: Notifications table
        $notifications = $wpdb->prefix . 'lmb_notifications';
        $sql_notifications = "CREATE TABLE {$notifications} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            actor_id BIGINT UNSIGNED NULL,
            ad_id BIGINT UNSIGNED NULL,
            type VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id_is_read_created (user_id, is_read, created_at),
            KEY ad_id (ad_id),
            KEY type (type)
        ) {$charset_collate};";

        dbDelta($sql_points);
        dbDelta($sql_notifications);
    }

    /**
     * Create tables if they don't exist (runs on every wp_loaded)
     */
    public static function maybe_create_tables() {
        global $wpdb;
        $notifications = $wpdb->prefix . 'lmb_notifications';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
            DB_NAME,
            $notifications
        ));
        if (!$exists) {
            self::create_custom_tables();
        }
    }

    public static function daily_maintenance() {
        global $wpdb;
        // Auto-delete notifications older than 180 days
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}lmb_notifications WHERE created_at < %s",
            gmdate('Y-m-d H:i:s', time() - 180 * DAY_IN_SECONDS)
        ));

        // Optimize tables
        $tables = [
            $wpdb->prefix . 'lmb_points_transactions',
            $wpdb->prefix . 'lmb_notifications',
            $wpdb->posts,
            $wpdb->postmeta,
        ];
        foreach ($tables as $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $wpdb->query("OPTIMIZE TABLE {$table}");
        }
    }
}