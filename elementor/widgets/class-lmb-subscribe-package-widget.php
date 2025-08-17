<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Subscribe_Package_Widget extends Widget_Base {
    public function get_name() { return 'lmb_subscribe_package'; }
    public function get_title() { return __('LMB Packages Pricing Table','lmb-core'); }
    public function get_icon() { return 'eicon-price-table'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to view packages.', 'lmb-core').'</p></div>';
            return;
        }

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1, 'orderby' => 'meta_value_num', 'meta_key' => 'price', 'order' => 'ASC']);
        
        if (!$packages) {
            echo '<p>'.esc_html__('No subscription packages are available at the moment.', 'lmb-core').'</p>';
            return;
        }

        echo '<div class="lmb-pricing-table">';
        foreach ($packages as $p) {
            $price = get_post_meta($p->ID, 'price', true);
            $points = get_post_meta($p->ID, 'points', true);
            $ad_cost = get_post_meta($p->ID, 'cost_per_ad', true);

            echo '<div class="lmb-package-item">';
                echo '<h3 class="lmb-package-title">'.esc_html($p->post_title).'</h3>';
                echo '<div class="lmb-package-price"><span>'.esc_html($price).'</span> MAD</div>';
                echo '<div class="lmb-package-description">'.wp_kses_post($p->post_content).'</div>';
                echo '<ul class="lmb-package-features">';
                    echo '<li><strong>'.esc_html($points).'</strong> Points Included</li>';
                    echo '<li><strong>'.esc_html($ad_cost).'</strong> Points Per Ad</li>';
                echo '</ul>';
                echo '<div class="lmb-package-action">';
                    // This button now has a class and data-attribute for our AJAX script to target
                    echo '<button class="lmb-btn lmb-btn-primary lmb-get-invoice-btn" data-pkg-id="'.esc_attr($p->ID).'">'.esc_html__('Get Invoice','lmb-core').'</button>';
                echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
}