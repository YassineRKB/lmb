<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_stats'; }
    public function get_title(){ return __('LMB User Stats','lmb-core'); }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>'.esc_html__('Please log in to see your stats.','lmb-core').'</p>';
            return;
        }

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $total_ads = count_user_posts($user_id, 'lmb_legal_ad');
        $points_balance = LMB_Points::get_balance($user_id);
        $cost_per_ad = LMB_Points::get_cost_per_ad($user_id);
        ?>
        <div class="lmb-user-stats-widget">
            <div class="lmb-user-welcome">
                <h2><?php printf(__('Welcome back, %s!', 'lmb-core'), esc_html($user->display_name)); ?></h2>
            </div>
            <div class="lmb-stats-grid">
                <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($points_balance); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Current Points Balance','lmb-core'); ?></div>
                    </div>
                </div>
                 <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="fas fa-gavel"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($total_ads); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Ads Submitted','lmb-core'); ?></div>
                    </div>
                </div>
                 <div class="lmb-stat-card">
                    <div class="lmb-stat-icon"><i class="fas fa-tag"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($cost_per_ad); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Points Per Ad','lmb-core'); ?></div>
                    </div>
                </div>
            </div>
            <div class="lmb-user-charts">
                <?php echo do_shortcode('[lmb_user_charts]'); ?>
            </div>
        </div>
        <?php
    }
}