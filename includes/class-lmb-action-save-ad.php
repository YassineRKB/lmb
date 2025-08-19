<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class LMB_Save_Ad_Action extends \ElementorPro\Modules\Forms\Classes\Action_Base {

    /**
     * Get action name.
     *
     * @return string
     */
    public function get_name() {
        return 'save_legal_ad';
    }

    /**
     * Get action label.
     *
     * @return string
     */
    public function get_label() {
        return __('Save as Legal Ad', 'lmb-core');
    }

    /**
     * Run the action after form submission.
     *
     * @param \ElementorPro\Modules\Forms\Classes\Form_Record $record
     * @param \ElementorPro\Modules\Forms\Classes\Ajax_Handler $ajax_handler
     * @return void
     */
    public function run($record, $ajax_handler) {
        LMB_Error_Handler::log_error('LMB Save Ad Action triggered', [
            'action' => 'run',
            'user_id' => get_current_user_id(),
            'is_ajax' => wp_doing_ajax()
        ]);

        try {
            // Check if user is logged in
            if (!get_current_user_id()) {
                LMB_Error_Handler::log_error('User not logged in');
                $ajax_handler->add_error_message(__('You must be logged in to submit an ad.', 'lmb-core'));
                return;
            }

            // Get raw form fields
            $raw_fields = $record->get('fields');
            LMB_Error_Handler::log_error('Raw form fields received', ['fields' => $raw_fields]);

            if (empty($raw_fields)) {
                LMB_Error_Handler::log_error('No form fields received');
                $ajax_handler->add_error_message(__('No form data received.', 'lmb-core'));
                return;
            }

            $form_data = [];

            // Map form fields to data array - more flexible approach
            foreach ($raw_fields as $field_id => $field_data) {
                if (isset($field_data['value']) && !empty($field_data['value'])) {
                    $form_data[$field_id] = $field_data['value'];
                }
            }

            LMB_Error_Handler::log_error('Processed form data', ['form_data' => $form_data]);

            // Validate that we have some essential data
            if (empty($form_data)) {
                LMB_Error_Handler::log_error('No valid form data after processing');
                $ajax_handler->add_error_message(__('No valid form data received.', 'lmb-core'));
                return;
            }

            // Set a default ad_type if not provided
            if (empty($form_data['ad_type'])) {
                $form_data['ad_type'] = 'legal_ad';
            }

            // Set full_text from the largest text field if not set
            if (empty($form_data['full_text'])) {
                // Look for common text field names
                $text_fields = ['companyObjects', 'full_text', 'description', 'content', 'details'];
                foreach ($text_fields as $field) {
                    if (!empty($form_data[$field])) {
                        $form_data['full_text'] = $form_data[$field];
                        break;
                    }
                }
                
                // If still empty, use a default
                if (empty($form_data['full_text'])) {
                    $form_data['full_text'] = 'Legal advertisement content';
                }
            }

            LMB_Error_Handler::log_error('Final form data for processing', ['form_data' => $form_data]);

            // Create the legal ad
            $post_id = LMB_Form_Handler::create_legal_ad($form_data);
            
            LMB_Error_Handler::log_error('Legal ad created successfully', ['post_id' => $post_id]);

            $ajax_handler->add_response_data('message', __('Legal Ad saved successfully as draft.', 'lmb-core'));
            $ajax_handler->add_response_data('post_id', $post_id);
            
            // Add success message
            $ajax_handler->add_admin_error_message(__('Legal Ad saved successfully as draft. Post ID: ' . $post_id, 'lmb-core'));

        } catch (Exception $e) {
            LMB_Error_Handler::log_error('Exception in save legal ad action', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $ajax_handler->add_error_message(__('Failed to create Legal Ad: ', 'lmb-core') . $e->getMessage());
        } catch (Error $e) {
            LMB_Error_Handler::log_error('Fatal error in save legal ad action', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            $ajax_handler->add_error_message(__('A fatal error occurred while creating the Legal Ad.', 'lmb-core'));
        }
    }

    /**
     * Register settings section.
     *
     * @param \ElementorPro\Modules\Forms\Widgets\Form $form
     * @return void
     */
    public function register_settings_section($form) {
        // No settings needed for this action
    }

    /**
     * On export, remove sensitive data.
     *
     * @param array $element
     * @return array
     */
    public function on_export($element) {
        return $element;
    }
}