<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Dashboard_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_dashboard'; }
    public function get_title(){ return __('LMB User Dashboard','lmb-core'); }
    public function get_icon() { return 'eicon-user-circle-o'; }
    public function get_categories(){ return ['general']; }

    protected function render() {
        if (!is_user_logged_in()) { echo '<p>'.esc_html__('Login required.','lmb-core').'</p>'; return; }

        echo '<h3>'.esc_html__('Your Points','lmb-core').'</h3>';
        echo do_shortcode('[lmb_user_points]');

        echo '<h3>'.esc_html__('Your Legal Ads','lmb-core').'</h3>';
        echo do_shortcode('[lmb_user_ads_list]');

        // Quick submit form (keeps HTML intact)
        echo '<h3>'.esc_html__('Submit New Legal Ad','lmb-core').'</h3>';
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="lmb_submit_ad" />';
        wp_nonce_field('lmb_submit_ad');
        echo '<p><input type="text" name="title" placeholder="'.esc_attr__('Title','lmb-core').'" required></p>';
        echo '<p><input type="text" name="ad_type" placeholder="'.esc_attr__('Ad Type','lmb-core').'" required></p>';
        echo '<p><textarea name="full_text" rows="10" placeholder="'.esc_attr__('Paste your formatted HTML here…','lmb-core').'" required></textarea></p>';
        echo '<p><button class="button button-primary">'.esc_html__('Save Draft','lmb-core').'</button></p>';
        echo '</form>';
    }
}
