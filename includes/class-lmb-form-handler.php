<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        // Register our custom "Save as Legal Ad" action with Elementor Pro Forms
        add_action('elementor_pro/forms/actions/register', [__CLASS__, 'register_elementor_action']);
        
        // Add debugging hook to check if Elementor Pro is active
        add_action('admin_init', [__CLASS__, 'check_elementor_pro']);
    }

    public static function check_elementor_pro() {
        if (!did_action('elementor_pro/init')) {
            LMB_Error_Handler::log_error('Elementor Pro is not active or loaded', [
                'action' => 'check_elementor_pro',
                'elementor_pro_active' => class_exists('\ElementorPro\Plugin'),
                'elementor_active' => class_exists('\Elementor\Plugin')
            ]);
        }
    }

    public static function register_elementor_action($form_actions_registrar) {
        try {
            // Log registration attempt
            LMB_Error_Handler::log_error('Attempting to register Elementor action', [
                'registrar_class' => get_class($form_actions_registrar),
                'action' => 'register_elementor_action'
            ]);
            
            // This file contains the action's logic
            $action_file = LMB_CORE_PATH . 'includes/class-lmb-action-save-ad.php';
            if (!file_exists($action_file)) {
                LMB_Error_Handler::log_error('Action file not found', ['file' => $action_file]);
                return;
            }
            
            require_once $action_file;
            
            if (!class_exists('LMB_Save_Ad_Action')) {
                LMB_Error_Handler::log_error('LMB_Save_Ad_Action class not found after requiring file');
                return;
            }
            
            // The correct method in recent Elementor Pro versions is register()
            $action = new LMB_Save_Ad_Action();
            $form_actions_registrar->register($action);
            
            LMB_Error_Handler::log_error('Successfully registered Elementor action', [
                'action_name' => $action->get_name(),
                'action_label' => $action->get_label()
            ]);
            
        } catch (Exception $e) {
            LMB_Error_Handler::log_error('Failed to register Elementor action', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    // Centralized function to create the legal ad post from form data
    public static function create_legal_ad($form_data) {
        LMB_Error_Handler::log_error('Creating legal ad', ['form_data' => $form_data]);
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            LMB_Error_Handler::log_error('User not logged in when trying to create legal ad');
            $login_url = wp_login_url(get_permalink());
            $message = sprintf(__('You must be <a href="%s">logged in</a> to submit an ad.', 'lmb-core'), esc_url($login_url));
            throw new Exception($message);
        }

        $ad_title = !empty($form_data['title']) ? sanitize_text_field($form_data['title']) : sanitize_text_field($form_data['ad_type']) . ' - ' . wp_date('Y-m-d');

        $post_data = [
            'post_type'    => 'lmb_legal_ad',
            'post_title'   => $ad_title,
            'post_status'  => 'draft',
            'post_author'  => $user_id,
            // --- CHANGE HERE: Removed wp_kses_post to preserve HTML formatting ---
            'post_content' => $form_data['full_text'] ?? '',
        ];

        LMB_Error_Handler::log_error('About to insert post', ['post_data' => $post_data]);

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            LMB_Error_Handler::log_error('Failed to insert post', [
                'error' => $post_id->get_error_message(),
                'error_data' => $post_id->get_error_data()
            ]);
            throw new Exception($post_id->get_error_message());
        }

        LMB_Error_Handler::log_error('Successfully created post', ['post_id' => $post_id]);

        // Save custom fields from the form
        $meta_fields = [
            'ad_type' => sanitize_text_field($form_data['ad_type'] ?? ''),
            // --- Also save the raw HTML to the meta field for consistency ---
            'full_text' => $form_data['full_text'] ?? '',
            'lmb_status' => 'draft',
            'lmb_client_id' => $user_id,
        ];

        // Save all other form data as meta
        foreach ($form_data as $key => $value) {
            if ($key !== 'ad_type' && $key !== 'full_text' && $key !== 'title') {
                $meta_fields[$key] = is_array($value) ? $value : sanitize_text_field($value);
            }
        }

        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        LMB_Error_Handler::log_error('Successfully saved meta fields', [
            'post_id' => $post_id,
            'meta_count' => count($meta_fields)
        ]);
        
        self::log_activity('New legal ad #%d created as draft by %s', $post_id, wp_get_current_user()->display_name);
        
        return $post_id;
    }
    
    // Helper to prevent code duplication
    private static function log_activity($msg, ...$args) {
        if (class_exists('LMB_Ad_Manager')) {
            LMB_Ad_Manager::log_activity(vsprintf($msg, $args));
        }
    }
}
