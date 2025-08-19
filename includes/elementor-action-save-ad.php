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

        // Check if user is logged in
        if (!is_user_logged_in()) {
            $ajax_handler->add_error_message(__('You must be logged in to submit an ad.', 'lmb-core'));
            return;
        }

        // Validate required fields
        if (empty($data['ad_type'])) {
            $ajax_handler->add_error_message(__('Ad type is required.', 'lmb-core'));
            return;
        }

        // Get the full_text field directly from the form data
        $full_text = '';
        if (!empty($data['full_text'])) {
            $full_text = sanitize_textarea_field($data['full_text']);
        } else {
            // If no full_text field, try to build it from other fields
            $full_text = $this->build_legal_text_from_form_data($data);
        }

        if (empty($full_text)) {
            $ajax_handler->add_error_message(__('Legal ad content cannot be empty.', 'lmb-core'));
            return;
        }

        // Prepare data for creating the legal ad
        $ad_data = [
            'ad_type'   => sanitize_text_field($data['ad_type']),
            'full_text' => $full_text,
            'title'     => !empty($data['title']) ? sanitize_text_field($data['title']) : 
                          (!empty($data['companyName']) ? sanitize_text_field($data['companyName']) : 
                          sanitize_text_field($data['ad_type']) . ' - ' . date('Y-m-d'))
        ];

        try {
            $post_id = $this->create_legal_ad($ad_data);
            
            // Store additional form data as meta if needed
            $this->store_additional_form_data($post_id, $data);
            
            // Log the activity
            if (class_exists('LMB_Ad_Manager')) {
                LMB_Ad_Manager::log_activity(sprintf('Legal ad #%d created via Elementor form by %s', $post_id, wp_get_current_user()->display_name));
            }
            
        } catch (Exception $e) {
            $ajax_handler->add_error_message(__('Error: ', 'lmb-core') . $e->getMessage());
        }
    }

    /**
     * Create the legal ad post
     */
    private function create_legal_ad($ad_data) {
        $user_id = get_current_user_id();
        
        $post_data = [
            'post_type'    => 'lmb_legal_ad',
            'post_title'   => $ad_data['title'],
            'post_status'  => 'draft',
            'post_author'  => $user_id,
        ];

        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        // Save the meta data
        update_post_meta($post_id, 'ad_type', $ad_data['ad_type']);
        update_post_meta($post_id, 'full_text', $ad_data['full_text']);
        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);
        
        return $post_id;
    }

    /**
     * Store additional form data as post meta
     */
    private function store_additional_form_data($post_id, $form_data) {
        // Store all form fields as meta data for future reference
        $excluded_fields = ['ad_type', 'full_text', 'title'];
        
        foreach ($form_data as $field_name => $field_value) {
            if (!in_array($field_name, $excluded_fields) && !empty($field_value)) {
                update_post_meta($post_id, 'form_' . $field_name, sanitize_text_field($field_value));
            }
        }
    }

    /**
     * Build legal text from form data if full_text is not provided
     */
    private function build_legal_text_from_form_data($data) {
        // This is a fallback method to build legal text from individual form fields
        // You can customize this based on your form structure
        
        $text = '';
        
        if (!empty($data['companyName'])) {
            $text .= "AVIS DE CONSTITUTION DE SOCIETE\n\n";
            $text .= strtoupper($data['companyName']) . "\n";
            $text .= "SOCIETE A RESPONSABILITE LIMITEE\n\n";
            
            if (!empty($data['companyCapital'])) {
                $text .= "AU CAPITAL DE : " . number_format((float)$data['companyCapital'], 2, ',', ' ') . " DHS\n";
            }
            
            if (!empty($data['addrCompanyHQ'])) {
                $text .= "SIEGE SOCIAL : " . $data['addrCompanyHQ'] . "\n";
            }
            
            if (!empty($data['city'])) {
                $text .= $data['city'] . "\n";
            }
            
            if (!empty($data['companyRC'])) {
                $text .= "R.C : " . $data['companyRC'] . "\n\n";
            }
        } else {
            // Generic fallback
            $text = "Legal Ad Content\n\n";
            foreach ($data as $key => $value) {
                if (!empty($value) && $key !== 'ad_type') {
                    $text .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
                }
            }
        }
        
        return $text;
    }

    public function register_settings_section($widget) {
        $widget->start_controls_section(
            'lmb_save_ad_section',
            [
                'label' => __('Save as Legal Ad Settings', 'lmb-core'),
                'condition' => [
                    'submit_actions' => $this->get_name(),
                ],
            ]
        );

        $widget->add_control(
            'lmb_save_ad_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __('This action will save the form submission as a legal ad draft. Make sure your form includes an "ad_type" field and optionally a "full_text" field for the complete legal text.', 'lmb-core'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );
    }

        $widget->end_controls_section();
    public function on_export($element) {
        return $element;
    }
}