<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        // Elementor form hooks
        add_action('elementor_pro/forms/new_record', [__CLASS__, 'handle_elementor_form'], 10, 2);
        
        // Fallback hooks
        add_action('admin_post_nopriv_lmb_submit_ad', [__CLASS__, 'submit_ad']);
        add_action('admin_post_lmb_submit_ad', [__CLASS__, 'submit_ad']);
        
        // AJAX hooks for form interception
        add_action('wp_ajax_elementor_pro_forms_send_form', [__CLASS__, 'intercept_elementor_form'], 5);
        add_action('wp_ajax_nopriv_elementor_pro_forms_send_form', [__CLASS__, 'intercept_elementor_form'], 5);
    }
    
    /**
     * Handle Elementor Pro form submissions
     */
    public static function handle_elementor_form($record, $handler) {
        $form_name = $record->get_form_settings('form_name');
        $raw_fields = $record->get('fields');
        
        // Check if this is an LMB form by looking for ad_type field
        $fields = [];
        foreach ($raw_fields as $id => $field) {
            $fields[$field['id']] = $field['value'];
        }
        
        if (isset($fields['ad_type'])) {
            self::process_lmb_form($fields, $record);
        }
    }
    
    /**
     * Intercept Elementor form AJAX calls
     */
    public static function intercept_elementor_form() {
        if (isset($_POST['form_fields']['ad_type'])) {
            self::process_lmb_form($_POST['form_fields']);
        }
    }
    
    /**
     * Process LMB form submission
     */
    public static function process_lmb_form($fields, $record = null) {
        if (!is_user_logged_in()) {
            LMB_Error_Handler::log_error('Unauthorized form submission attempt');
            return;
        }
        
        try {
            $validated_data = self::validate_form_data($fields);
            $post_id = self::create_legal_ad($validated_data);
            
            // Log activity
            LMB_Ad_Manager::log_activity(sprintf(
                'Legal ad #%d submitted by user %s',
                $post_id,
                wp_get_current_user()->user_login
            ));
            
        } catch (Exception $e) {
            LMB_Error_Handler::handle_form_error($e, $fields);
            if ($record) {
                $record->add_error('submission_error', $e->getMessage());
            }
        }
    }
    
    /**
     * Validate form data
     */
    public static function validate_form_data($fields) {
        $allowed_ad_types = [
            'Liquidation - definitive',
            'Liquidation - anticipee',
            'Constitution - SARL',
            'Constitution - SARL AU',
            'Modification - Capital',
            'Modification - parts',
            'Modification - denomination',
            'Modification - seige',
            'Modification - gerant',
            'Modification - objects'
        ];
        
        $ad_type = sanitize_text_field($fields['ad_type'] ?? '');
        $full_text = wp_kses_post($fields['full_text'] ?? ''); // Keep HTML formatting
        
        if (empty($ad_type) || !in_array($ad_type, $allowed_ad_types)) {
            throw new Exception(__('Invalid ad type.', 'lmb-core'));
        }
        
        if (empty($full_text) || strlen($full_text) < 50) {
            throw new Exception(__('Ad content is too short.', 'lmb-core'));
        }
        
        return [
            'ad_type' => $ad_type,
            'full_text' => $full_text,
            'title' => sanitize_text_field($fields['title'] ?? $ad_type . ' - ' . current_time('Y-m-d'))
        ];
    }
    
    /**
     * Create legal ad post
     */
    public static function create_legal_ad($data) {
        $user_id = get_current_user_id();
        
        $post_id = wp_insert_post([
            'post_type' => 'lmb_legal_ad',
            'post_title' => $data['title'],
            'post_status' => 'draft',
            'post_author' => $user_id,
        ], true);
        
        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }
        
        // Save ACF fields
        update_field('ad_type', $data['ad_type'], $post_id);
        update_field('full_text', $data['full_text'], $post_id);
        update_field('lmb_status', 'draft', $post_id);
        update_field('lmb_client_id', $user_id, $post_id);
        
        return $post_id;
    }

    /**
     * User submits a Legal Ad:
     * - store full HTML in 'full_text' (NO stripping)
     * - set status 'draft' initially (user can publish later from dashboard widget)
     */
    public static function submit_ad() {
        if (!is_user_logged_in()) wp_die('Auth required.');

        check_admin_referer('lmb_submit_ad');

        $user_id   = get_current_user_id();
        $title     = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $ad_type   = isset($_POST['ad_type']) ? sanitize_text_field(wp_unslash($_POST['ad_type'])) : '';
        $full_html = isset($_POST['full_text']) ? wp_kses_post(wp_unslash($_POST['full_text'])) : '';

        // Create post in draft
        $post_id = wp_insert_post([
            'post_type'   => 'lmb_legal_ad',
            'post_title'  => $title ?: ('Ad by user '.$user_id),
            'post_status' => 'draft',
            'meta_input'  => [
                'lmb_client_id' => $user_id,
            ]
        ], true);

        if (is_wp_error($post_id)) wp_die($post_id->get_error_message());
        
        // Save ACF fields
        update_field('ad_type', $ad_type, $post_id);
        update_field('full_text', $full_html, $post_id);
        update_field('lmb_status', 'draft', $post_id);
        update_field('lmb_client_id', $user_id, $post_id);

        wp_safe_redirect(add_query_arg(['ad_submitted' => 1, 'ad_id' => $post_id], wp_get_referer() ?: home_url('/dashboard')));
        exit;
    }
}
