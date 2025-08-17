<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        // Register our custom "Save as Legal Ad" action with Elementor Pro Forms
        add_action('elementor_pro/forms/actions/register', [__CLASS__, 'register_elementor_action']);
    }

    public static function register_elementor_action($form_actions_registrar) {
        // This file contains the action's logic
        require_once LMB_CORE_PATH . 'includes/elementor-action-save-ad.php';
        $form_actions_registrar->add_action_instance(new LMB_Save_Ad_Action());
    }

    // Centralized function to create the legal ad post from form data
    public static function create_legal_ad($form_data) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            throw new Exception(__('You must be logged in to submit an ad.', 'lmb-core'));
        }

        $ad_title = !empty($form_data['title']) ? sanitize_text_field($form_data['title']) : sanitize_text_field($form_data['ad_type']) . ' - ' . wp_date('Y-m-d');

        $post_id = wp_insert_post([
            'post_type'    => 'lmb_legal_ad',
            'post_title'   => $ad_title,
            'post_status'  => 'draft',
            'post_author'  => $user_id,
        ], true);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        // Save custom fields from the form
        update_post_meta($post_id, 'ad_type', sanitize_text_field($form_data['ad_type']));
        update_post_meta($post_id, 'full_text', wp_kses_post($form_data['full_text'])); // Preserve HTML
        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);
        
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