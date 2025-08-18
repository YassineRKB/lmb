<?php
if (!defined('ABSPATH')) exit;

class LMB_Save_Ad_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    public function get_name() {
        return 'lmb_save_ad';
    }

    public function get_label() {
        return __('Save as Legal Ad', 'lmb-core');
    }

    public function run($record, $ajax_handler) {
        $data = $record->get_formatted_data();

        // --- SERVER-SIDE VALIDATION ---
        if (empty($data['ad_type'])) {
            $ajax_handler->add_error_message('A required field is missing: Ad Type.');
            $ajax_handler->add_error_to_field('ad_type', 'This field is required.');
            return;
        }

        // --- BUILD THE LEGAL TEXT ON THE SERVER ---
        $full_text = "AVIS DE CONSTITUTION DE SOCIETE\n\n";
        $full_text .= strtoupper($data['companyName'] ?? '') . "\n";
        $full_text .= "SOCIETE A RESPONSABILITE LIMITEE\n\n";
        $full_text .= "AU CAPITAL DE : " . number_format((float)($data['companyCapital'] ?? 0), 2, ',', ' ') . " DHS\n";
        $full_text .= "SIEGE SOCIAL : " . ($data['addrCompanyHQ'] ?? '') . "\n";
        $full_text .= ($data['city'] ?? '') . "\n";
        $full_text .= "R.C : " . ($data['companyRC'] ?? '') . "\n\n";
        $full_text .= "Aux termes d’un acte S.S.P à " . ($data['city'] ?? '') . " en date du " . ($data['actDate'] ?? '') . " a été établi les statuts d’une société à Responsabilité limitée SARL dont les caractéristiques sont les suivantes ;\n";
        $full_text .= "FORME : SARL\n";
        $full_text .= "DENOMINATION: " . ($data['companyName'] ?? '') . "\n";
        $full_text .= "OBJET : " . ($data['companyObjects'] ?? '') . "\n";
        $full_text .= "SIEGE SOCIAL: " . ($data['addrCompanyHQ'] ?? '') . "\n";
        $full_text .= "DURÉE : " . ($data['actDuration'] ?? '99') . " ans\n";
        $full_text .= "CAPITAL SOCIAL: " . number_format((float)($data['companyCapital'] ?? 0), 2, ',', ' ') . " DHS\n\n";

        // Handle Repeater Fields for Associates
        if (!empty($data['repAssocEnd'])) {
            $full_text .= "ASSOCIES :\n";
            $associates = explode("\n", trim($data['repAssocEnd']));
            $total_parts = 0;
            foreach ($associates as $assoc_line) {
                // Example line: "Associer Name: xawdawda, Nombre de Parts: 10000, Address d' Associer: dwkodkwok"
                $parts = explode(', ', $assoc_line);
                $name = str_replace('Associer Name: ', '', $parts[0] ?? '');
                $shares_str = str_replace('Nombre de Parts: ', '', $parts[1] ?? '0');
                $shares = (int)$shares_str;
                $address = str_replace("Address d' Associer: ", '', $parts[2] ?? '');
                $full_text .= "- " . trim($name) . " (Adresse: " . trim($address) . ") " . number_format($shares) . " Parts sociales\n";
                $total_parts += $shares;
            }
            $full_text .= "Soit au total : " . number_format($total_parts) . " parts\n\n";
        }
        
        // Handle Repeater Fields for Managers
        if (!empty($data['repGerantEnd'])) {
            $full_text .= "GÉRANCE:\n";
            $managers = explode("\n", trim($data['repGerantEnd']));
            foreach ($managers as $manager_line) {
                 // Example line: "Nom complete: dwokoko, ADDRESS: dw5313"
                $parts = explode(', ', $manager_line);
                $name = str_replace('Nom complete: ', '', $parts[0] ?? '');
                $address = str_replace('ADDRESS: ', '', $parts[1] ?? '');
                $full_text .= "- " . trim($name) . ", address " . trim($address) . "\n";
            }
            $full_text .= "est désigner comme de gérant unique de la société et cette dernière sera engagée par sa signature unique\n\n";
        }

        $full_text .= "Le dépôt légal a été effectué au " . ($data['court'] ?? '') . " de la ville " . ($data['courtCity'] ?? '') . ", le " . ($data['courtDate'] ?? '') . " sous le N° " . ($data['courtNum'] ?? '') . ".\n";
        $full_text .= "Pour extrait et mention\n\nLE GERANT";

        // --- Prepare data for the form handler ---
        $ad_data = [
            'ad_type'   => $data['ad_type'],
            'full_text' => $full_text,
            'title'     => !empty($data['title']) ? $data['title'] : $data['companyName'] // Use Company Name as title if 'title' field is absent
        ];

        try {
            LMB_Form_Handler::create_legal_ad($ad_data);
        } catch (Exception $e) {
            $ajax_handler->add_error_message('Error: ' . $e->getMessage());
        }
    }

    public function register_settings_section($widget) {
        // No settings needed for this action
    }

    public function on_export($element) {
        // No export handling needed
    }
}