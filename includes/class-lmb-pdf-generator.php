<?php
if (!defined('ABSPATH')) exit;

require_once LMB_CORE_PATH.'libraries/fpdf/fpdf.php';

class LMB_PDF_Generator {
    public static function generate_html_pdf($filename, $html, $title='') {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetTitle($title);
        $pdf->SetFont('Arial','',12);
        
        // A very basic HTML parser
        $html = str_replace('<br>', "\n", $html);
        $html = str_replace('<br/>', "\n", $html);
        $html = str_replace('<hr>', "--------------------------------------------------\n", $html);
        $html = strip_tags($html); // Basic sanitization

        $pdf->MultiCell(0, 10, $html);
        
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
        
        $html_content = "<h1>{$title}</h1><hr><p>{$full_text}</p>";

        return self::generate_html_pdf('ad-'.$post_id.'.pdf', $html_content, $title);
    }
}