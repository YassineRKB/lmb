<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Ads_List_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_ads_list'; }
    public function get_title() { return __('LMB User Ads List', 'lmb-core'); }
    public function get_icon() { return 'eicon-posts-grid'; }
    public function get_categories() { return ['lmb-user-widgets']; }

    public function get_script_depends() {
        return ['lmb-user-ads-list'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>' . esc_html__('Please log in to see your ads.', 'lmb-core') . '</p>';
            return;
        }
        ?>
        <div id="lmb-user-ads-list-container">
            <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading your ads...</div>
        </div>
        <div id="lmb-user-ads-pagination"></div>
        <?php
    }
}