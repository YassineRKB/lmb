<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Dissolution_Anticipee_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'dissolution_anticipee';
    }

    protected function get_ad_type() {
        return 'Dissolution - Dissolution Anticipée';
    }

    protected function register_form_controls(Widget_Base $widget) {
        // Define controls
    }

    public function build_legal_text($data) {
        $text = "Annonce Légale\nDissolution\n\n";
        $text .= ($data['companyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n";
        $text .= "Par décision de l'assemblée générale extraordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ",\n";
        $text .= "il a été décidé la dissolution anticipée de la société à compter du " . ($data['actLiquidationDate'] ?? '...') . " et sa mise en liquidation amiable.\n";
        $text .= ($data['companyLiquiditeur'] ?? '...') . " demeurant " . ($data['companyLiquiditeurHQ'] ?? '...') . " a été nommé en qualité de liquidateur.\n";
        $text .= "Les pouvoirs les plus étendus pour terminer les opérations sociales en cours, réaliser l'actif, acquitter le passif lui ont été confiées. Le siège de liquidation est fixé au " . ($data['companyLiquiditeurHQ'] ?? '...') . ", au même titre que l’adresse de correspondance.\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".";

        return $text;
    }
}