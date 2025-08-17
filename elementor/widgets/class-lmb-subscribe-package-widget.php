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

        // Handle invoice generation
        if (isset($_GET['lmb_get_invoice'], $_GET['pkg_id']) && wp_verify_nonce($_GET['_wpnonce'] ?? '', 'lmb_get_invoice_nonce')) {
            $this->generate_invoice_for_user();
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
            
            $url = add_query_arg([
                'lmb_get_invoice' => 1,
                'pkg_id' => $p->ID,
                '_wpnonce' => wp_create_nonce('lmb_get_invoice_nonce')
            ], get_permalink());

            echo '<div class="lmb-package-item">';
                echo '<h3 class="lmb-package-title">'.esc_html($p->post_title).'</h3>';
                echo '<div class="lmb-package-price"><span>'.esc_html($price).'</span> MAD</div>';
                echo '<div class="lmb-package-description">'.wp_kses_post($p->post_content).'</div>';
                echo '<ul class="lmb-package-features">';
                    echo '<li><strong>'.esc_html($points).'</strong> Points Included</li>';
                    echo '<li><strong>'.esc_html($ad_cost).'</strong> Points Per Ad</li>';
                echo '</ul>';
                echo '<div class="lmb-package-action">';
                    echo '<a class="lmb-btn lmb-btn-primary" href="'.esc_url($url).'">'.esc_html__('Get Invoice','lmb-core').'</a>';
                echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function generate_invoice_for_user() {
        $pkg_id = (int) $_GET['pkg_id'];
        $package = get_post($pkg_id);
        if (!$package || $package->post_type !== 'lmb_package') return;

        $price = get_post_meta($pkg_id, 'price', true);
        $details = $package->post_content;
        $ref  = 'LMB-'.get_current_user_id().'-'.time();
        
        // Generate the PDF and get its URL
        $pdf_url = LMB_Invoice_Handler::create_package_invoice(get_current_user_id(), $pkg_id, $price, $details, $ref);

        // Force download the generated PDF
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="invoice-'.$ref.'.pdf"');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        readfile(str_replace(site_url('/'), ABSPATH, $pdf_url));
        exit;
    }
}