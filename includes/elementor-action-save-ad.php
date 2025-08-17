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
        $form_fields = $record->get('fields');
        $processed_fields = [];
        foreach ($form_fields as $id => $field) {
            $processed_fields[$id] = $field['value'];
        }

        // --- IMPORTANT VALIDATION ---
        // Ensure your Elementor form fields have these exact IDs
        if (empty($processed_fields['ad_type']) || empty($processed_fields['full_text'])) {
            $ajax_handler->add_error_message('A required field is missing. Please check Ad Type and Full Text.');
            $ajax_handler->add_error_to_field('ad_type', 'This field is required.');
            $ajax_handler->add_error_to_field('full_text', 'This field is required.');
            return;
        }

        try {
            LMB_Form_Handler::create_legal_ad($processed_fields);
            // Optionally, redirect on success
            // $redirect_url = $record->get_form_settings('redirect_to');
            // if ($redirect_url) {
            //     $ajax_handler->add_response_data('redirect_url', $redirect_url);
            // }
        } catch (Exception $e) {
            $ajax_handler->add_error_message('Error: ' . $e->getMessage());
        }
    }

    public function register_settings_section($widget) {
        // You can add settings here if needed, but for now, it's automatic.
    }

    public function on_export($element) {
        // Not needed
    }
}