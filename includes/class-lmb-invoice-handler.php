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

        $template = get_option('lmb_accuse_template_html', 'Template not found.');

        // Get Journal Number (check for final, then fallback to temporary)
        $journal_no = '';
        $final_journal_id = get_post_meta($ad_id, 'lmb_final_journal_id', true);
        $temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
        if ($final_journal_id) {
            $journal_no = get_post_meta($final_journal_id, 'journal_no', true);
        } elseif ($temp_journal_id) {
            $journal_no = get_post_meta($temp_journal_id, 'journal_no', true);
        }

        if (empty($journal_no)) {
            return false; 
        }

        $ad_object = get_post_meta($ad_id, 'ad_type', true);
        $announces_page = get_page_by_path('announces');
        $legal_ad_link = $announces_page ? add_query_arg('legal-ad', $ad->ID . '-' . $ad->post_name, get_permalink($announces_page)) : home_url();

        // --- START: NEW LOGIC TO CONVERT URLS TO PATHS ---
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];

        $logo_url = get_option('lmb_logo_url');
        $signature_url = get_option('lmb_signature_url');

        // Convert URL to a server path
        $logo_path = str_replace($base_url, $base_dir, $logo_url);
        $signature_path = str_replace($base_url, $base_dir, $signature_url);
        // --- END: NEW LOGIC ---

        // --- Placeholder replacements ---
        $vars = [
            // Use the new local paths for the images
            'lmb_logo_url'    => $logo_path,
            'signature_url'   => $signature_path,
            // Other variables remain the same
            'journal_no'      => $journal_no,
            'ad_object'       => $ad_object,
            'legal_ad_link'   => $legal_ad_link,
        ];

        foreach ($vars as $key => $value) {
            $template = str_replace('{{'.$key.'}}', esc_html($value), $template);
        }

        $filename = 'accuse-ad-' . $ad_id . '.pdf';
        $title = 'Accuse de Publication - ' . $ad->post_title;

        return LMB_PDF_Generator::generate_html_pdf($filename, $template, $title);
    }
}