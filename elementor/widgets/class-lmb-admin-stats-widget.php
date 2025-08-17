<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_stats'; }
    public function get_title(){ return __('LMB Admin Stats','lmb-core'); }
    public function get_icon() { return 'eicon-dashboard'; }
    public function get_categories(){ return ['general']; }

    protected function render() {
        if (!current_user_can('edit_others_posts')) { echo '<p>'.esc_html__('Admins only.','lmb-core').'</p>'; return; }
        
        $stats = LMB_Admin::collect_stats();
        
        echo '<div class="lmb-admin-stats-widget">';
        echo '<h3>'.esc_html__('System Overview','lmb-core').'</h3>';
        
        // Metrics grid
        echo '<div class="lmb-stats-grid">';
        
        echo '<div class="lmb-stat-card">';
        echo '<div class="lmb-stat-number">'.number_format($stats['users_total']).'</div>';
        echo '<div class="lmb-stat-label">'.esc_html__('Total Users','lmb-core').'</div>';
        echo '</div>';
        
        echo '<div class="lmb-stat-card pending">';
        echo '<div class="lmb-stat-number">'.number_format($stats['ads_pending']).'</div>';
        echo '<div class="lmb-stat-label">'.esc_html__('Pending Ads','lmb-core').'</div>';
        echo '</div>';
        
        echo '<div class="lmb-stat-card">';
        echo '<div class="lmb-stat-number">'.number_format($stats['ads_total']).'</div>';
        echo '<div class="lmb-stat-label">'.esc_html__('Total Ads','lmb-core').'</div>';
        echo '</div>';
        
        echo '<div class="lmb-stat-card">';
        echo '<div class="lmb-stat-number">'.number_format($stats['news_total']).'</div>';
        echo '<div class="lmb-stat-label">'.esc_html__('Newspapers','lmb-core').'</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Revenue section
        echo '<div class="lmb-revenue-section">';
        echo '<h4>'.esc_html__('Revenue (Points)','lmb-core').'</h4>';
        echo '<div class="lmb-revenue-grid">';
        
        echo '<div class="lmb-revenue-item">';
        echo '<span class="lmb-revenue-label">'.esc_html__('Today','lmb-core').'</span>';
        echo '<span class="lmb-revenue-value">'.number_format($stats['rev_today']).'</span>';
        echo '</div>';
        
        echo '<div class="lmb-revenue-item">';
        echo '<span class="lmb-revenue-label">'.esc_html__('This Month','lmb-core').'</span>';
        echo '<span class="lmb-revenue-value">'.number_format($stats['rev_month']).'</span>';
        echo '</div>';
        
        echo '<div class="lmb-revenue-item">';
        echo '<span class="lmb-revenue-label">'.esc_html__('This Year','lmb-core').'</span>';
        echo '<span class="lmb-revenue-value">'.number_format($stats['rev_year']).'</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';

        // Recent activity
        $log = get_option('lmb_activity_log', []);
        if ($log) {
            echo '<div class="lmb-activity-section">';
            echo '<h4>'.esc_html__('Recent Activity','lmb-core').'</h4>';
            echo '<div class="lmb-activity-feed">';
            
            foreach (array_slice($log, 0, 5) as $row) {
                $user = get_userdata($row['user']);
                echo '<div class="lmb-activity-item">';
                echo '<div class="lmb-activity-time">'.esc_html(human_time_diff(strtotime($row['time']), current_time('timestamp')) . ' ago').'</div>';
                echo '<div class="lmb-activity-user">'.esc_html($user ? $user->display_name : 'System').'</div>';
                echo '<div class="lmb-activity-message">'.esc_html($row['msg']).'</div>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add styles
        ?>
        <style>
        .lmb-admin-stats-widget { padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .lmb-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .lmb-stat-card { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: #f9f9f9; }
        .lmb-stat-card.pending { border-left: 4px solid #d63638; }
        .lmb-stat-number { font-size: 24px; font-weight: bold; color: #0073aa; margin-bottom: 5px; }
        .lmb-stat-label { color: #666; font-size: 12px; text-transform: uppercase; }
        .lmb-revenue-section { margin-bottom: 30px; }
        .lmb-revenue-section h4 { margin-bottom: 15px; color: #333; }
        .lmb-revenue-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .lmb-revenue-item { display: flex; justify-content: space-between; padding: 10px 15px; background: #f0f8ff; border-radius: 4px; }
        .lmb-revenue-label { color: #666; }
        .lmb-revenue-value { font-weight: bold; color: #0073aa; }
        .lmb-activity-section h4 { margin-bottom: 15px; color: #333; }
        .lmb-activity-feed { max-height: 300px; overflow-y: auto; }
        .lmb-activity-item { padding: 10px; border-bottom: 1px solid #f0f0f1; }
        .lmb-activity-time { color: #666; font-size: 12px; }
        .lmb-activity-user { font-weight: bold; color: #0073aa; }
        .lmb-activity-message { color: #333; margin-top: 2px; }
        
        @media (max-width: 768px) {
            .lmb-stats-grid { grid-template-columns: repeat(2, 1fr); }
            .lmb-revenue-grid { grid-template-columns: 1fr; }
        }
        </style>
        <?php
    }
}
