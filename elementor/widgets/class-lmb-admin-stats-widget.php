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
        echo '<div class="lmb-admin-stats">';
        echo '<ul>';
        echo '<li>'.sprintf(__('Total Users: %d','lmb-core'), $stats['users_total']).'</li>';
        echo '<li>'.sprintf(__('Total Legal Ads: %d','lmb-core'), $stats['ads_total']).'</li>';
        echo '<li>'.sprintf(__('Total Newspapers: %d','lmb-core'), $stats['news_total']).'</li>';
        echo '<li>'.sprintf(__('Revenue Today (points): %d','lmb-core'), $stats['rev_today']).'</li>';
        echo '<li>'.sprintf(__('Revenue This Month (points): %d','lmb-core'), $stats['rev_month']).'</li>';
        echo '<li>'.sprintf(__('Revenue This Year (points): %d','lmb-core'), $stats['rev_year']).'</li>';
        echo '</ul>';

        $log = get_option('lmb_activity_log', []);
        echo '<h4>'.esc_html__('Recent Activity','lmb-core').'</h4>';
        echo '<div class="lmb-activity">';
        foreach ($log as $row) {
            $u = get_userdata($row['user']);
            echo '<div class="lmb-activity-row"><strong>'.esc_html($row['time']).'</strong> ';
            echo esc_html($u ? $u->user_login : '-').': ';
            echo esc_html($row['msg']).'</div>';
        }
        echo '</div></div>';
    }
}
