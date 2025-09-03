<?php
if (!defined('ABSPATH')) exit;

require_once LMB_CORE_PATH . 'libraries/fpdf/fpdf.php';

// A helper class that extends FPDF to handle UTF-8 text properly.
class PDF_UTF8 extends FPDF {
    protected function _UTF8toISOLatin1($text) {
        try {
            return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        } catch (Exception $e) {
            return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
        }
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        parent::Cell($w, $h, $this->_UTF8toISOLatin1($txt), $border, $ln, $align, $fill, $link);
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        parent::MultiCell($w, $h, $this->_UTF8toISOLatin1($txt), $border, $align, $fill);
    }
}

// This new class reads an HTML template and draws it onto the PDF.
class LMB_Accuse_PDF_From_Template extends PDF_UTF8 {
    private $data = [];
    private $template_html = '';

    public function __construct($template_html, $data) {
        parent::__construct('P', 'mm', 'A4');
        $this->data = $data;
        $this->template_html = $template_html;
        $this->AddPage();
        $this->SetAutoPageBreak(true, 15);
    }
    
    // Main function to build the entire PDF from the template.
    public function BuildPDF() {
        // Draw Header
        if (file_exists($this->data['logo_path'])) {
            $this->Image($this->data['logo_path'], 15, 15, 45);
        }
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(40, 40, 40);
        $this->SetXY(0, 25);
        $this->Cell(200, 10, 'ACCUSE DE PUBLICATION', 0, 1, 'C');
        
        // Draw Main Content
        $this->SetY(50);
        $this->SetX(15);
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(50, 50, 50);
        $main_content = $this->_getSection('main');
        $this->MultiCell(180, 7, $main_content);

        // Draw Footer with QR Code and Signature
        $this->SetY(240);
        
        // QR Code on the left
        if (file_exists($this->data['qr_code_path'])) {
            $this->Image($this->data['qr_code_path'], 15, 240, 35);
            @unlink($this->data['qr_code_path']); // Clean up temp file
        }

        // Footer Text in the middle
        $this->SetY(245);
        $this->SetX(60);
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(100, 100, 100);
        $footer_text = $this->_getSection('footer');
        $this->MultiCell(90, 3.5, $footer_text, 0, 'C');

        // Signature on the right
        if (file_exists($this->data['signature_path'])) {
            $this->Image($this->data['signature_path'], 155, 235, 45, 0, 'PNG');
        }
    }

    // Helper to extract content from <div id="..."> sections in the template.
    private function _getSection($id) {
        $pattern = '/<div id="' . $id . '">(.*?)<\/div>/is';
        if (preg_match($pattern, $this->template_html, $matches)) {
            $content = trim($matches[1]);
            // Replace placeholders and clean up HTML for PDF output
            foreach ($this->data as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }
            return strip_tags(str_replace('<br>', "\n", $content));
        }
        return '';
    }
}

