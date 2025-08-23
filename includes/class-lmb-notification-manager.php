<?php
// FILE: includes/class-lmb-notification-manager.php
if (!defined('ABSPATH')) exit;

class LMB_Notification_Manager {
    const TABLE = 'lmb_notifications';
    const NONCE = 'lmb_notifications_nonce';

    public static function init() {
        // AJAX (logged-in only)
        add_action('wp_ajax_lmb_get_notifications', [__CLASS__, 'ajax_get_notifications']);
        add_action('wp_ajax_lmb_mark_notification_read', [__CLASS__, 'ajax_mark_notification_read']);
        add_action('wp_ajax_lmb_mark_all_notifications_read', [__CLASS__, 'ajax_mark_all_notifications_read']);

        // Event listeners for legal ad lifecycle
        add_action('updated_post_meta', [__CLASS__, 'on_updated_post_meta'], 10, 4);
        add_action('transition_post_status', [__CLASS__, 'on_transition_post_status'], 10, 3);
    }

    /* -----------------------------
     * Creation helpers
     * ---------------------------*/
    public static function add($user_id, $type, $title, $message, $args = []) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE;

        $insert = [
            'user_id'  => (int) $user_id,
            'actor_id' => isset($args['actor_id']) ? (int) $args['actor_id'] : get_current_user_id(),
            'ad_id'    => isset($args['ad_id']) ? (int) $args['ad_id'] : null,
            'type'     => sanitize_key($type),
            'title'    => wp_strip_all_tags($title),
            'message'  => wp_kses_post($message),
            'is_read'  => 0,
            'created_at'=> current_time('mysql', true), // GMT
        ];

