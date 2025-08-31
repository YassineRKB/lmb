<?php
// FILE: includes/class-lmb-invoice-handler.php

if (!defined('ABSPATH')) exit;

class LMB_Invoice_Handler {
    public static function init() {
        // The AJAX action is already registered in the central handler
    }

    /**
     * Creates a new 'lmb_payment' post and generates a PDF invoice for it.
     * This is the new central function for creating invoices.
     */
    public static function create_invoice_for_package($user_id, $package_id) {
        $package = get_post($package_id);
        $user = get_userdata($user_id);

        if (!$package || $package->post_type !== 'lmb_package' || !$user) {
            return false;
        }

        // Create a unique reference for this payment attempt
        $reference = 'LMB-' . $user_id . '-' . time();
        $price = get_post_meta($package_id, 'price', true);

        // Create the payment post. This is our invoice record.
        $payment_id = wp_insert_post([
            'post_type'    => 'lmb_payment',
            'post_title'   => sprintf('Invoice %s for %s', $reference, $package->post_title),
            'post_status'  => 'publish', // CPT is not public, so 'publish' is safe
            'post_author'  => $user_id,
        ]);

        if (is_wp_error($payment_id)) {
            return false;
        }

        // Store all necessary data in the new payment post's meta
        update_post_meta($payment_id, 'user_id', $user_id);
        update_post_meta($payment_id, 'package_id', $package_id);
        update_post_meta($payment_id, 'payment_reference', $reference);
        update_post_meta($payment_id, 'package_price', $price);
        update_post_meta($payment_id, 'payment_status', 'pending'); // Initial status

        // Now, generate the PDF using the data from the payment post
        $pdf_url = self::generate_invoice_pdf($payment_id);
        
        return $pdf_url;
    }

    /**
     * Generates a PDF from an existing lmb_payment post ID.
     */
    public static function generate_invoice_pdf($payment_id) {
        $user_id = get_post_meta($payment_id, 'user_id', true);
        $package_id = get_post_meta($payment_id, 'package_id', true);
        $user = get_userdata($user_id);
        $package = get_post($package_id);

        if (!$user || !$package) return false;

        $payment_status = get_post_meta($payment_id, 'payment_status', true);
        $is_receipt = ($payment_status === 'approved');

        $vars = [
            'invoice_number'    => get_post_meta($payment_id, 'payment_reference', true),
            'invoice_date'      => get_the_date('Y-m-d', $payment_id),
            'user_name'         => $user->display_name,
            'user_email'        => $user->user_email,
            'package_name'      => $package->post_title,
            'package_price'     => get_post_meta($payment_id, 'package_price', true),
            'payment_reference' => get_post_meta($payment_id, 'payment_reference', true),
            'our_bank_name'     => get_option('lmb_bank_name', 'Your Bank Name'),
            'our_iban'          => get_option('lmb_bank_iban', 'Your IBAN/RIB'),
            'points_awarded'    => get_post_meta($package_id, 'points', true),
            'approval_date'     => get_post_meta($payment_id, 'approved_date', true) ?: current_time('mysql'),
        ];
        
        if ($is_receipt) {
            // Use the receipt template from options, with a fallback default.
            $template = get_option('lmb_receipt_template_html', '<h1>Receipt {{invoice_number}}</h1><p>Thank you for your payment of {{package_price}} MAD for package: {{package_name}}.</p><p>{{points_awarded}} points have been added to your account.</p>');
            $filename = 'receipt-' . $vars['invoice_number'] . '.pdf';
            $title = 'Receipt';
        } else {
            // Use the invoice template from options, with a fallback default.
            $template = get_option('lmb_invoice_template_html', '<h1>Invoice {{invoice_number}}</h1><p>Please pay {{package_price}} MAD for package: {{package_name}}.</p><p>Reference: {{payment_reference}}</p>');
            $filename = 'invoice-' . $vars['invoice_number'] . '.pdf';
            $title = 'Invoice';
        }

        foreach ($vars as $key => $value) {
            $template = str_replace('{{'.$key.'}}', esc_html($value), $template);
        }

        return LMB_PDF_Generator::generate_html_pdf($filename, $template, $title);
    }

    /**
     * Generates an "accuse de réception" PDF for a published legal ad.
     * This is called automatically when an ad is approved.
     */
    public static function generate_accuse_pdf($ad_id) {
        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            return false;
        }

        $user = get_userdata($ad->post_author);
        if (!$user) {
            return false;
        }

        $template = get_option('lmb_accuse_template_html', '<h1>Accuse de Réception</h1><p>Ad Réf: {{ad_id}}</p>');

        $client_name = get_user_meta($user->ID, 'company_name', true) ?: $user->display_name;

        // --- Placeholder replacements ---
        $vars = [
            'ad_id'            => $ad_id,
            'ad_title'         => $ad->post_title,
            'publication_date' => get_the_modified_date('Y-m-d', $ad_id),
            'client_name'      => $client_name,
            'client_email'     => $user->user_email,
            'ad_cost'          => get_post_meta($ad_id, 'ad_cost_points', true) ?: LMB_Points::get_cost_per_ad($user->ID),
        ];

        foreach ($vars as $key => $value) {
            $template = str_replace('{{'.$key.'}}', esc_html($value), $template);
        }

        $filename = 'accuse-ad-' . $ad_id . '.pdf';
        $title = 'Accuse de Réception - ' . $ad->post_title;

        return LMB_PDF_Generator::generate_html_pdf($filename, $template, $title);
    }
}