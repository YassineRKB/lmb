<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Modification_Capital_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'modification_capital';
    }

    protected function get_ad_type() {
        return 'Modification - Augmentation de Capital';
    }

    protected function register_form_controls(Widget_Base $widget) {
        // Define Elementor controls here if needed for settings
    }

    public function build_legal_text($data) {
        $text = "AVIS D'AUGMENTATION DE CAPITAL SOCIAL\n\n";
        $text .= ($data['companyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['OldCompanyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n";
        $text .= "Par décision de l'assemblée générale extraordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ",\n";
        $text .= "les associés ont décidé d'augmenter le capital social, qui est porté de\n";
        $text .= ($data['OldCompanyCapital'] ?? '...') . " DHS à " . ($data['NewCompanyCapital'] ?? '...') . " DHS.\n";
        $text .= "L'article 12 des statuts a été modifié en conséquence.\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", \n";
        $text .= "le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".";

        return $text;
    }
}