<?php
// FILE: elementor/widgets/class-lmb-admin-stats-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_stats'; }
    public function get_title(){ return __('LMB Admin Stats & Overview','lmb-core'); }
    public function get_title(){ return __('Statistiques et Aperçu Admin LMB','lmb-core'); }
    public function get_icon() { return 'eicon-dashboard'; }
    public function get_categories(){ return ['lmb-admin-widgets-v2']; } // Changed category

    public function get_style_depends() {
        return ['lmb-admin-widgets-v2']; // Changed to V2 styles
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<p>'.esc_html__('Ce widget est réservé aux administrateurs uniquement.','lmb-core').'</p>';
            return;
        }
        
        $stats = LMB_Admin::collect_stats();
        ?>
        <div class="lmb-admin-stats-widget">
            <div class="lmb-stats-grid lmb-stats-grid-admin">
                <div class="lmb-stat-card lmb-stat-users"><div class="lmb-stat-icon"><i class="fas fa-users"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['users_total']); ?></div><div class="lmb-stat-label"><?php esc_html_e('Total Clients','lmb-core'); ?></div></div></div>
                <div class="lmb-stat-card lmb-stat-users"><div class="lmb-stat-icon"><i class="fas fa-users"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['users_total']); ?></div><div class="lmb-stat-label">Total Clients</div></div></div>
                <div class="lmb-stat-card lmb-stat-ads-published"><div class="lmb-stat-icon"><i class="fas fa-check-circle"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_published']); ?></div><div class="lmb-stat-label">Annonces Publiées</div></div></div>
                <div class="lmb-stat-card lmb-stat-news"><div class="lmb-stat-icon"><i class="far fa-newspaper"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['news_total']); ?></div><div class="lmb-stat-label">Total Journaux</div></div></div>
                <div class="lmb-stat-card lmb-stat-ads-pending"><div class="lmb-stat-icon"><i class="fas fa-clock"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['ads_pending']); ?></div><div class="lmb-stat-label">Annonces en Attente</div></div></div>
                <div class="lmb-stat-card lmb-stat-earnings"><div class="lmb-stat-icon"><i class="fas fa-chart-line"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['earnings_month'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label">Revenus Ce Mois</div></div></div>
                <div class="lmb-stat-card lmb-stat-earnings"><div class="lmb-stat-icon"><i class="fas fa-chart-bar"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['earnings_quarter'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label">Revenus Ce Trimestre</div></div></div>
                <div class="lmb-stat-card lmb-stat-earnings"><div class="lmb-stat-icon"><i class="fas fa-chart-area"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['earnings_year'], 2); ?> <span class="lmb-currency">MAD</span></div><div class="lmb-stat-label">Revenus Cette Année</div></div></div>
                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-coins"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['total_unspent_points']); ?></div><div class="lmb-stat-label">Total Points Non Utilisés</div></div></div>
                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-hand-holding-usd"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['total_spent_points']); ?></div><div class="lmb-stat-label">Total Points Dépensés</div></div></div>
                <div class="lmb-stat-card lmb-stat-points"><div class="lmb-stat-icon"><i class="fas fa-globe"></i></div><div class="lmb-stat-content"><div class="lmb-stat-number"><?php echo number_format($stats['total_points_system']); ?></div><div class="lmb-stat-label">Total Points dans le Système</div></div></div>
            </div>
        </div>
        <?php
    }
}