        // Simple de-duplication within 24h for same user/type/ad
        $dupe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id=%d AND type=%s AND (ad_id <=> %d) AND created_at > %s ORDER BY id DESC LIMIT 1",
            $insert['user_id'], $insert['type'], $insert['ad_id'], gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS)
        ));
        if ($dupe) return (int) $dupe;

        $wpdb->insert($table, $insert, [
            '%d','%d','%d','%s','%s','%s','%d','%s'
        ]);
        return (int) $wpdb->insert_id;
    }

    public static function notify_admins_ad_pending($ad_id) {
        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad') return;
        $author = get_userdata($ad->post_author);
        $title = sprintf(__('Legal ad "%s" submitted for review', 'lmb-core'), get_the_title($ad_id));
        $msg   = sprintf(__('User %s has submitted a legal ad for review.', 'lmb-core'), esc_html($author ? $author->display_name : ('#'.$ad->post_author)));

        foreach (self::get_admin_user_ids() as $admin_id) {
            self::add($admin_id, 'ad_pending', $title, $msg, [ 'ad_id' => $ad_id, 'actor_id' => $ad->post_author ]);
        }
    }

    public static function notify_user_ad_approved($ad_id) {
        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad') return;
        $title = sprintf(__('Your legal ad "%s" was approved', 'lmb-core'), get_the_title($ad_id));
        $msg   = __('Your legal ad has been approved and will be published.', 'lmb-core');
        self::add($ad->post_author, 'ad_approved', $title, $msg, [ 'ad_id' => $ad_id ]);
    }

    public static function notify_user_ad_denied($ad_id, $reason = '') {
        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad') return;
        $title = sprintf(__('Your legal ad "%s" was denied', 'lmb-core'), get_the_title($ad_id));
        $msg   = $reason ? sprintf(__('Your legal ad was denied. Reason: %s', 'lmb-core'), esc_html($reason)) : __('Your legal ad was denied.', 'lmb-core');
        self::add($ad->post_author, 'ad_denied', $title, $msg, [ 'ad_id' => $ad_id ]);
    }

    // Example used elsewhere in plugin
    public static function notify_payment_verified($user_id, $package_id, $points) {
        $title = __('Payment approved', 'lmb-core');
        $msg = sprintf(__('Your payment for package %s was approved. %s points added.', 'lmb-core'), get_the_title((int)$package_id), esc_html($points));
        self::add((int)$user_id, 'payment_approved', $title, $msg);
    }

    /* -----------------------------
     * Readers & formatters
     * ---------------------------*/
    public static function get_unread_count($user_id) {
        global $wpdb; $table = $wpdb->prefix . self::TABLE;
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id=%d AND is_read=0", (int)$user_id));
    }

    public static function get_latest($user_id, $limit = 10, $offset = 0) {
        global $wpdb; $table = $wpdb->prefix . self::TABLE;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT id, user_id, actor_id, ad_id, type, title, message, is_read, created_at FROM {$table} WHERE user_id=%d ORDER BY id DESC LIMIT %d OFFSET %d",
            (int)$user_id, (int)$limit, (int)$offset
        ), ARRAY_A);
        foreach ($rows as &$r) {
            $r['time_ago'] = human_time_diff( strtotime(get_date_from_gmt($r['created_at'])) , current_time('timestamp')) . ' ' . __('ago');
        }
        return $rows;
    }

    public static function mark_read($user_id, $notification_id) {
        global $wpdb; $table = $wpdb->prefix . self::TABLE;
        return (bool) $wpdb->update($table, ['is_read'=>1], ['id'=>(int)$notification_id, 'user_id'=>(int)$user_id], ['%d'], ['%d','%d']);
    }

    public static function mark_all_read($user_id) {
        global $wpdb; $table = $wpdb->prefix . self::TABLE;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (bool) $wpdb->query( $wpdb->prepare("UPDATE {$table} SET is_read = 1 WHERE user_id=%d AND is_read=0", (int)$user_id) );
    }

    /* -----------------------------
     * Hooks
     * ---------------------------*/
    public static function on_updated_post_meta($meta_id, $post_id, $meta_key, $meta_value) {
        if ($meta_key !== 'lmb_status') return;
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'lmb_legal_ad') return;
        $new = is_string($meta_value) ? strtolower($meta_value) : '';
        if (in_array($new, ['pending','pending_review'], true)) {
            self::notify_admins_ad_pending($post_id);
        } elseif (in_array($new, ['approved','approve'], true)) {
            self::notify_user_ad_approved($post_id);
        } elseif (in_array($new, ['denied','rejected','deny'], true)) {
            $reason = get_post_meta($post_id, 'lmb_denial_reason', true);
            self::notify_user_ad_denied($post_id, $reason);
        }
    }

    public static function on_transition_post_status($new_status, $old_status, $post) {
        if ($post->post_type !== 'lmb_legal_ad') return;
        if ($new_status === 'pending' && $old_status !== 'pending') {
            self::notify_admins_ad_pending($post->ID);
        } elseif ($old_status === 'pending' && $new_status === 'publish') {
            self::notify_user_ad_approved($post->ID);
        }
        // If your workflow uses a custom status, add_filter('lmb_denied_statuses', ...) to extend this.
        $denied_statuses = apply_filters('lmb_denied_statuses', ['draft']);
        if (in_array($new_status, $denied_statuses, true) && $old_status === 'pending') {
            self::notify_user_ad_denied($post->ID);
        }
    }

    /* -----------------------------
     * AJAX
     * ---------------------------*/
    public static function verify_nonce() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['nonce']), self::NONCE)) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }
    }

    public static function ajax_get_notifications() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized'], 401);
        self::verify_nonce();
        $user_id = get_current_user_id();
        $items = self::get_latest($user_id, 10, 0);
        $unread = self::get_unread_count($user_id);
        wp_send_json_success(['items' => $items, 'unread' => $unread]);
    }

    public static function ajax_mark_notification_read() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized'], 401);
        self::verify_nonce();
        $nid = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $ok = $nid ? self::mark_read(get_current_user_id(), $nid) : false;
        wp_send_json_success(['ok' => (bool)$ok]);
    }

    public static function ajax_mark_all_notifications_read() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized'], 401);
        self::verify_nonce();
        $ok = self::mark_all_read(get_current_user_id());
        wp_send_json_success(['ok' => (bool)$ok]);
    }

    /* -----------------------------
     * Utils
     * ---------------------------*/
    private static function get_admin_user_ids() {
        $ids = [];
        $admins = get_users(['role' => 'administrator', 'fields' => ['ID']]);
        foreach ($admins as $a) { $ids[] = (int)$a->ID; }
        // Fallback: site admin email user
        $admin_email = get_option('admin_email');
        if ($admin_email) {
            $u = get_user_by('email', $admin_email);
            if ($u && !in_array((int)$u->ID, $ids, true)) $ids[] = (int)$u->ID;
        }
        return array_unique($ids);
    }

    // Email helpers (optional)
    public static function should_send_email() {
        return (bool) get_option('lmb_enable_email_notifications', 1);
    }
}

// Ensure the manager is initialized once the plugin loads
add_action('plugins_loaded', ['LMB_Notification_Manager','init']);