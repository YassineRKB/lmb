<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Dissolution_Cloture_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'dissolution_cloture';
    }

    protected function get_ad_type() {
        return 'Dissolution - Clôture de Liquidation';
    }

    protected function register_form_controls(Widget_Base $widget) {
        // Define controls
    }

    public function build_legal_text($data) {
        $text = "Annonce Légale\nCLÔTURE DE LIQUIDATION\n\n";
        $text .= ($data['companyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n";
        $text .= "Aux termes d'une délibération de l'assemblée générale ordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ", la collectivité des associés a :\n";
        $text .= "approuvé les comptes définitifs de la liquidation, donné qui tus au Liquidateur, Monsieur " . ($data['companyLiquiditeur'] ?? '...') . " demeurant " . ($data['companyLiquiditeurHQ'] ?? '...') . " pour sa gestion et le décharge de son mandat, \n";
        $text .= "prononcé la clôture des opérations de liquidation à compter du jour de ladite Assemblée.\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".\n";
        $text .= "Radiation au R.C de " . ($data['RadiationRc'] ?? '...') . ".\n";
        $text .= "Pour avis et mention.\nLE GÉRANT";

        return $text;
    }
}