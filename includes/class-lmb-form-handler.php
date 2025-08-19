<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        // This action hooks into WordPress early to catch our form submission POST data.
        add_action('template_redirect', ['LMB_Form_Widget_Base', 'handle_form_submission']);
    }

    // The create_legal_ad function remains the same, as it's still needed.
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

        update_post_meta($post_id, 'ad_type', sanitize_text_field($form_data['ad_type']));
        update_post_meta($post_id, 'full_text', wp_kses_post($form_data['full_text']));
        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);
        
        self::log_activity('New legal ad #%d created as draft by %s', $post_id, wp_get_current_user()->display_name);
        
        return $post_id;
    }
    
    private static function log_activity($msg, ...$args) {
        if (class_exists('LMB_Ad_Manager')) {
            LMB_Ad_Manager::log_activity(vsprintf($msg, $args));
        }
    }
}