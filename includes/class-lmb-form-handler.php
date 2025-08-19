<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        // Hook the submission handler into an early action that runs on every page load.
        add_action('template_redirect', [__CLASS__, 'handle_form_submission']);
    }

    /**
     * Handles the submission of all custom LMB Elementor form widgets.
     */
    public static function handle_form_submission() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'lmb_submit_dynamic_form' || !isset($_POST['lmb_form_name'])) {
            return;
        }

        $form_name = sanitize_key($_POST['lmb_form_name']);

        if (!wp_verify_nonce($_POST['_wpnonce'], 'lmb_submit_' . $form_name)) {
            self::redirect_with_error('Security check failed.');
            return;
        }

        $widget_class = self::get_widget_class_from_form_name($form_name);

        if (!$widget_class || !class_exists($widget_class)) {
            self::redirect_with_error('Invalid form type specified.');
            return;
        }
        
        // Sanitize all submitted form data from the $_POST global.
        $form_data = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'form_fields_') === 0) {
                $field_name = str_replace('form_fields_', '', $key);
                if (is_array($value)) {
                     $form_data[$field_name] = filter_var_array($value, FILTER_SANITIZE_STRING);
                } else {
                     $form_data[$field_name] = sanitize_text_field($value);
                }
            }
        }
        
        try {
            $widget_instance = new $widget_class();
            $full_text = $widget_instance->build_legal_text($form_data);
            
            $ad_data = [
                'ad_type'   => $widget_instance->get_ad_type(),
                'full_text' => $full_text,
                'title'     => isset($form_data['companyName']) ? sanitize_text_field($form_data['companyName']) : $widget_instance->get_ad_type()
            ];

            self::create_legal_ad($ad_data);
            self::redirect_with_success();

        } catch (Exception $e) {
            self::redirect_with_error($e->getMessage());
        }
    }

    /**
     * Maps an internal form name to its corresponding widget class.
     */
    private static function get_widget_class_from_form_name($form_name) {
        $forms = [
            'constitution_sarl' => 'LMB_Form_Constitution_Sarl_Widget',
            'constitution_sarl_au' => 'LMB_Form_Constitution_Sarl_Au_Widget',
            'modification_siege' => 'LMB_Form_Modification_Siege_Widget',
            'modification_objet' => 'LMB_Form_Modification_Objet_Widget',
            'modification_gerant' => 'LMB_Form_Modification_Gerant_Widget',
            'modification_denomination' => 'LMB_Form_Modification_Denomination_Widget',
            'modification_capital' => 'LMB_Form_Modification_Capital_Widget',
            'modification_cession' => 'LMB_Form_Modification_Cession_Widget',
            'dissolution_anticipee' => 'LMB_Form_Dissolution_Anticipee_Widget',
            'dissolution_cloture' => 'LMB_Form_Dissolution_Cloture_Widget',
        ];
        return $forms[$form_name] ?? null;
    }

    /**
     * Creates the legal ad post.
     */
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
    
    /**
     * Create legal ad from Elementor form data (alternative method)
     */
    public static function create_legal_ad_from_elementor($form_data, $full_text) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            throw new Exception(__('You must be logged in to submit an ad.', 'lmb-core'));
        }

        $ad_title = !empty($form_data['title']) ? sanitize_text_field($form_data['title']) : 
                   (!empty($form_data['companyName']) ? sanitize_text_field($form_data['companyName']) : 
                   sanitize_text_field($form_data['ad_type']) . ' - ' . wp_date('Y-m-d'));

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
        update_post_meta($post_id, 'full_text', wp_kses_post($full_text));
        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);
        
        // Store additional form data as meta
        foreach ($form_data as $key => $value) {
            if (!in_array($key, ['ad_type', 'title']) && !empty($value)) {
                update_post_meta($post_id, 'form_' . $key, sanitize_text_field($value));
            }
        }
        
        self::log_activity('New legal ad #%d created via Elementor form by %s', $post_id, wp_get_current_user()->display_name);
        
        return $post_id;
    }
    
    private static function log_activity($msg, ...$args) {
        if (class_exists('LMB_Ad_Manager')) {
            LMB_Ad_Manager::log_activity(vsprintf($msg, $args));
        }
    }

    private static function redirect_with_error($message) {
        $redirect_url = add_query_arg('lmb_form_error', urlencode($message), wp_get_referer());
        wp_safe_redirect($redirect_url);
        exit();
    }

    private static function redirect_with_success() {
        $redirect_url = add_query_arg('lmb_form_success', 'true', wp_get_referer());
        wp_safe_redirect($redirect_url);
        exit();
    }
}