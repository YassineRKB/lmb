<?php
// FILE: elementor/widgets/class-lmb-admin-stats-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_stats'; }
    public function get_title(){ return __('LMB Admin Stats & Overview','lmb-core'); }
    public function get_icon() { return 'eicon-dashboard'; }
    public function get_categories(){ return ['lmb-admin-widgets']; }

    // --- ADDED: Style dependency for the new layout ---
    public function get_style_depends() {
        return ['lmb-admin-widgets'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<p>'.esc_html__('This widget is for administrators only.','lmb-core').'</p>';
            return;
        }
        
        $stats = LMB_Admin::collect_stats();
        ?>
        <div class="lmb-admin-stats-widget">
            <div class="lmb-stats-grid lmb-stats-grid-admin">
                <div class="lmb-stat-card lmb-stat-users"><div class="lmb-stat-icon"><i class="fas fa-users"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['users_total']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Total Clients','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-ads-published"><div class="lmb-stat-icon"><i class="fas fa-check-circle"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_published']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Published Ads','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-ads-pending"><div class="lmb-stat-icon"><i class="fas fa-clock"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_pending']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Pending Ads','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-ads-draft"><div class="lmb-stat-icon"><i class="fas fa-edit"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_draft']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Draft Ads','lmb-core'); ?></div></div></div>
                
                <div class="lmb-stat-card lmb-stat-due-payments"><div class="lmb-stat-icon"><i class="fas fa-file-invoice-dollar"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['due_payments_value'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label"><?php echo sprintf(esc_html__('%d Due Payments', 'lmb-core'), $stats['due_payments_count']); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-earnings"><div class="lmb-stat-icon"><i class="fas fa-chart-line"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['earnings_month'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label"><?php esc_html_e('Earnings This Month','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-earnings"><div class="lmb-stat-icon"><i class="fas fa-chart-bar"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['earnings_quarter'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label"><?php esc_html_e('Earnings This Quarter','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-earnings"><div class="lmb-stat-icon"><i class="fas fa-chart-area"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['earnings_year'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label"><?php esc_html_e('Earnings This Year','lmb-core'); ?></div></div></div>

                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-coins"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['total_unspent_points']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Total Unspent Points','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-hand-holding-usd"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['total_spent_points']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Total Spent Points','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-globe"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['total_points_system']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Total Points in System','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-news"><div class="lmb-stat-icon"><i class="far fa-newspaper"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['news_total']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Total Newspapers','lmb-core'); ?></div></div></div>
            </div>
        </div>
        <?php
    }
}