<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_stats'; }
    public function get_title(){ return __('LMB User Stats','lmb-core'); }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories(){ return ['lmb-cw-user']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>'.esc_html__('This content is only available for logged-in users.','lmb-core').'</p>';
            return;
        }

        $stats = LMB_User_Dashboard::collect_user_stats();
        ?>
        <div class="lmb-admin-stats-widget lmb-user-stats-widget">
            <div class="lmb-stats-grid">
                
                <div class="lmb-stat-card lmb-stat-points">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['points_balance']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Your Points Balance','lmb-core'); ?></div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-ads">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Ads Submitted','lmb-core'); ?></div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-pending">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_pending']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Ads Pending Review','lmb-core'); ?></div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-published">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_published']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Published Ads','lmb-core'); ?></div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}