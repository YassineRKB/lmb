<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Modification_Siege_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'modification_siege';
    }

    protected function get_ad_type() {
        return 'Modification - Changement de Siège Social';
    }

    protected function register_form_controls(Widget_Base $widget) {
        // Define controls
    }
    
    public function build_legal_text($data) {
        $text = "AVIS DE TRANSFERT DE SIEGE SOCIAL\n\n";
        $text .= ($data['companyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['OldAddrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n";
        $text .= "Par décision de l'assemblée générale extraordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ",\n";
        $text .= "les associés ont décidé de ce qui suit :\n";
        $text .= "Transfert du siège social , Le siège social de la société est transféré de " . ($data['OldAddrCompanyHQ'] ?? '...') . " à " . ($data['NewAddrCompanyHQ'] ?? '...') . "\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".";

        return $text;
    }
}