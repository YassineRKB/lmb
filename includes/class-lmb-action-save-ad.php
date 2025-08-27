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
            $raw_fields = $record->get('fields');
            if (empty($raw_fields)) {
                throw new \Exception('Form data is empty.');
            }

            $form_data = [];
            $post_data = $_POST['form_fields'] ?? [];

            // 1. Get all simple fields from the record.
            foreach ($raw_fields as $key => $field) {
                if (isset($field['value']) && !is_array($field['value'])) {
                    $form_data[strtolower($key)] = $field['value'];
                }
            }

            // --- START: NEW UPGRADED LOGIC ---

            // 2. Check if this is the special "Gérance" form by looking for its unique repeaters.
            if (isset($post_data['repAncientGerantEnd']) || isset($post_data['repNewGerantEnd'])) {
                
                // Process the "Ancient Gerants" repeater specifically
                if (isset($post_data['nameGerant']) && isset($raw_fields['repAncientGerantEnd']['raw_value'])) {
                    $repeater_json = json_decode($raw_fields['repAncientGerantEnd']['raw_value'], true);
                    if (is_array($repeater_json) && !empty($repeater_json['id'])) {
                        $form_data['ancient_gerants'] = [];
                        foreach ($repeater_json['id'] as $item_id) {
                            if (isset($post_data['nameGerant'][$item_id])) {
                                $form_data['ancient_gerants'][] = [
                                    'namegerant' => $post_data['nameGerant'][$item_id],
                                    'addrgerant' => $post_data['addrGerant'][$item_id] ?? '',
                                ];
                            }
                        }
                    }
                }

                // Process the "New Gerants" repeater specifically
                if (isset($post_data['nameGerant']) && isset($raw_fields['repNewGerantEnd']['raw_value'])) {
                    $repeater_json = json_decode($raw_fields['repNewGerantEnd']['raw_value'], true);
                    if (is_array($repeater_json) && !empty($repeater_json['id'])) {
                        $form_data['new_gerants'] = [];
                        foreach ($repeater_json['id'] as $item_id) {
                            if (isset($post_data['nameGerant'][$item_id])) {
                                $form_data['new_gerants'][] = [
                                    'namegerant' => $post_data['nameGerant'][$item_id],
                                    'addrgerant' => $post_data['addrGerant'][$item_id] ?? '',
                                ];
                            }
                        }
                    }
                }

            } else {
                // 3. Use the original logic for all other forms (like Constitution SARL).
                if (isset($post_data['assocName']) && is_array($post_data['assocName'])) {
                    $form_data['associates'] = [];
                    $assoc_names = array_values($post_data['assocName']);
                    $assoc_shares = array_values($post_data['assocShares']);
                    $assoc_addrs = array_values($post_data['assocAddr']);

                    foreach ($assoc_names as $index => $name) {
                        $form_data['associates'][] = [ 'assocname' => $name, 'assocshares' => $assoc_shares[$index] ?? '', 'assocaddr' => $assoc_addrs[$index] ?? '' ];
                    }
                }
                if (isset($post_data['nameGerant']) && is_array($post_data['nameGerant'])) {
                    $form_data['gerants'] = [];
                    $gerant_names = array_values($post_data['nameGerant']);
                    $gerant_addrs = array_values($post_data['addrGerant']);
                    foreach ($gerant_names as $index => $name) {
                        $form_data['gerants'][] = [ 'namegerant' => $name, 'addrgerant' => $gerant_addrs[$index] ?? '' ];
                    }
                }
            }

            // --- END: NEW UPGRADED LOGIC ---

            if (class_exists('LMB_Error_Handler')) {
                LMB_Error_Handler::log_error('Final Processed Form Data', ['form_data' => $form_data]);
            }
            
            if (class_exists('LMB_Form_Handler')) {
                $post_id = LMB_Form_Handler::create_legal_ad($form_data);
                $ajax_handler->add_success_message(__('Legal Ad saved as draft.', 'lmb-core'));
                $ajax_handler->add_response_data('post_id', (int) $post_id);
            } else {
                throw new \Exception('LMB_Form_Handler class not found.');
            }

        } catch (\Throwable $e) {
            if (class_exists('LMB_Error_Handler')) {
                LMB_Error_Handler::log_error('Save Ad Action Exception', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
            $ajax_handler->add_error_message(__('An error occurred while saving the ad.', 'lmb-core'));
        }
    }

    public function register_settings_section($form) {
        // No custom settings; action appears in "Actions After Submit"
    }

    public function on_export($element) {
        return $element; // nothing sensitive to strip
    }
}
