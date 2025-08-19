<?php
use Elementor\Widget_Base;
if (!defined('ABSPATH')) exit;

class LMB_Form_Modification_Cession_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'modification_cession';
    }

    protected function get_ad_type() {
        return 'Modification - Cession des Parts';
    }

    protected function register_form_controls(Widget_Base $widget) {
        // Define Elementor controls here
    }

    public function build_legal_text($data) {
        $text = "AVIS DE CESSION DES PARTS\n\n";
        $text .= ($data['redationLibre'] ?? '...');

        return $text;
    }
}