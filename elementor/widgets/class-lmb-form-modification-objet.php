<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Modification_Objet_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'modification_objet';
    }

    protected function get_ad_type() {
        return 'Modification - Extension d\'Objet Social';
    }
    
    protected function register_form_controls(Widget_Base $widget) {
        // Define controls
    }

    public function build_legal_text($data) {
        $text = "AVIS DE MODIFICATION D'OBJET SOCIAL\n\n";
        $text .= ($data['companyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n";
        $text .= "Par décision de l'assemblée générale extraordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ",\n";
        $text .= "les associés ont décidé Changement d'objet social\n";
        $text .= "L'objet social de la société, qui était initialement de:\n";
        $text .= ($data['OldCompanyObjects'] ?? '...') . "\n";
        $text .= "est désormais de \n";
        $text .= ($data['NewCompanyObjects'] ?? '...') . "\n\n";
        $text .= "L'article 12 des statuts a été modifié en conséquence.\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", \n";
        $text .= "le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".";

        return $text;
    }
}