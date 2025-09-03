<?php
if (!defined('ABSPATH')) exit;

require_once LMB_CORE_PATH.'libraries/fpdf/fpdf.php';

// --- A new class that extends FPDF to handle UTF-8 and local images ---
class PDF_UTF8 extends FPDF {
    
    // Function to handle UTF-8 text conversion
    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        $txt = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $txt);
        parent::MultiCell($w, $h, $txt, $border, $align, $fill);
    }

    // A more advanced HTML parser that handles basic tags and local images
    function WriteHTML($html) {
        $html = str_replace("\n", ' ', $html);
        
        // Handle images - this is the key addition
        $html = preg_replace_callback(
            '/<img src="([^"]+)" width="(\d+)">/i',
            function($matches) {
                // When an image tag is found, we use the Image() method of FPDF
                // This correctly embeds the image from a local path.
                $imagePath = $matches[1];
                $width = $matches[2] / 3.78; // Convert px to mm
                if (file_exists($imagePath)) {
                    $this->Image($imagePath, $this->GetX(), $this->GetY(), $width);
                    $this->Ln(20); // Add some space after the image
                }
                return ''; // Return an empty string to remove the tag from the HTML flow
            },
            $html
        );

        $html = str_replace('<br>', "\n", $html);
        $html = str_replace('<br/>', "\n", $html);
        $html = str_replace('<hr>', "--------------------------------------------------\n", $html);
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        $html = strip_tags($html);

        $this->MultiCell(0, 5, $html);
    }
}


class LMB_PDF_Generator {
    public static function generate_html_pdf($filename, $html, $title='') {
        $pdf = new PDF_UTF8();
        $pdf->AddPage();
        $pdf->SetTitle($title);
        $pdf->SetFont('Arial','',12);
        
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
        
        return self::generate_html_pdf('ad-'.$post_id.'.pdf', $full_text, $title);
    }
}