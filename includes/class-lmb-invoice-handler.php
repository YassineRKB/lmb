<?php
if (!defined('ABSPATH')) exit;

class LMB_Invoice_Handler {
    /**
     * Render invoice HTML from template with variables.
     */
    public static function render_template($vars = []) {
        $tpl = get_option('lmb_invoice_template_html', '');
        $replacements = [
            '{{invoice_number}}'  => $vars['invoice_number'] ?? '',
            '{{invoice_date}}'    => $vars['invoice_date'] ?? current_time('Y-m-d H:i'),
            '{{user_id}}'         => $vars['user_id'] ?? '',
            '{{user_name}}'       => $vars['user_name'] ?? '',
            '{{user_email}}'      => $vars['user_email'] ?? '',
            '{{package_name}}'    => $vars['package_name'] ?? '',
            '{{package_price}}'   => $vars['package_price'] ?? '',
            '{{package_details}}' => $vars['package_details'] ?? '',
            '{{payment_reference}}'=> $vars['payment_reference'] ?? '',
            '{{our_bank_name}}'   => $vars['our_bank_name'] ?? '',
            '{{our_iban}}'        => $vars['our_iban'] ?? '',
            '{{ad_id}}'           => $vars['ad_id'] ?? '',
            '{{ad_cost_points}}'  => $vars['ad_cost_points'] ?? '',
            '{{points_after}}'    => $vars['points_after'] ?? '',
        ];
        return strtr($tpl, $replacements);
    }

    public static function generate_invoice_pdf($filename, $vars) {
        $html = self::render_template($vars);
        return LMB_PDF_Generator::generate_html_pdf($filename, $html, 'Invoice '.$vars['invoice_number']);
    }

    /** Ad publication invoice (points deduction). */
    public static function create_ad_publication_invoice($user_id, $ad_id, $cost_points, $points_after) {
        $user = get_userdata($user_id);
        $vars = [
            'invoice_number'   => 'AD-'.time().'-'.$ad_id,
            'user_id'          => $user_id,
            'user_name'        => $user ? $user->display_name : ('User#'.$user_id),
            'user_email'       => $user ? $user->user_email : '',
            'ad_id'            => $ad_id,
            'ad_cost_points'   => $cost_points,
            'points_after'     => $points_after,
            // your bank defaults (admins can place them into template)
            'our_bank_name'    => get_option('lmb_bank_name', 'Your Bank'),
            'our_iban'         => get_option('lmb_bank_iban', 'YOUR-IBAN-RIB'),
        ];
        return self::generate_invoice_pdf('invoice-ad-'.$ad_id.'.pdf', $vars);
    }

    /** Package subscription invoice (bank transfer). */
    public static function create_package_invoice($user_id, $package_id, $price, $details, $reference) {
        $user = get_userdata($user_id);
        $vars = [
            'invoice_number'   => 'PKG-'.time().'-'.$package_id,
            'user_id'          => $user_id,
            'user_name'        => $user ? $user->display_name : ('User#'.$user_id),
            'user_email'       => $user ? $user->user_email : '',
            'package_name'     => get_the_title($package_id),
            'package_price'    => $price,
            'package_details'  => $details,
            'payment_reference'=> $reference,
            'our_bank_name'    => get_option('lmb_bank_name', 'Your Bank'),
            'our_iban'         => get_option('lmb_bank_iban', 'YOUR-IBAN-RIB'),
        ];
        return self::generate_invoice_pdf('invoice-pkg-'.$package_id.'-'.get_current_user_id().'.pdf', $vars);
    }
}
