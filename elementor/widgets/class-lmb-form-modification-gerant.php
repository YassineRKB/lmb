<?php
use Elementor\Widget_Base;
if (!defined('ABSPATH')) exit;

class LMB_Form_Modification_Gerant_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'modification_gerant';
    }

    protected function get_ad_type() {
        return 'Modification - Changement de Gérant';
    }

    protected function register_form_controls(Widget_Base $widget) {
        // Define controls
    }
    
    public function build_legal_text($data) {
        // --- Build Old Gerants Text ---
        $oldGerantsText = '';
        $oldGerantRows = !empty($data['repAncientGerantEnd']) ? json_decode(stripslashes($data['repAncientGerantEnd']), true) : [];
        foreach($oldGerantRows as $gerant) {
            $oldGerantsText .= "\n- " . ($gerant['nameGerant'] ?? '...') . ", demeurant à " . ($gerant['addrGerant'] ?? '...');
        }
        $oldGeranceLegalText = count($oldGerantRows) > 1 ? "La société a ete engager par LA SIGNATURE SEPARE DES CO-GERANTS, Suite à la démission des co-gérants suivants :" : "La société a ete engager par LA SIGNATURE UNIQUE, Suite à la démission du gérant unique suivant :";

        // --- Build New Gerants Text ---
        $newGerantsText = '';
        $newGerantRows = !empty($data['repNewGerantEnd']) ? json_decode(stripslashes($data['repNewGerantEnd']), true) : [];
        foreach($newGerantRows as $gerant) {
            $newGerantsText .= "\n- " . ($gerant['nameGerant'] ?? '...') . ", demeurant à " . ($gerant['addrGerant'] ?? '...');
        }
        $newGeranceLegalText = count($newGerantRows) > 1 ? "Les co-gérants suivants ont été nommés en remplacement et La société sora engager par LA SIGNATURE SEPARE DES CO-GERANTS" : "Le gérant unique suivant a été nommé en remplacement et La société a ete engager par LA SIGNATURE UNIQUE";

        $text = "AVIS DE CHANGEMENT DE GÉRANCE\n\n";
        $text .= ($data['companyName'] ?? '...') . "\n\n";
        $text .= "FORM JURIDIQUE: " . ($data['formJuridique'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . ", " . ($data['city'] ?? '...') . "\n\n";
        $text .= "Par décision de l'assemblée générale extraordinaire en date de " . ($data['actDecisionDate'] ?? '...') . ", les associés ont décidé de ce qui suit :\n";
        $text .= "Changement de gérance:\n";
        $text .= $oldGeranceLegalText . $oldGerantsText . "\n";
        $text .= $newGeranceLegalText . $newGerantsText . "\n";
        $text .= "L'article 12 des statuts a été modifié en conséquence.\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".\n\n";
        $text .= "Pour extrait et mention\nLE GERANT";

        return $text;
    }
}