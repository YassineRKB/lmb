<?php
if (!defined('ABSPATH')) { exit; }

class LMB_PDF_Generator {
    /**
     * Create a very simple PDF (vector-less) with given $title and $body (plain text).
     * This avoids external libraries while still producing a valid PDF.
     */
    public static function output_simple_pdf($filename, $title, $body) {
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $body);
        $y = 770;

        $objects = [];
        $pdf  = "%PDF-1.4\n";

        // 1. Catalog
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        // 2. Pages
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        // 3. Page
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
        // 4. Contents
        $content = "BT /F1 12 Tf 50 800 Td (".self::escape_pdf_text($title).") Tj ET\n";
        foreach ($lines as $line) {
            $content .= "BT /F1 11 Tf 50 $y Td (".self::escape_pdf_text($line).") Tj ET\n";
            $y -= 14;
            if ($y < 50) break; // prevent overflow (simple)
        }
        $objects[] = "<< /Length ".strlen($content)." >>\nstream\n".$content."endstream";
        // 5. Font
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i+1] = strlen($pdf);
            $pdf .= ($i+1)." 0 obj\n".$obj."\nendobj\n";
        }
        $xref_pos = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects)+1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i=1;$i<=count($objects);$i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer << /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".$xref_pos."\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $pdf;
        exit;
    }

    protected static function escape_pdf_text($text) {
        return str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $text);
    }

    public static function create_invoice_pdf($user_id, $invoice_id) {
        $user = get_userdata($user_id);
        $title = "Invoice (Bank Transfer Reference): ".$invoice_id;
        $body  = "User: ".$user->display_name." (".$user->user_email.")\n";
        $body .= "Date: ".date('Y-m-d H:i:s')."\n\n";
        $body .= "Please transfer the amount according to your package and include this reference in the transfer note:\n";
        $body .= $invoice_id."\n\n";
        $body .= "Thank you.";

        self::output_simple_pdf("invoice-".$invoice_id.".pdf", $title, $body);
    }

    public static function create_ad_pdf($post_id) {
        $owner = (int) get_post_meta($post_id, 'ad_owner', true);
        $user  = $owner ? get_userdata($owner) : null;
        $title = get_the_title($post_id);
        $full  = (string) get_post_meta($post_id, 'full_text', true);
        $header = "Ad ID: ".$post_id."\n";
        if ($user) {
            $header .= "Owner: ".$user->display_name." (".$user->user_email.")\n";
        }
        $header .= "Published: ".current_time('mysql')."\n\n";
        self::output_simple_pdf("ad-".$post_id.".pdf", $title, $header.$full);
    }
}
