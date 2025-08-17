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

        echo '<div class="lmb-user-dashboard">';
        
        // Points section
        echo do_shortcode('[lmb_user_points]');

        // Ads list section
        echo do_shortcode('[lmb_user_ads_list]');

        // Quick submit form
        echo '<div class="lmb-quick-submit">';
        echo '<h3>'.esc_html__('Submit New Legal Ad','lmb-core').'</h3>';
        echo '<p>'.esc_html__('Use this form for quick submissions, or use our detailed forms for specific ad types.','lmb-core').'</p>';
        
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="lmb-submit-form">';
        echo '<input type="hidden" name="action" value="lmb_submit_ad" />';
        wp_nonce_field('lmb_submit_ad');
        
        echo '<div class="lmb-form-row">';
        echo '<div class="lmb-form-group">';
        echo '<label for="title">'.esc_html__('Title','lmb-core').'</label>';
        echo '<input type="text" name="title" id="title" placeholder="'.esc_attr__('Enter ad title','lmb-core').'" required>';
        echo '</div>';
        
        echo '<div class="lmb-form-group">';
        echo '<label for="ad_type">'.esc_html__('Ad Type','lmb-core').'</label>';
        echo '<select name="ad_type" id="ad_type" required>';
        echo '<option value="">'.esc_html__('Select ad type','lmb-core').'</option>';
        
        $ad_types = [
            'Liquidation - definitive',
            'Liquidation - anticipee',
            'Constitution - SARL',
            'Constitution - SARL AU',
            'Modification - Capital',
            'Modification - parts',
            'Modification - denomination',
            'Modification - seige',
            'Modification - gerant',
            'Modification - objects'
        ];
        
        foreach ($ad_types as $type) {
            echo '<option value="'.esc_attr($type).'">'.esc_html($type).'</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="lmb-form-group">';
        echo '<label for="full_text">'.esc_html__('Ad Content','lmb-core').'</label>';
        echo '<textarea name="full_text" id="full_text" rows="10" placeholder="'.esc_attr__('Paste your formatted content here...','lmb-core').'" required></textarea>';
        echo '<small>'.esc_html__('You can paste HTML content to preserve formatting.','lmb-core').'</small>';
        echo '</div>';
        
        echo '<div class="lmb-form-actions">';
        echo '<button type="submit" class="lmb-submit-btn">'.esc_html__('Save as Draft','lmb-core').'</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        
        echo '</div>';
        
        // Add styles
        ?>
        <style>
        .lmb-user-dashboard { max-width: 1200px; margin: 0 auto; }
        .lmb-quick-submit { margin-top: 40px; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .lmb-submit-form { margin-top: 20px; }
        .lmb-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .lmb-form-group { margin-bottom: 20px; }
        .lmb-form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .lmb-form-group input, .lmb-form-group select, .lmb-form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .lmb-form-group small { display: block; margin-top: 5px; color: #666; font-size: 12px; }
        .lmb-form-actions { text-align: center; }
        .lmb-submit-btn { background: #0073aa; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        .lmb-submit-btn:hover { background: #005a87; }
        
        @media (max-width: 768px) {
            .lmb-form-row { grid-template-columns: 1fr; }
            .lmb-quick-submit { padding: 20px; }
        }
        </style>
        <?php
    }
}
