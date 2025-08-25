<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Actions_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_actions'; }
    public function get_title(){ return __('LMB Admin Actions & Feeds','lmb-core'); }
    public function get_icon() { return 'eicon-tabs'; }
    public function get_categories(){ return ['lmb-admin-widgets']; }

    public function get_script_depends() { return ['lmb-admin-actions']; }
    public function get_style_depends() { return ['lmb-admin-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) return;
        ?>
        <div class="lmb-admin-actions-widget lmb-admin-widget">
            <div class="lmb-tabs-nav">
                <button class="lmb-tab-btn active" data-tab="feed"><i class="fas fa-stream"></i> <?php _e('Activity Feed', 'lmb-core'); ?></button>
                <button class="lmb-tab-btn" data-tab="pending-ads"><i class="fas fa-clock"></i> <?php _e('Pending Ads', 'lmb-core'); ?><span class="lmb-tab-badge" id="pending-ads-count">0</span></button>
                <button class="lmb-tab-btn" data-tab="pending-payments"><i class="fas fa-money-check-alt"></i> <?php _e('Pending Payments', 'lmb-core'); ?><span class="lmb-tab-badge" id="pending-payments-count">0</span></button>
            </div>
            <div class="lmb-tab-content"><div id="lmb-tab-content-area"><div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i></div></div></div>
        </div>
        <?php
    }

    public static function get_tab_content($tab) {
        $pending_ads_query = new WP_Query(['post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_query' => [['key' => 'lmb_status', 'value' => 'pending_review', 'compare' => '=']]]);
        $pending_payments_query = new WP_Query(['post_type' => 'lmb_payment', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);

        $content = '';
        switch ($tab) {
            case 'feed': $content = self::render_activity_feed_content(); break;
            case 'pending-ads': $content = self::render_pending_ads_content($pending_ads_query->posts); break;
            case 'pending-payments': $content = self::render_pending_payments_content($pending_payments_query->posts); break;
        }

        return [
            'content' => $content,
            'pending_ads_count' => $pending_ads_query->found_posts,
            'pending_payments_count' => $pending_payments_query->found_posts,
        ];
    }

    private static function render_activity_feed_content() {
        $activity_log = get_option('lmb_activity_log', []);
        if (empty($activity_log)) return '<div class="lmb-feed-empty"><i class="fas fa-stream"></i><p>' . __('No recent activity.', 'lmb-core') . '</p></div>';
        $html = '<div class="lmb-activity-feed">';
        foreach (array_slice($activity_log, 0, 15) as $entry) {
            $user = get_userdata($entry['user']);
            $user_name = $user ? $user->display_name : 'System';
            $html .= '<div class="lmb-feed-item"><div class="lmb-feed-content"><div class="lmb-feed-title">' . esc_html($entry['msg']) . '</div><div class="lmb-feed-meta"><i class="fas fa-user"></i> ' . esc_html($user_name) . ' • <i class="fas fa-clock"></i> ' . human_time_diff(strtotime($entry['time'])) . ' ago</div></div></div>';
        }
        return $html . '</div>';
    }

    private static function render_pending_ads_content($posts) {
        if (empty($posts)) return '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No legal ads are pending approval.', 'lmb-core') . '</p></div>';
        $html = '<div class="lmb-pending-ads-feed">';
        foreach ($posts as $ad) {
            $client = get_userdata($ad->post_author);
            $html .= '<div class="lmb-feed-item" data-id="' . $ad->ID . '"><div class="lmb-feed-content"><a href="' . get_edit_post_link($ad->ID) . '" class="lmb-feed-title" target="_blank">' . esc_html($ad->post_title) . '</a><div class="lmb-feed-meta"><i class="fas fa-user"></i> ' . ($client ? esc_html($client->display_name) : 'Unknown') . ' • <i class="fas fa-clock"></i> ' . human_time_diff(get_the_time('U', $ad->ID)) . ' ago</div></div><div class="lmb-feed-actions"><button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-ad-action" data-action="approve" data-id="' . $ad->ID . '"><i class="fas fa-check"></i> Approve</button><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-ad-action" data-action="deny" data-id="' . $ad->ID . '"><i class="fas fa-times"></i> Deny</button></div></div>';
        }
        return $html . '</div>';
    }

    private static function render_pending_payments_content($posts) {
        if (empty($posts)) return '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No payments are pending verification.', 'lmb-core') . '</p></div>';
        $html = '<div class="lmb-pending-payments-feed">';
        foreach ($posts as $payment) {
            $user = get_userdata(get_post_meta($payment->ID, 'user_id', true));
            $proof_url = wp_get_attachment_url(get_post_meta($payment->ID, 'proof_attachment_id', true));
            $html .= '<div class="lmb-feed-item" data-id="' . $payment->ID . '"><div class="lmb-feed-content"><a href="' . get_edit_post_link($payment->ID) . '" class="lmb-feed-title" target="_blank">' . esc_html($payment->post_title) . '</a><div class="lmb-feed-meta"><i class="fas fa-user"></i> ' . ($user ? esc_html($user->display_name) : 'Unknown') . '</div></div><div class="lmb-feed-actions">' . ($proof_url ? '<a href="'.esc_url($proof_url).'" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-paperclip"></i> View Proof</a>' : '') . '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action" data-action="approve" data-id="' . $payment->ID . '"><i class="fas fa-check"></i> Approve</button><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action" data-action="reject" data-id="' . $payment->ID . '"><i class="fas fa-times"></i> Reject</button></div></div>';
        }
        return $html . '</div>';
    }
}