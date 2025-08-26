<?php
if (!defined('ABSPATH')) exit;

class LMB_Invoice_Handler {
    public static function init() {
        add_action('wp_ajax_lmb_generate_package_invoice', [__CLASS__, 'ajax_generate_package_invoice']);
    }

    public static function ajax_generate_package_invoice() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!is_user_logged_in() || !isset($_POST['pkg_id'])) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }

        $pkg_id = intval($_POST['pkg_id']);
        $package = get_post($pkg_id);
        if (!$package || $package->post_type !== 'lmb_package') {
            wp_send_json_error(['message' => 'Invalid package.']);
        }

        $price = get_post_meta($pkg_id, 'price', true);
        $ref  = 'LMB-'.get_current_user_id().'-'.time();
        
        $pdf_url = self::create_package_invoice(get_current_user_id(), $pkg_id, $price, $package->post_content, $ref);
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Could not generate PDF invoice.']);
        }
    }
    
    public static function create_package_invoice($user_id, $package_id, $price, $details, $reference) {
        $user = get_userdata($user_id);
        $vars = [
            'invoice_number'   => 'PKG-'.time().'-'.$package_id,
            'user_name'        => $user->display_name,
            'package_name'     => get_the_title($package_id),
            'package_price'    => $price,
            'payment_reference'=> $reference,
        ];
        $html = self::render_template($vars);
        return LMB_PDF_Generator::generate_html_pdf('invoice-'.$reference.'.pdf', $html, 'Invoice');
    }

    private static function render_template($vars) {
        $template = get_option('lmb_invoice_template_html', '<h1>Invoice {{invoice_number}}</h1><p>Package: {{package_name}}</p><p>Price: {{package_price}}</p><p>Ref: {{payment_reference}}</p>');
        foreach ($vars as $key => $value) {
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }
        return $template;
    }
}