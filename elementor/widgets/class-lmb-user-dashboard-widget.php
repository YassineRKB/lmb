<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_User_Dashboard_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_dashboard'; }
    public function get_title(){ return __('LMB User Dashboard','lmb-core'); }
    public function get_icon() { return 'eicon-user-circle-o'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>'.esc_html__('You must be logged in to view your dashboard.', 'lmb-core').'</p>';
            return;
        }

        echo '<div class="lmb-user-dashboard-widget">';
        
        // Points and Account Summary
        echo do_shortcode('[lmb_user_points]');

        // List of User's Ads
        echo do_shortcode('[lmb_user_ads_list]');

        // New Ad Submission Form
        echo '
        <div class="lmb-form-section">
            <h3>'.esc_html__('Submit a New Legal Ad', 'lmb-core').'</h3>
            <form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="lmb-submit-form">
                <input type="hidden" name="action" value="lmb_submit_ad" />
                '.wp_nonce_field('lmb_submit_ad_action', '_wpnonce', true, false).'
                
                <div class="lmb-form-group">
                    <label for="lmb_ad_title">'.esc_html__('Ad Title', 'lmb-core').'</label>
                    <input type="text" id="lmb_ad_title" name="lmb_ad_title" required>
                </div>

                <div class="lmb-form-group">
                    <label for="lmb_ad_type">'.esc_html__('Ad Type', 'lmb-core').'</label>
                    <select id="lmb_ad_type" name="lmb_ad_type" required>
                        <option value="">'.esc_html__('Select Type...', 'lmb-core').'</option>
                        <option value="Constitution - SARL">Constitution - SARL</option>
                        <option value="Constitution - SARL AU">Constitution - SARL AU</option>
                        <option value="Modification - Capital">Modification - Capital</option>
                        <option value="Modification - parts">Modification - Parts</option>
                        <option value="Modification - denomination">Modification - Denomination</option>
                        <option value="Modification - siege">Modification - Siège</option>
                        <option value="Modification - gerant">Modification - Gérant</option>
                        <option value="Modification - objects">Modification - Objects</option>
                        <option value="Liquidation - anticipee">Liquidation - Anticipée</option>
                        <option value="Liquidation - definitive">Liquidation - Définitive</option>
                    </select>
                </div>
                
                <div class="lmb-form-group">
                    <label for="lmb_full_text">'.esc_html__('Ad Content (HTML allowed)', 'lmb-core').'</label>
                    <textarea id="lmb_full_text" name="lmb_full_text" rows="12" required></textarea>
                </div>
                
                <button type="submit" class="lmb-submit-btn">'.esc_html__('Save as Draft', 'lmb-core').'</button>
            </form>
        </div>';
        
        echo '</div>';
    }
}