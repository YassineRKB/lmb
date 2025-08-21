<?php
if (!defined('ABSPATH')) exit;

require_once LMB_CORE_PATH.'libraries/fpdf/fpdf.php';

// --- CHANGE HERE: Create a new class that extends FPDF to handle UTF-8 ---
class PDF_UTF8 extends FPDF {
    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        // Convert UTF-8 text to a compatible format (ISO-8859-1)
        $txt = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt);
        parent::MultiCell($w, $h, $txt, $border, $align, $fill);
    }

    function WriteHTML($html) {
        // A very basic HTML parser that handles UTF-8
        $html = str_replace('<br>', "\n", $html);
        $html = str_replace('<br/>', "\n", $html);
        $html = str_replace('<hr>', "--------------------------------------------------\n", $html);
        // Decode HTML entities (like &#8211;) and then strip any remaining tags
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        $html = strip_tags($html);

        $this->MultiCell(0, 5, $html); // Use a smaller line height for better looks
    }
}


class LMB_PDF_Generator {
    public static function generate_html_pdf($filename, $html, $title='') {
        // --- CHANGE HERE: Use our new PDF_UTF8 class ---
        $pdf = new PDF_UTF8();
        $pdf->AddPage();
        $pdf->SetTitle($title);
        $pdf->SetFont('Arial','',12);
        
        // Use the new WriteHTML method
        $pdf->WriteHTML($html);
        
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']).'lmb-pdfs';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        $path = $dir.'/'.$filename;
        $pdf->Output('F', $path);
        
        return trailingslashit($upload['baseurl']).'lmb-pdfs/'.$filename;
    }

    public static function create_ad_pdf_from_fulltext($post_id) {
        $title = get_the_title($post_id);
        $full_text = get_post_meta($post_id, 'full_text', true);
        
        // The title is already part of the full_text, so we just use that.
        // This ensures the PDF matches the preview exactly.
        return self::generate_html_pdf('ad-'.$post_id.'.pdf', $full_text, $title);
    }
}
