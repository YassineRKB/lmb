<?php
if (!defined('ABSPATH')) exit;

class LMB_Ad_Manager {
    public static function init() {
        // user clicks "Publish" from dashboard → set status pending_review
        add_action('admin_post_lmb_user_publish_ad', [__CLASS__, 'user_publish_ad']);
        // admin actions
        add_action('admin_post_lmb_admin_accept_ad', [__CLASS__, 'admin_accept_ad']);
        add_action('admin_post_lmb_admin_deny_ad',   [__CLASS__, 'admin_deny_ad']);
        // columns for admin list
        add_filter('manage_lmb_legal_ad_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_lmb_legal_ad_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
    }

    public static function cols($cols) {
        $cols['ad_owner'] = __('Owner', 'lmb-core');
        $cols['status']   = __('Status', 'lmb-core');
        return $cols;
    }
    public static function col_content($col, $post_id) {
        if ($col === 'ad_owner') {
            $u = get_userdata((int) get_post_meta($post_id, 'ad_owner', true));
            echo $u ? esc_html($u->user_login) : '-';
        } elseif ($col === 'status') {
            echo esc_html(get_post_status_object(get_post_status($post_id))->label ?? get_post_status($post_id));
        }
    }

    /** User requests publication → pending_review */
    public static function user_publish_ad() {
        if (!is_user_logged_in()) wp_die('Auth required.');
        check_admin_referer('lmb_user_publish_ad');

        $ad_id = isset($_POST['ad_id']) ? (int) $_POST['ad_id'] : 0;
        $owner = (int) get_post_meta($ad_id, 'ad_owner', true);
        if (!$ad_id || $owner !== get_current_user_id()) wp_die('Invalid ad.');

        wp_update_post(['ID' => $ad_id, 'post_status' => 'pending_review']);
        wp_safe_redirect(add_query_arg(['ad_pending' => 1], home_url('/dashboard')));
        exit;
    }

    /** Admin accepts: publish, deduct points, generate PDFs */
    public static function admin_accept_ad() {
        if (!current_user_can('edit_others_posts')) wp_die('No permission.');
        check_admin_referer('lmb_admin_accept_ad');

        $ad_id  = (int) ($_POST['ad_id'] ?? 0);
        $owner  = (int) get_post_meta($ad_id, 'ad_owner', true);
        if (!$ad_id || !$owner) wp_die('Invalid ad.');

        $cost = LMB_Points::get_cost_per_ad($owner);
        if ($cost <= 0) $cost = (int) get_option('lmb_default_cost_per_ad', 1);

        if (!LMB_Points::deduct($owner, $cost)) {
            wp_safe_redirect(add_query_arg(['ad_accept_failed' => 'insufficient_points'], wp_get_referer()));
            exit;
        }

        // publish
        wp_update_post(['ID' => $ad_id, 'post_status' => 'publish']);

        // PDFs
        $ad_pdf = LMB_PDF_Generator::create_ad_pdf_from_fulltext($ad_id);
        update_post_meta($ad_id, 'ad_pdf_url', esc_url_raw($ad_pdf));

        $bal = LMB_Points::get_balance($owner);
        $invoice_pdf = LMB_Invoice_Handler::create_ad_publication_invoice($owner, $ad_id, $cost, $bal);
        update_post_meta($ad_id, 'ad_invoice_pdf_url', esc_url_raw($invoice_pdf));

        // Activity log
        self::log_activity(sprintf('Ad #%d accepted & published by %s. Cost %d points.', $ad_id, wp_get_current_user()->user_login, $cost));

        wp_safe_redirect(add_query_arg(['ad_published' => 1], wp_get_referer()));
        exit;
    }

    /** Admin denies → lmb_denied */
    public static function admin_deny_ad() {
        if (!current_user_can('edit_others_posts')) wp_die('No permission.');
        check_admin_referer('lmb_admin_deny_ad');

        $ad_id  = (int) ($_POST['ad_id'] ?? 0);
        if (!$ad_id) wp_die('Invalid ad.');
        wp_update_post(['ID' => $ad_id, 'post_status' => 'lmb_denied']);

        self::log_activity(sprintf('Ad #%d denied by %s.', $ad_id, wp_get_current_user()->user_login));

        wp_safe_redirect(add_query_arg(['ad_denied' => 1], wp_get_referer()));
        exit;
    }

    public static function log_activity($msg) {
        $log = get_option('lmb_activity_log', []);
        if (!is_array($log)) $log = [];
        array_unshift($log, ['time'=> current_time('mysql'), 'msg'=> sanitize_text_field($msg), 'user'=> get_current_user_id()]);
        $log = array_slice($log, 0, 200);
        update_option('lmb_activity_log', $log);
    }
}
