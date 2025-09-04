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


// The final, dedicated class for building the accuse PDF with a precise layout.
class LMB_Accuse_PDF extends PDF_UTF8 {
    private $data = [];
    protected $angle = 0; // Property to store the rotation angle

    public function __construct($data) {
        parent::__construct('P', 'mm', 'A4');
        $this->data = $data;
        $this->AddPage();
        $this->SetAutoPageBreak(false); // We control the footer manually
    }
    
    // --- METHODS FOR ROTATION ---
    function Rotate($angle, $x=-1, $y=-1) {
        if($x == -1)
            $x = $this->x;
        if($y == -1)
            $y = $this->y;
        if($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if($angle != 0) {
            $angle *= M_PI/180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage() {
        if($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
    // --- END ROTATION METHODS ---
    
    function Header() {
        // Blue top border
        $this->SetFillColor(45, 85, 155);
        $this->Rect(0, 0, 210, 10, 'F');

        // Logo on the Left
        if (file_exists($this->data['logo_path'])) {
            $this->Image($this->data['logo_path'], 15, 18, 50);
        }

        // Main Title on the Right
        $this->SetY(25);
        $this->SetX(-15);
        $this->SetFont('Arial', 'B', 20);
        $this->SetTextColor(40, 40, 40);
        $this->Cell(0, 10, 'ACCUSE DE PUBLICATION', 0, 1, 'R');
        
        // Date Subtitle on the Right
        $this->SetX(-15);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 7, 'Date de Publication: ' . $this->data['publication_date'], 0, 1, 'R');
        
        // Header bottom line
        $this->SetDrawColor(220, 220, 220);
        $this->Line(15, 50, 195, 50);
    }
    
    function Footer() {
        $this->SetY(-35); // Position 35mm from the bottom
        $this->SetFont('Arial', '', 7);
        $this->SetTextColor(100, 100, 100);
        
        $footerText = "Directeur de publication : MOHAMED ELBACHIR LANSAR | License : 2022/23/01ص\n" .
                      "Adresse : RUE AHL LKHALIL OULD MHAMED N°08 ES-SEMARA\n" .
                      "ICE : 002924841000097 | TP : 77402556 | IF : 50611382 | CNSS : 4319969\n" .
                      "RIB : 007260000899200000033587\n" .
                      "lmbannonceslegales.com | ste.lmbgroup@gmail.com\n" .
                      "06 61 83 82 11 | 06 74 40 61 97 | 06 05 28 98 04 | 08 08 61 04 87";
        
        $this->MultiCell(0, 3.5, $footerText, 0, 'C');
    }

    public function BuildPDF() {
        $this->DrawMainContent();
        $this->DrawSignatureAndQR();
    }

    // --- MODIFIED FUNCTION ---
    // Draws the main block of text with bold values.
    function DrawMainContent() {
        $this->SetY(65);
        $this->SetX(20);
        $this->SetTextColor(50, 50, 50);
        $lineHeight = 7;
        $labelWidth = 45; // Fixed width for the labels

        // Client Info
        //
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(0, $lineHeight, $this->data['client_value']);

        // Company Name
        $this->SetX(20);
        $this->SetFont('Arial', '', 11);
        $this->Cell($labelWidth, $lineHeight, "Societe / Nom:");
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(0, $lineHeight, $this->data['companyName']);

        // Object
        $this->SetX(20);
        $this->SetFont('Arial', '', 11);
        $this->Cell($labelWidth, $lineHeight, "Objet:");
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(0, $lineHeight, "Avis de " . $this->data['ad_object']);

        // Ad Ref
        $this->SetX(20);
        $this->SetFont('Arial', '', 11);
        $this->Cell($labelWidth, $lineHeight, "Ref. Annonce:");
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(0, $lineHeight, $this->data['ad_id']);

        // Journal No
        $this->SetX(20);
        $this->SetFont('Arial', '', 11);
        $this->Cell($labelWidth, $lineHeight, "Journal N:");
        $this->SetFont('Arial', 'B', 11);
        $this->MultiCell(0, $lineHeight, $this->data['journal_no']);

        $this->Ln(5);

        // Link
        $this->SetX(20);
        $this->SetFont('Arial', '', 11);
        $this->SetTextColor(50, 50, 50); // Reset text color
        $this->Cell(0, $lineHeight, "Consulter Votre Annonce:", 0, 1);
        $this->SetX(20);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(45, 85, 155); // Blue color for link
        $this->Cell(0, $lineHeight, $this->data['legal_ad_link'], 0, 1, 'L', false, $this->data['legal_ad_link']);
    }
    
    // --- MODIFIED FUNCTION ---
    // Draws the QR code and the rotated signature.
    function DrawSignatureAndQR() {
        // QR Code on the left
        if (file_exists($this->data['qr_code_path'])) {
            $this->Image($this->data['qr_code_path'], 20, 180, 40);
            @unlink($this->data['qr_code_path']);
        }

        // Signature rotated in the center
        if (file_exists($this->data['signature_path'])) {
            $imageWidth = 40;
            $centerX = 105 - ($imageWidth / 2); 
            $centerY = 135;

            $this->Rotate(20, $centerX, $centerY);
            $this->Image($this->data['signature_path'], $centerX, $centerY, $imageWidth, 0, 'PNG');
            $this->Rotate(0);
        }
    }
}

