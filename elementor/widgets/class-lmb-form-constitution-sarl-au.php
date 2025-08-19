<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Constitution_Sarl_Au_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'constitution_sarl_au';
    }

    protected function get_ad_type() {
        return 'Constitution - SARL AU';
    }
    
    protected function register_form_controls(Widget_Base $widget) {
        // Define controls
    }

    public function build_legal_text($data) {
        $companyForme = 'SARL AU';
        $companyNature = 'à Responsabilité limitée d\'Associé Unique SARL.AU';

        // --- Gerants ---
        $gerantsText = '';
        if (!empty($data['repGerantEnd'])) {
             $gerants = json_decode(stripslashes($data['repGerantEnd']), true);
             foreach($gerants as $gerant) {
                $gerantsText .= "\n- " . ($gerant['nameGerant'] ?? '[Nom du Gérant]') . ", address " . ($gerant['addrGerant'] ?? '[adresse du gérant]');
             }
             if(count($gerants) > 1) {
                $gerantsText .= "\nLa société sera engager par LA SIGNATURE SEPARE DES CO-GERANTS";
             } else {
                $gerantsText .= "\nest désigner comme de gérant unique de la société et cette dernière sera engagée par sa signature unique";
             }
        }
        
        // --- Associate ---
        $associesText = "\n" . ($data['assocName'] ?? '[Nom Associé]') . " (" . ($data['assocAddr'] ?? '[Adresse]') . ") aver " . ($data['assocShares'] ?? '0') . " parts";

        $text = "AVIS DE CONSTITUTION DE SOCIETE\n\n";
        $text .= strtoupper($data['companyName'] ?? '[NOM DE LA SOCIETE]') . "\n";
        $text .= "SOCIETE A RESPONSABILITE LIMITEE A ASSOCIE UNIQUE\n\n";
        $text .= "AU CAPITAL DE : " . ($data['companyCapital'] ?? '...') . " DHS\n";
        $text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= ($data['city'] ?? '...') . "\n";
        $text .= "R.C : " . ($data['companyRC'] ?? '...') . "\n";
        $text .= "Aux termes d’un acte S.S.P à " . ($data['city'] ?? '...') . " en date du " . ($data['actDate'] ?? '...') . " a été établi les statuts d’une société " . $companyNature . " dont les caractéristiques sont les suivantes ;\n";
        $text .= "FORME : " . $companyForme . "\n";
        $text .= "DENOMINATION: " . ($data['companyName'] ?? '...') . "\n";
        $text .= "OBJET : " . ($data['companyObjects'] ?? '...') . "\n";
        $text .= "SIEGE SOCIAL: " . ($data['addrCompanyHQ'] ?? '...') . "\n";
        $text .= "DURÉE : " . ($data['actDuration'] ?? '99') . " ans\n";
        $text .= "CAPITAL SOCIAL: " . ($data['companyCapital'] ?? '...') . " DHS\n\n";
        $text .= "ASSOCIES :" . $associesText . "\n\n";
        $text .= "GÉRANCE:" . $gerantsText . "\n\n";
        $text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '...') . " de la ville " . ($data['courtCity'] ?? '...') . ", le " . ($data['courtDate'] ?? '...') . " sous le N° " . ($data['courtNum'] ?? '...') . ".\n";
        $text .= "Pour extrait et mention\n\nLE GERANT";

        return $text;
    }
}