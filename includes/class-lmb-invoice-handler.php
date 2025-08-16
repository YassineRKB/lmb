<?php
if (!defined('ABSPATH')) { exit; }

class LMB_Invoice_Handler {
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'maybe_generate_invoice']);
        add_shortcode('lmb_invoice_link', [__CLASS__, 'shortcode_link']);
    }

    public static function shortcode_link() {
        if (!is_user_logged_in()) return '';
        $uid = get_current_user_id();
        $url = add_query_arg([
            'lmb-generate-invoice' => 'true',
            '_wpnonce' => wp_create_nonce('lmb_generate_invoice_' . $uid),
        ], home_url('/'));
        return '<a href="'.esc_url($url).'">'.esc_html__('Télécharger la facture de virement', 'lmb-core').'</a>';
    }

    public static function maybe_generate_invoice() {
        if (!isset($_GET['lmb-generate-invoice']) || $_GET['lmb-generate-invoice'] !== 'true') return;
        if (!is_user_logged_in()) {
            wp_redirect( wp_login_url() );
            exit;
        }
        $user_id = (int) get_current_user_id();
        $nonce   = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        if (!wp_verify_nonce($nonce, 'lmb_generate_invoice_' . $user_id)) {
            wp_die('Invalid request');
        }
        $invoice_id = uniqid('INV-');
        LMB_PDF_Generator::create_invoice_pdf($user_id, $invoice_id);
    }
}
LMB_Invoice_Handler::init();
