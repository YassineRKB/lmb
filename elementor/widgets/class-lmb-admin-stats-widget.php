<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_stats'; }
    public function get_title(){ return __('LMB Admin Stats & Overview','lmb-core'); }
    public function get_icon() { return 'eicon-dashboard'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<p>'.esc_html__('This widget is for administrators only.','lmb-core').'</p>';
            return;
        }
        
        $stats = LMB_Admin::collect_stats();
        ?>
        <div class="lmb-admin-stats-widget">
            <div class="lmb-stats-grid">
                <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="fas fa-users"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['users_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Clients','lmb-core'); ?></div>
                    </div>
                </div>
                 <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="fas fa-gavel"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Legal Ads','lmb-core'); ?></div>
                    </div>
                </div>
                 <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="far fa-newspaper"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['news_total']); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Newspapers','lmb-core'); ?></div>
                    </div>
                </div>
                <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['rev_year']); ?></div>
                        <div class="lmb-stat-label"><?php printf(__('Points Spent (%s)', 'lmb-core'), date('Y')); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .lmb-admin-stats-widget .lmb-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
            .lmb-admin-stats-widget .lmb-stat-card { display: flex; align-items: center; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: all .3s ease; }
            .lmb-admin-stats-widget .lmb-stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
            .lmb-admin-stats-widget .lmb-stat-icon { font-size: 28px; color: #0073aa; margin-right: 20px; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; background: #f0f5fa; border-radius: 50%; }
            .lmb-admin-stats-widget .lmb-stat-number { font-size: 26px; font-weight: 600; color: #1d2327; line-height: 1.1; }
            .lmb-admin-stats-widget .lmb-stat-label { font-size: 14px; color: #50575e; }
        </style>
        <?php
    }
}