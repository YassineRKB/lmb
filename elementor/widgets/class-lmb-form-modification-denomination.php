<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Modification_Denomination_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'modification_denomination';
    }

    protected function get_ad_type() {
        return 'Modification - Changement de Dénomination';
    }
    
    protected function register_form_controls(Widget_Base $widget) {
        // Define Elementor controls here
    }

    public function build_legal_text($data) {
        $text = "AVIS DE CHANGEMENT DE DENOMINATION\n\n";
        $text .= ($data['AncientCompanyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n";
        $text .= "Par décision de l'assemblée générale extraordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ",\n";
        $text .= "les associés ont décidé de ce qui suit :\n";
        $text .= "Changement de la dénomination sociale, La dénomination sociale de la société " . ($data['AncientCompanyName'] ?? '...') . " est changée par : " . ($data['NewCompanyName'] ?? '...') . "\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".";

        return $text;
    }
}