<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) exit;

class LMB_Form_Constitution_Sarl_Widget extends LMB_Form_Widget_Base {

    protected function get_form_name() {
        return 'constitution_sarl';
    }

    public function get_ad_type() {
        return 'Constitution - SARL';
    }
    
    // This is the key function to define the fields in the Elementor Editor
    protected function _register_controls() {

        $this->start_controls_section(
            'form_fields_section',
            [
                'label' => __('Form Fields', 'lmb-core'),
            ]
        );

        $this->add_control(
            'companyName',
            [
                'label' => __('Dénomination', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Dénomination', 'lmb-core'),
                'label_block' => true,
            ]
        );

        $this->add_control(
            'addrCompanyHQ',
            [
                'label' => __('Siège Social', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'placeholder' => __('Siège Social', 'lmb-core'),
                'label_block' => true,
            ]
        );

        // Add all other simple fields... (city, companyRC, companyCapital, etc.)

        // --- Repeater for Associates ---
        $repeater_assoc = new Repeater();

        $repeater_assoc->add_control(
            'assocName', [
                'label' => __('Nom complet', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );
        $repeater_assoc->add_control(
            'assocShares', [
                'label' => __('Nombre de Parts', 'lmb-core'),
                'type' => Controls_Manager::NUMBER,
            ]
        );
        $repeater_assoc->add_control(
            'assocAddr', [
                'label' => __("Address d' Associer", 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );
        
        $this->add_control(
            'repAssocEnd',
            [
                'label' => __('Associés', 'lmb-core'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater_assoc->get_controls(),
                'default' => [
                    [
                        'assocName' => 'John Doe',
                    ],
                ],
                'title_field' => '{{{ assocName }}}',
            ]
        );

        // --- Repeater for Gerants ---
        $repeater_gerant = new Repeater();
        $repeater_gerant->add_control(
            'nameGerant', [
                'label' => __('Nom complet', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );
        $repeater_gerant->add_control(
            'addrGerant', [
                'label' => __('Adresse', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $this->add_control(
            'repGerantEnd',
            [
                'label' => __('Gérants', 'lmb-core'),
                'type' => Controls_Manager::REPEATER,
                'fields' => $repeater_gerant->get_controls(),
                'title_field' => '{{{ nameGerant }}}',
            ]
        );
        
        $this->add_control(
            'submit_button_text',
            [
                'label' => __('Submit Button Text', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Create SARL Ad', 'lmb-core'),
            ]
        );


        $this->end_controls_section();
    }

    // This function renders the actual HTML of the form on the live page
    protected function render_form_fields() {
        $settings = $this->get_settings_for_display();
        
        // Render simple text fields...
        // This is a simplified example. You would create a loop or helper function for all your fields.
        ?>
        <div class="elementor-field-group elementor-column elementor-col-100">
            <label class="elementor-field-label"><?php echo esc_html($settings['companyName_label']); ?></label>
            <input type="text" name="form_fields_companyName" class="elementor-field elementor-size-md" placeholder="<?php echo esc_attr($settings['companyName_placeholder']); ?>">
        </div>
        
        <?php
        // Render Repeater Fields
        // This is a complex task and a full implementation would require JavaScript to handle adding/removing items on the frontend.
        // For a server-side only form, you might pre-define a fixed number of fields.
    }

    public function build_legal_text($data) {
        // This function remains the same, building text from the submitted data.
        $companyForme = 'SARL';
        $companyNature = 'à Responsabilité limitée SARL';
        
        // ... (rest of your text-building logic)
        
        $text = "AVIS DE CONSTITUTION DE SOCIETE\n\n";
        // ... build the full text using the $data array ...

        return $text;
    }
}