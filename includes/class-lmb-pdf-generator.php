<?php
if (!defined('ABSPATH')) exit;

require_once LMB_CORE_PATH.'libraries/fpdf/fpdf.php';

/**
 * Tiny HTML→FPDF bridge supporting basic tags:
 * <b>, <i>, <u>, <a href>, <p>, <br>, <h1..h3>, <ul>, <ol>, <li>, <table>/<tr>/<td> (simple), <hr>
 */
class LMB_HTML_PDF extends FPDF {
    protected $B = 0; protected $I = 0; protected $U = 0;
    protected $HREF = '';

    function Header() {}
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10, sprintf(__('Page %s/{nb}', 'lmb-core'), $this->PageNo()),0,0,'C');
    }

    function WriteHTML($html) {
        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace(['<br>', '<br/>', '<br />'], "<br />", $html);
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach($a as $i => $e) {
            if($i % 2 == 0) {
                if($this->HREF) $this->PutLink($this->HREF, html_entity_decode($e));
                else $this->Write(5, html_entity_decode($e));
            } else {
                if($e[0]=='/') $this->CloseTag(strtoupper(substr($e,1)));
                else {
                    $arr = explode(' ', $e);
                    $tag = strtoupper(array_shift($arr));
                    $attr = [];
                    foreach($arr as $v){
                        if(preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $m)){
                            $attr[strtoupper($m[1])] = $m[2];
                        }
                    }
                    $this->OpenTag($tag, $attr);
                }
            }
        }
    }

    function OpenTag($tag, $attr){
        switch($tag){
            case 'B': $this->SetStyle('B', true); break;
            case 'I': $this->SetStyle('I', true); break;
            case 'U': $this->SetStyle('U', true); break;
            case 'A': $this->HREF = isset($attr['HREF']) ? $attr['HREF'] : ''; break;
            case 'BR': $this->Ln(5); break;
            case 'P': $this->Ln(8); break;
            case 'H1': $this->Ln(6); $this->SetFont('Arial','B',18); break;
            case 'H2': $this->Ln(6); $this->SetFont('Arial','B',16); break;
            case 'H3': $this->Ln(6); $this->SetFont('Arial','B',14); break;
            case 'UL': case 'OL': $this->Ln(3); break;
            case 'LI': $this->Cell(5,5, chr(149)); break;
            case 'HR': $this->Ln(3); $x=$this->GetX(); $y=$this->GetY(); $this->Line($x, $y, $x+180, $y); $this->Ln(3); break;
            case 'TABLE': $this->Ln(3); break;
            case 'TR': $this->Ln(5); break;
            case 'TD': $w = 60; $this->Cell($w,6,'',0); $this->SetX($this->GetX()-$w); break;
        }
    }

    function CloseTag($tag){
        switch($tag){
            case 'B': $this->SetStyle('B', false); break;
            case 'I': $this->SetStyle('I', false); break;
            case 'U': $this->SetStyle('U', false); break;
            case 'A': $this->HREF=''; break;
            case 'H1': case 'H2': case 'H3':
                $this->SetFont('Arial','',12);
                break;
        }
    }

    function SetStyle($tag, $enable){
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach(['B','I','U'] as $s){ if($this->$s>0) $style .= $s; }
        $this->SetFont('Arial', $style, 12);
    }

    function PutLink($URL, $txt){
        $this->SetTextColor(0,0,255);
        $this->SetStyle('U', true);
        $this->Write(5, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }
}

class LMB_PDF_Generator {
    public static function generate_html_pdf($filename, $html, $title='') {
        $pdf = new LMB_HTML_PDF();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetTitle($title ?: 'Document', true);
        $pdf->SetFont('Arial','',12);
        $pdf->WriteHTML($html);
        $upload = wp_upload_dir();
        $dir = trailingslashit($upload['basedir']).'lmb-pdfs';
        if (!file_exists($dir)) wp_mkdir_p($dir);
        $path = $dir.'/'.$filename;
        $pdf->Output('F', $path);
        return trailingslashit($upload['baseurl']).'lmb-pdfs/'.$filename;
    }

    public static function create_ad_pdf_from_fulltext($post_id) {
        $title = get_the_title($post_id);
        $full  = (string) get_post_meta($post_id, 'full_text', true);
        $header = '<h2>'.esc_html($title).'</h2>'.
                  '<p><strong>'.__('Ad ID','lmb-core').':</strong> '.intval($post_id).'</p>'.
                  '<hr />';
        return self::generate_html_pdf('ad-'.$post_id.'.pdf', $header.$full, $title);
    }
}
