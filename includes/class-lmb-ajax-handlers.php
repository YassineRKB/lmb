<?php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {

    public static function init() {
        // Existing
        add_action('wp_ajax_lmb_get_balance_history', [__CLASS__, 'get_balance_history']);

        // Centralized admin tabs loader (moved out of widget)
        add_action('wp_ajax_lmb_load_admin_tab', [__CLASS__, 'load_admin_tab']);

        // Centralized balance manipulation handlers (moved out of widget)
        add_action('wp_ajax_lmb_search_user', [__CLASS__, 'search_user']);
        add_action('wp_ajax_lmb_update_balance', [__CLASS__, 'update_balance']);
    }

    /* -------------------------------
     * Balance history (already existed)
     * ------------------------------- */
    public static function get_balance_history() {
        check_ajax_referer('lmb_balance_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid user ID'], 400);
        }

        if (!class_exists('LMB_Points') || !method_exists('LMB_Points', 'get_transactions')) {
            wp_send_json_error(['message' => 'Points system unavailable'], 500);
        }

        $transactions = LMB_Points::get_transactions($user_id, 10);
        $history = [];

        foreach ($transactions as $t) {
            $history[] = [
                'date'   => isset($t['date']) ? $t['date'] : '',
                'type'   => isset($t['type']) ? $t['type'] : '',
                'amount' => isset($t['points']) ? intval($t['points']) : 0,
                'note'   => isset($t['note']) ? $t['note'] : '',
            ];
        }

        wp_send_json_success(['history' => $history]);
    }

    /* -----------------------------------------
     * Admin tabs: feed / actions / pending views
     * (moved out of class-lmb-admin-actions-widget.php)
     * ----------------------------------------- */
    public static function load_admin_tab() {
        check_ajax_referer('lmb_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';
        $content = '';
        $pending_ads_count = 0;
        $pending_payments_count = 0;

        switch ($tab) {
            case 'feed':
                $content = self::render_activity_feed();
                break;

            case 'actions':
                $content = self::render_quick_actions();
                break;

            case 'pending-ads':
                $res = self::render_pending_ads();
                $content = $res['content'];
                $pending_ads_count = $res['count'];
                break;

            case 'pending-payments':
                $res = self::render_pending_payments();
                $content = $res['content'];
                $pending_payments_count = $res['count'];
                break;

            default:
                $content = '<p>' . esc_html__('Invalid tab', 'lmb-core') . '</p>';
        }

        wp_send_json_success([
            'content' => $content,
            'pending_ads_count' => $pending_ads_count,
            'pending_payments_count' => $pending_payments_count,
        ]);
    }

    private static function render_activity_feed() {
        $activity_log = get_option('lmb_activity_log', []);

        if (empty($activity_log)) {
            return '<div class="lmb-feed-empty"><i class="fas fa-stream"></i><p>' .
                esc_html__('No recent activity.', 'lmb-core') . '</p></div>';
        }

        $content = '<div class="lmb-activity-feed">';
        foreach (array_slice($activity_log, 0, 10) as $entry) {
            $user = isset($entry['user']) ? get_userdata($entry['user']) : null;
            $user_name = $user ? $user->display_name : esc_html__('Unknown User', 'lmb-core');

            $content .= '<div class="lmb-feed-item">';
            $content .= '  <div class="lmb-feed-content">';
            $content .= '    <div class="lmb-feed-title">' . esc_html($entry['msg'] ?? '') . '</div>';
            $content .= '    <div class="lmb-feed-meta">';
            $content .= '      <i class="fas fa-user"></i> ' . esc_html($user_name);
            if (!empty($entry['time'])) {
                $content .= ' • <i class="fas fa-clock"></i> ' . esc_html(human_time_diff(strtotime($entry['time'])) . ' ' . __('ago', 'lmb-core'));
            }
            $content .= '    </div>';
            $content .= '  </div>';
            $content .= '</div>';
        }
        $content .= '</div>';

        return $content;
    }

    private static function render_quick_actions() {
        $actions = [
            [
                'title' => __('Upload New Newspaper', 'lmb-core'),
                'icon'  => 'fas fa-plus-circle',
                'url'   => admin_url('post-new.php?post_type=lmb_newspaper'),
                'description' => __('Add a new newspaper edition', 'lmb-core'),
            ],
            [
                'title' => __('Manage Legal Ads', 'lmb-core'),
                'icon'  => 'fas fa-gavel',
                'url'   => admin_url('edit.php?post_type=lmb_legal_ad'),
                'description' => __('Review and manage legal ads', 'lmb-core'),
            ],
            [
                'title' => __('Review Payments', 'lmb-core'),
                'icon'  => 'fas fa-credit-card',
                'url'   => admin_url('edit.php?post_type=lmb_payment'),
                'description' => __('Verify or deny user payment proofs', 'lmb-core'),
            ],
            [
                'title' => __('Invoices', 'lmb-core'),
                'icon'  => 'fas fa-file-invoice',
                'url'   => admin_url('edit.php?post_type=lmb_invoice'),
                'description' => __('Browse and manage invoices', 'lmb-core'),
            ],
        ];

        $out  = '<div class="lmb-actions-grid">';
        foreach ($actions as $a) {
            $out .= '<div class="lmb-action-card">';
            $out .= '  <a class="lmb-action-link" href="' . esc_url($a['url']) . '">';
            $out .= '    <div class="lmb-action-icon"><i class="' . esc_attr($a['icon']) . '"></i></div>';
            $out .= '    <div class="lmb-action-title">' . esc_html($a['title']) . '</div>';
            $out .= '    <div class="lmb-action-desc">' . esc_html($a['description']) . '</div>';
            $out .= '  </a>';
            $out .= '</div>';
        }
        $out .= '</div>';

        return $out;
    }

    private static function render_pending_ads() {
        $pending_ads = get_posts([
            'post_type'      => 'lmb_legal_ad',
            'post_status'    => 'any',
            'posts_per_page' => 10,
            'meta_query'     => [
                [
                    'key'     => 'lmb_status',
                    'value'   => 'pending_review',
                    'compare' => '=',
                ],
            ],
        ]);

        $count = is_array($pending_ads) ? count($pending_ads) : 0;

        if (empty($pending_ads)) {
            return [
                'content' => '<div class="lmb-feed-empty"><i class="fas fa-clipboard-check"></i><p>' .
                    esc_html__('No legal ads are pending approval.', 'lmb-core') . '</p></div>',
                'count' => 0,
            ];
        }

        $out  = '<div class="lmb-pending-ads-feed">';
        foreach ($pending_ads as $post) {
            $title = get_the_title($post);
            $user_id = get_post_meta($post->ID, 'user_id', true);
            $user = $user_id ? get_userdata($user_id) : null;

            $out .= '<div class="lmb-feed-item">';
            $out .= '  <div class="lmb-feed-content">';
            $out .= '    <div class="lmb-feed-title">' . esc_html($title) . '</div>';
            $out .= '    <div class="lmb-feed-meta">';
            if ($user) {
                $out .= '      <i class="fas fa-user"></i> ' . esc_html($user->display_name) . ' • ';
            }
            $out .= '      <i class="fas fa-clock"></i> ' . esc_html(get_the_date('', $post));
            $out .= '    </div>';
            $out .= '    <div class="lmb-actions">';
            $out .= '      <button class="lmb-btn lmb-approve lmb-ad-action" data-action="approve" data-id="' . intval($post->ID) . '">' . esc_html__('Approve', 'lmb-core') . '</button>';
            $out .= '      <button class="lmb-btn lmb-deny lmb-ad-action" data-action="deny" data-id="' . intval($post->ID) . '">' . esc_html__('Deny', 'lmb-core') . '</button>';
            $out .= '    </div>';
            $out .= '  </div>';
            $out .= '</div>';
        }
        $out .= '</div>';

        return ['content' => $out, 'count' => $count];
    }

    private static function render_pending_payments() {
        $pending = get_posts([
            'post_type'      => 'lmb_payment',
            'post_status'    => 'any',
            'posts_per_page' => 10,
            'meta_query'     => [
                [
                    'key'     => 'payment_status',
                    'value'   => 'pending',
                    'compare' => '=',
                ],
            ],
        ]);

        $count = is_array($pending) ? count($pending) : 0;

        if (empty($pending)) {
            return [
                'content' => '<div class="lmb-feed-empty"><i class="fas fa-receipt"></i><p>' .
                    esc_html__('No payments are pending verification.', 'lmb-core') . '</p></div>',
                'count' => 0,
            ];
        }

        $out  = '<div class="lmb-pending-payments-feed">';
        foreach ($pending as $payment) {
            $user_id  = get_post_meta($payment->ID, 'user_id', true);
            $user     = $user_id ? get_userdata($user_id) : null;
            $reference = get_post_meta($payment->ID, 'payment_reference', true);

            $out .= '<div class="lmb-feed-item">';
            $out .= '  <div class="lmb-feed-content">';
            $out .= '    <div class="lmb-feed-title">' . esc_html(get_the_title($payment)) . '</div>';
            $out .= '    <div class="lmb-feed-meta">';
            if ($user) {
                $out .= '      <i class="fas fa-user"></i> ' . esc_html($user->display_name) . ' • ';
            }
            $out .= '      <i class="fas fa-hashtag"></i> ' . esc_html($reference);
            $out .= '    </div>';
            $out .= '    <div class="lmb-actions">';
            $out .= '      <button class="lmb-btn lmb-approve lmb-payment-action" data-action="approve" data-id="' . intval($payment->ID) . '">' . esc_html__('Approve', 'lmb-core') . '</button>';
            $out .= '      <button class="lmb-btn lmb-deny lmb-payment-action" data-action="deny" data-id="' . intval($payment->ID) . '">' . esc_html__('Deny', 'lmb-core') . '</button>';
            $out .= '    </div>';
            $out .= '  </div>';
            $out .= '</div>';
        }
        $out .= '</div>';

        return ['content' => $out, 'count' => $count];
    }

    /* ------------------------------------------------
     * Balance: search & update (moved out of widget)
     * ------------------------------------------------ */
    public static function search_user() {
        check_ajax_referer('lmb_balance_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if ($term === '') {
            wp_send_json_success(['results' => []]);
        }

        // Try by ID
        if (is_numeric($term)) {
            $u = get_user_by('ID', (int) $term);
            if ($u) {
                wp_send_json_success(['results' => [[
                    'id'   => $u->ID,
                    'text' => $u->display_name . ' (' . $u->user_email . ')',
                ]]]);
            }
        }

        // By email or login or display_name
        $args = [
            'search'         => '*' . esc_attr($term) . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number'         => 10,
        ];
        $users = get_users($args);
        $results = [];
        foreach ($users as $u) {
            $results[] = [
                'id'   => $u->ID,
                'text' => $u->display_name . ' (' . $u->user_email . ')',
            ];
        }

        wp_send_json_success(['results' => $results]);
    }

    public static function update_balance() {
        check_ajax_referer('lmb_balance_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount  = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $reason  = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        $type    = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'add';

        if (!$user_id || !$amount) {
            wp_send_json_error(['message' => 'Missing user or amount'], 400);
        }

        if (!class_exists('LMB_Points')) {
            wp_send_json_error(['message' => 'Points system unavailable'], 500);
        }

        if ($type === 'remove') {
            $amount = -abs($amount);
        }

        $ok = LMB_Points::add_points($user_id, $amount, $reason ?: 'Manual update');
        if (!$ok) {
            wp_send_json_error(['message' => 'Failed to update balance'], 500);
        }

        wp_send_json_success(['message' => 'Balance updated']);
    }
}
