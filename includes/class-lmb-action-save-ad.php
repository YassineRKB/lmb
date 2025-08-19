<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Elementor Pro Forms custom action: Save as Legal Ad
 */
class LMB_Save_Ad_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    public function get_name() {
        return 'save_legal_ad';
    }

    public function get_label() {
        return __('Save as Legal Ad', 'lmb-core');
    }

    public function run($record, $ajax_handler) {
        try {
            // Ensure Elementor Pro provides the fields structure
            $raw_fields = $record->get('fields');
            if (empty($raw_fields) || !is_array($raw_fields)) {
                if (class_exists('LMB_Error_Handler')) {
                    LMB_Error_Handler::log_error('Save Ad Action: empty or invalid fields payload');
                }
                $ajax_handler->add_error_message(__('No form data received.', 'lmb-core'));
                return;
            }

            // Flatten fields into key => value
            $form_data = [];
            foreach ($raw_fields as $key => $field) {
                // Elementor typically: ['value' => '...']
                if (is_array($field) && array_key_exists('value', $field)) {
                    $form_data[$key] = is_array($field['value']) ? implode(', ', $field['value']) : $field['value'];
                } else {
                    $form_data[$key] = is_array($field) ? json_encode($field) : $field;
                }
            }

            if (class_exists('LMB_Error_Handler')) {
                LMB_Error_Handler::log_error('Save Ad Action: parsed form data', ['data' => $form_data]);
            }

            // Prefer centralized creator if available
            if (class_exists('LMB_Form_Handler') && method_exists('LMB_Form_Handler', 'create_legal_ad')) {
                $post_id = LMB_Form_Handler::create_legal_ad($form_data);
            } else {
                // Fallback: create post directly here
                $user_id  = get_current_user_id();
                $ad_title = !empty($form_data['ad_title'])
                    ? sanitize_text_field($form_data['ad_title'])
                    : (!empty($form_data['company_name']) ? sanitize_text_field($form_data['company_name']) : __('Legal Ad', 'lmb-core'));

                $post_data = [
                    'post_type'    => 'lmb_legal_ad',
                    'post_title'   => $ad_title,
                    'post_status'  => 'draft',
                    'post_author'  => $user_id,
                    'post_content' => wp_kses_post($form_data['full_text'] ?? ''),
                ];

                $post_id = wp_insert_post($post_data, true);
                if (is_wp_error($post_id)) {
                    throw new \Exception($post_id->get_error_message());
                }

                // Save all fields as meta (prefixed) for traceability
                foreach ($form_data as $k => $v) {
                    if ($k === 'full_text') continue;
                    update_post_meta($post_id, '_lmb_' . sanitize_key($k), sanitize_text_field($v));
                }
                update_post_meta($post_id, 'lmb_status', 'draft');
                update_post_meta($post_id, 'lmb_client_id', $user_id);
            }

            if (class_exists('LMB_Error_Handler')) {
                LMB_Error_Handler::log_error('Save Ad Action: legal ad created', ['post_id' => $post_id]);
            }

            // Replies visible in Elementor UI
            $ajax_handler->add_response_data('post_id', (int) $post_id);
            $ajax_handler->add_success_message(__('Legal Ad saved as draft.', 'lmb-core'));

        } catch (\Throwable $e) {
            if (class_exists('LMB_Error_Handler')) {
                LMB_Error_Handler::log_error('Save Ad Action: exception', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            $ajax_handler->add_error_message(__('An error occurred while creating the Legal Ad.', 'lmb-core'));
        }
    }

    public function register_settings_section($form) {
        // No custom settings; action appears in "Actions After Submit"
    }

    public function on_export($element) {
        return $element; // nothing sensitive to strip
    }
}
