<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Subscribe_Package_Widget extends Widget_Base {
    public function get_name() { return 'lmb_subscribe_package'; }
    public function get_title() { return __('LMB Subscribe (Bank Transfer)','lmb-core'); }
    public function get_icon() { return 'eicon-price-table'; }
    public function get_categories(){ return ['general']; }

    protected function render() {
        if (!is_user_logged_in()) { echo '<p>'.esc_html__('Login required.','lmb-core').'</p>'; return; }

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1]);
        if (!$packages) { echo '<p>'.esc_html__('No packages.','lmb-core').'</p>'; return; }

        echo '<div class="lmb-packages">';
        foreach ($packages as $p) {
            $price   = get_post_meta($p->ID, 'price', true);
            $points  = get_post_meta($p->ID, 'points', true);
            $ad_cost = get_post_meta($p->ID, 'cost_per_ad', true);
            
            echo '<div class="lmb-package">';
            echo '<h3>'.esc_html($p->post_title).'</h3>';
            echo '<div class="desc">'.wp_kses_post($p->post_content).'</div>';
            echo '<div class="lmb-package-details">';
            echo '<div class="lmb-package-price">'.esc_html($price).' MAD</div>';
            echo '<div class="lmb-package-points">'.esc_html($points).' '.esc_html__('Points','lmb-core').'</div>';
            echo '<div class="lmb-package-cost">'.esc_html($ad_cost).' '.esc_html__('points per ad','lmb-core').'</div>';
            echo '</div>';

            // Clicking subscribe generates an invoice PDF with reference
            $url = add_query_arg([
                'lmb_subscribe' => 1,
                'pkg' => $p->ID,
                '_wpnonce' => wp_create_nonce('lmb_subscribe')
            ], home_url($_SERVER['REQUEST_URI']));

            echo '<div class="lmb-package-action">';
            echo '<a class="lmb-subscribe-btn" href="'.esc_url($url).'">'.esc_html__('Subscribe','lmb-core').'</a>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';

        // Handle subscribe click
        if (isset($_GET['lmb_subscribe'], $_GET['pkg']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'lmb_subscribe')) {
            $pkg = (int) $_GET['pkg'];
            $package = get_post($pkg);
            if (!$package) return;
            
            $price = get_post_meta($pkg, 'price', true);
            $details = $package->post_content;
            $ref  = 'BT-'.get_current_user_id().'-'.time();
            
            $pdf = LMB_Invoice_Handler::create_package_invoice(get_current_user_id(), $pkg, $price, $details, $ref);
            
            echo '<div class="lmb-invoice-generated">';
            echo '<h3>'.esc_html__('Invoice Generated','lmb-core').'</h3>';
            echo '<p>'.sprintf(__('Payment reference: %s','lmb-core'), '<strong>'.esc_html($ref).'</strong>').'</p>';
            echo '<p>'.esc_html__('Please use this reference when making your bank transfer.','lmb-core').'</p>';
            echo '<a target="_blank" href="'.esc_url($pdf).'" class="lmb-download-invoice">'.esc_html__('Download Invoice PDF','lmb-core').'</a>';
            echo '</div>';
        }
        
        ?>
        <style>
        .lmb-packages { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .lmb-package { border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: white; text-align: center; }
        .lmb-package h3 { margin-top: 0; color: #333; }
        .lmb-package-details { margin: 20px 0; }
        .lmb-package-price { font-size: 24px; font-weight: bold; color: #0073aa; margin-bottom: 10px; }
        .lmb-package-points, .lmb-package-cost { color: #666; margin-bottom: 5px; }
        .lmb-subscribe-btn { display: inline-block; padding: 12px 24px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .lmb-subscribe-btn:hover { background: #005a87; color: white; }
        .lmb-invoice-generated { background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 20px; margin: 20px 0; }
        .lmb-download-invoice { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }
        </style>
        <?php
    }
}
