<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_stats'; }
    public function get_title(){ return __('LMB Admin Stats & Overview','lmb-core'); }
    public function get_icon() { return 'eicon-dashboard'; }
    public function get_categories(){ return ['lmb-cw-admin']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<p>'.esc_html__('This widget is for administrators only.','lmb-core').'</p>';
            return;
        }
        
        $stats = LMB_Admin::collect_stats();
        ?>
        <div class="lmb-admin-stats-widget">
            <div class="lmb-stats-grid">
                <div class="lmb-stat-card lmb-stat-users">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['users_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Clients','lmb-core'); ?></div>
                        <div class="lmb-stat-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12% this month</span>
                        </div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-ads">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Legal Ads','lmb-core'); ?></div>
                        <div class="lmb-stat-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+8% this month</span>
                        </div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-news">
                    <div class="lmb-stat-icon">
                        <i class="far fa-newspaper"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['news_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Newspapers','lmb-core'); ?></div>
                        <div class="lmb-stat-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+3% this month</span>
                        </div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-revenue">
                    <div class="lmb-stat-icon">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['rev_year']); ?></div>
                        <div class="lmb-stat-label"><?php printf(__('Points Spent (%s)', 'lmb-core'), date('Y')); ?></div>
                        <div class="lmb-stat-change">
                            <i class="fas fa-arrow-up"></i>
                            <span>+15% this month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}