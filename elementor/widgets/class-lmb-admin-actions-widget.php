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
                    <div class="lmb-card-header">
                        <h3 class="lmb-card-title">
                            <i class="fas fa-bolt"></i>
                            <?php _e('Quick Actions', 'lmb-core'); ?>
                        </h3>
                    </div>
                    <div class="lmb-card-body">
                        <div class="lmb-action-buttons">
                            <a href="<?php echo admin_url('post-new.php?post_type=lmb_newspaper'); ?>" class="lmb-action-btn lmb-btn-primary">
                                <i class="fas fa-plus-circle"></i> 
                                <span><?php _e('Upload New Newspaper', 'lmb-core'); ?></span>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad'); ?>" class="lmb-action-btn lmb-btn-secondary">
                                <i class="fas fa-gavel"></i> 
                                <span><?php _e('Manage Legal Ads', 'lmb-core'); ?></span>
                            </a>
                            <a href="<?php echo admin_url('edit.php?post_type=lmb_payment'); ?>" class="lmb-action-btn lmb-btn-info">
                                <i class="fas fa-credit-card"></i> 
                                <span><?php _e('Review Payments', 'lmb-core'); ?></span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="lmb-action-card">
                    <div class="lmb-card-header">
                        <h3 class="lmb-card-title">
                            <i class="fas fa-clock"></i>
                            <?php _e('Pending Legal Ads', 'lmb-core'); ?>
                            <span class="lmb-badge"><?php echo count($pending_ads); ?></span>
                        </h3>
                    </div>
                    <div class="lmb-card-body">
                        <div class="lmb-feed">
                            <?php if (!empty($pending_ads)): ?>
                                <?php foreach($pending_ads as $ad): ?>
                                    <div class="lmb-feed-item">
                                        <div class="lmb-feed-content">
                                            <a href="<?php echo get_edit_post_link($ad->ID); ?>" class="lmb-feed-title">
                                                <?php echo esc_html($ad->post_title); ?>
                                            </a>
                                            <span class="lmb-feed-meta">
                                                <i class="fas fa-user"></i>
                                                <?php 
                                                $client_id = get_post_meta($ad->ID, 'lmb_client_id', true);
                                                $user = get_userdata($client_id);
                                                echo $user ? esc_html($user->display_name) : 'Unknown';
                                                ?>
                                                • <?php echo human_time_diff(get_the_time('U', $ad->ID)); ?> ago
                                            </span>
                                        </div>
                                        <div class="lmb-feed-actions">
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-ad-action" data-action="approve" data-id="<?php echo $ad->ID; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-ad-action" data-action="deny" data-id="<?php echo $ad->ID; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="lmb-feed-empty">
                                    <i class="fas fa-check-circle"></i>
                                    <p><?php _e('No legal ads are pending approval.', 'lmb-core'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="lmb-action-card">
                    <div class="lmb-card-header">
                        <h3 class="lmb-card-title">
                            <i class="fas fa-money-check-alt"></i>
                            <?php _e('Pending Payments', 'lmb-core'); ?>
                            <span class="lmb-badge"><?php echo count($pending_payments); ?></span>
                        </h3>
                    </div>
                    <div class="lmb-card-body">
                        <div class="lmb-feed">
                            <?php if (!empty($pending_payments)): ?>
                                <?php foreach($pending_payments as $payment): ?>
                                    <div class="lmb-feed-item">
                                        <div class="lmb-feed-content">
                                            <a href="<?php echo get_edit_post_link($payment->ID); ?>" class="lmb-feed-title">
                                                <?php echo esc_html($payment->post_title); ?>
                                            </a>
                                            <span class="lmb-feed-meta">
                                                <i class="fas fa-receipt"></i>
                                                <?php echo esc_html(get_post_meta($payment->ID, 'payment_reference', true)); ?>
                                                • <?php echo human_time_diff(get_the_time('U', $payment->ID)); ?> ago
                                            </span>
                                        </div>
                                        <div class="lmb-feed-actions">
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action" data-action="approve" data-id="<?php echo $payment->ID; ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action" data-action="reject" data-id="<?php echo $payment->ID; ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="lmb-feed-empty">
                                    <i class="fas fa-check-circle"></i>
                                    <p><?php _e('No payments are pending verification.', 'lmb-core'); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}