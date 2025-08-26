<?php
// FILE: elementor/widgets/class-lmb-user-stats-widget.php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_stats'; }
    public function get_title(){ return __('LMB User Stats','lmb-core'); }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories(){ return ['lmb-user-widgets']; }

    public function get_style_depends() {
        return ['lmb-admin-widgets']; // Reuse the same stylish card layout
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>'.esc_html__('This content is only available for logged-in users.','lmb-core').'</p>';
            return;
        }

        $stats = LMB_User_Dashboard::collect_user_stats();
        ?>
        <div class="lmb-admin-stats-widget lmb-user-stats-widget">
            <div class="lmb-stats-grid">
                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-coins"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['points_balance']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Your Points Balance','lmb-core'); ?></div></div></div>
                
                <div class="lmb-stat-card lmb-stat-due-payments"><div class="lmb-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['due_payments_value'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label"><?php esc_html_e('Due Payments','lmb-core'); ?></div></div></div>
                
                <div class="lmb-stat-card lmb-stat-pending"><div class="lmb-stat-icon"><i class="fas fa-clock"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_pending']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Ads Pending Review','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-published"><div class="lmb-stat-icon"><i class="fas fa-check-circle"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_published']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Published Ads','lmb-core'); ?></div></div></div>
            </div>
        </div>
        <?php
    }
}