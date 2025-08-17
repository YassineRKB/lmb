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
            echo '<p>'.sprintf(__('Price: %s | Points: %s | Cost/Ad: %s','lmb-core'), esc_html($price), esc_html($points), esc_html($ad_cost)).'</p>';

            // Clicking subscribe generates an invoice PDF with reference
            $url = add_query_arg([
                'lmb_subscribe' => 1,
                'pkg' => $p->ID,
                '_wpnonce' => wp_create_nonce('lmb_subscribe')
            ], home_url($_SERVER['REQUEST_URI']));

            echo '<p><a class="button button-primary" href="'.esc_url($url).'">'.esc_html__('Subscribe (Generate Invoice)','lmb-core').'</a></p>';
            echo '</div>';
        }
        echo '</div>';

        // Handle subscribe click
        if (isset($_GET['lmb_subscribe'], $_GET['pkg']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'lmb_subscribe')) {
            $pkg = (int) $_GET['pkg'];
            $price = get_post_meta($pkg, 'price', true);
            $details = get_post($pkg) ? get_post($pkg)->post_content : '';
            $ref  = 'BT-'.get_current_user_id().'-'.time();
            $pdf = LMB_Invoice_Handler::create_package_invoice(get_current_user_id(), $pkg, $price, $details, $ref);
            echo '<div class="lmb-notice">'.sprintf(__('Invoice generated. Reference: %s','lmb-core'), esc_html($ref)).' ';
            echo '<a target="_blank" href="'.esc_url($pdf).'">'.esc_html__('Download Invoice','lmb-core').'</a></div>';
        }
    }
}
