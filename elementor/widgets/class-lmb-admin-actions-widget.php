<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Actions_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_actions'; }
    public function get_title(){ return __('LMB Admin Actions & Feeds','lmb-core'); }
    public function get_icon() { return 'eicon-dual-button'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) return;

        $pending_ads = get_posts(['post_type' => 'lmb_legal_ad', 'post_status' => 'pending_review', 'posts_per_page' => 5]);
        $pending_payments = get_posts(['post_type' => 'lmb_payment', 'posts_per_page' => 5, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);
        ?>
        <div class="lmb-admin-actions-widget">
            <div class="lmb-actions-grid">
                <div class="lmb-action-card">
                    <h3 class="lmb-card-title"><?php _e('Quick Actions', 'lmb-core'); ?></h3>
                    <div class="lmb-action-buttons">
                        <a href="<?php echo admin_url('post-new.php?post_type=lmb_newspaper'); ?>" class="lmb-action-btn">
                            <i class="fas fa-plus-circle"></i> <?php _e('Upload New Newspaper', 'lmb-core'); ?>
                        </a>
                         <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad'); ?>" class="lmb-action-btn">
                            <i class="fas fa-gavel"></i> <?php _e('Manage Legal Ads', 'lmb-core'); ?>
                        </a>
                         <a href="<?php echo admin_url('edit.php?post_type=lmb_payment'); ?>" class="lmb-action-btn">
                            <i class="fas fa-credit-card"></i> <?php _e('Review Payments', 'lmb-core'); ?>
                        </a>
                    </div>
                </div>

                <div class="lmb-action-card">
                    <h3 class="lmb-card-title"><?php _e('Pending Legal Ads', 'lmb-core'); ?></h3>
                    <div class="lmb-feed">
                        <?php if (!empty($pending_ads)): ?>
                            <?php foreach($pending_ads as $ad): ?>
                                <div class="lmb-feed-item">
                                    <a href="<?php echo get_edit_post_link($ad->ID); ?>"><?php echo esc_html($ad->post_title); ?></a>
                                    <span><?php echo human_time_diff(get_the_time('U', $ad->ID)); ?> ago</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="lmb-feed-empty"><i class="fas fa-check-circle"></i> <?php _e('No legal ads are pending approval.', 'lmb-core'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lmb-action-card">
                    <h3 class="lmb-card-title"><?php _e('Pending Payments', 'lmb-core'); ?></h3>
                     <div class="lmb-feed">
                        <?php if (!empty($pending_payments)): ?>
                            <?php foreach($pending_payments as $payment): ?>
                                <div class="lmb-feed-item">
                                    <a href="<?php echo get_edit_post_link($payment->ID); ?>"><?php echo esc_html($payment->post_title); ?></a>
                                     <span><?php echo human_time_diff(get_the_time('U', $payment->ID)); ?> ago</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="lmb-feed-empty"><i class="fas fa-check-circle"></i> <?php _e('No payments are pending verification.', 'lmb-core'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .lmb-admin-actions-widget .lmb-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
            .lmb-admin-actions-widget .lmb-action-card { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .lmb-admin-actions-widget .lmb-card-title { margin-top: 0; margin-bottom: 20px; font-size: 16px; font-weight: 600; border-bottom: 1px solid #eee; padding-bottom: 10px; }
            .lmb-admin-actions-widget .lmb-action-buttons { display: flex; flex-direction: column; gap: 10px; }
            .lmb-admin-actions-widget .lmb-action-btn { background: #f6f7f7; padding: 12px 15px; border-radius: 5px; text-decoration: none; color: #1d2327; font-weight: 500; display: flex; align-items: center; transition: all .3s ease; }
            .lmb-admin-actions-widget .lmb-action-btn:hover { background: #e0e2e4; }
            .lmb-admin-actions-widget .lmb-action-btn i { margin-right: 10px; color: #0073aa; }
            .lmb-admin-actions-widget .lmb-feed-item { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f5f5f5; font-size: 14px; }
            .lmb-admin-actions-widget .lmb-feed-item:last-child { border-bottom: none; }
            .lmb-admin-actions-widget .lmb-feed-item a { text-decoration: none; font-weight: 500; color: #2271b1; }
            .lmb-admin-actions-widget .lmb-feed-item span { font-size: 12px; color: #787c82; }
            .lmb-admin-actions-widget .lmb-feed-empty { margin: 0; padding: 10px 0; color: #50575e; }
            .lmb-admin-actions-widget .lmb-feed-empty i { color: #4ab866; margin-right: 8px; }
        </style>
        <?php
    }
}