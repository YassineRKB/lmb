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

    // --- REVISED: This function now correctly saves repeater data as arrays ---
    public static function create_legal_ad($form_data) {
        // ... (user validation and post creation logic is correct)
        $user_id = get_current_user_id();
        if (!$user_id) {
            throw new Exception('User not logged in.');
        }

        $ad_title = !empty($form_data['title']) ? sanitize_text_field($form_data['title']) : sanitize_text_field($form_data['ad_type']) . ' - ' . wp_date('Y-m-d');

        $post_id = wp_insert_post([
            'post_type'    => 'lmb_legal_ad',
            'post_title'   => $ad_title,
            'post_status'  => 'draft',
            'post_author'  => $user_id,
            'post_content' => '' // Content will be generated
        ], true);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);

        // Save all individual form fields, including repeater arrays
        foreach ($form_data as $key => $value) {
            if (in_array($key, ['post_id', 'form_id', 'form_name', 'full_text', 'title', 'step'])) {
                continue;
            }
            // Sanitize and save the raw field value. update_post_meta handles arrays automatically.
            $sanitized_value = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            update_post_meta($post_id, sanitize_key($key), $sanitized_value);
        }

        // Generate and save the formatted text using the new engine
        self::generate_and_save_formatted_text($post_id);
        
        self::log_activity('New legal ad #%d created as draft by %s', $post_id, wp_get_current_user()->display_name);
        return $post_id;
    }
    
    // Helper to prevent code duplication
    private static function log_activity($msg, ...$args) {
        if (class_exists('LMB_Ad_Manager')) {
            LMB_Ad_Manager::log_activity(vsprintf($msg, $args));
        }
    }

    // --- REWRITTEN & UPGRADED FUNCTION ---
    public static function generate_and_save_formatted_text($post_id) {
        $ad_type = get_post_meta($post_id, 'ad_type', true);
        if (empty($ad_type)) return;

        // --- FIX: Load the single option containing all templates ---
        $all_templates = get_option('lmb_legal_ad_templates', []);
        $ad_type_key = sanitize_key($ad_type);
        
        // --- FIX: Get the correct template from the array ---
        $template = isset($all_templates[$ad_type_key]) 
            ? $all_templates[$ad_type_key] 
            : 'Template not found for this ad type. Please create one in the LMB Settings.';


        // --- 1. Pre-computation Step (Calculations) ---
        preg_match_all('/{{sum:(.*?):(.*?)}}/', $template, $sum_matches, PREG_SET_ORDER);
        if (!empty($sum_matches)) {
            foreach ($sum_matches as $match) {
                $repeater_id = $match[1];
                $field_to_sum = $match[2];
                $meta_values = get_post_meta($post_id, sanitize_key($field_to_sum), true);
                $total = 0;
                if (is_array($meta_values)) {
                    $total = array_sum(array_map('intval', $meta_values));
                }
                $template = str_replace($match[0], $total, $template);
            }
        }

        // --- 2. Conditional Logic Step (ifcount) ---
        preg_match_all('/{{#ifcount (.*?) > (\d+)}}(.*?){{else}}(.*?){{\/ifcount}}/s', $template, $cond_matches, PREG_SET_ORDER);
        if (!empty($cond_matches)) {
            foreach ($cond_matches as $match) {
                $repeater_id = $match[1];
                $count_check = (int)$match[2];
                $text_if_true = $match[3];
                $text_if_false = $match[4];

                // Get the hidden input for the repeater to find the actual item count
                $repeater_meta = get_post_meta($post_id, sanitize_key($repeater_id), true);
                $repeater_data = json_decode($repeater_meta, true);
                $item_count = isset($repeater_data['count']) ? (int)$repeater_data['count'] : 0;

                $output = ($item_count > $count_check) ? $text_if_true : $text_if_false;
                $template = str_replace($match[0], $output, $template);
            }
        }

        // --- 3. Repeater Loop Processing ---
        preg_match_all('/{{#each (.*?)}}(.*?){{\/each}}/s', $template, $repeater_matches, PREG_SET_ORDER);
        if (!empty($repeater_matches)) {
            foreach ($repeater_matches as $match) {
                $repeater_id = $match[1];
                $repeater_template = $match[2];
                preg_match_all('/{{(.*?)}}/', $repeater_template, $inner_field_matches);
                if (empty($inner_field_matches[1])) continue;

                $repeater_fields = $inner_field_matches[1];
                $repeater_data = [];
                $item_count = 0;

                foreach ($repeater_fields as $field_key) {
                    $meta_values = get_post_meta($post_id, sanitize_key($field_key), true);
                    if (is_array($meta_values)) {
                        $repeater_data[$field_key] = array_values($meta_values);
                        $item_count = max($item_count, count($meta_values));
                    }
                }

                $repeater_output = '';
                for ($i = 0; $i < $item_count; $i++) {
                    $line = $repeater_template;
                    foreach ($repeater_fields as $field_key) {
                        $value = isset($repeater_data[$field_key][$i]) ? esc_html($repeater_data[$field_key][$i]) : '';
                        $line = str_replace('{{' . $field_key . '}}', $value, $line);
                    }
                    $repeater_output .= $line;
                }
                $template = str_replace($match[0], $repeater_output, $template);
            }
        }

        // --- 4. Simple Placeholder Processing ---
        $all_meta = get_post_meta($post_id);
        preg_match_all('/{{(.*?)}}/', $template, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $field_key) {
                if (isset($all_meta[$field_key][0])) {
                    $value = esc_html($all_meta[$field_key][0]);
                    $template = str_replace('{{' . $field_key . '}}', $value, $template);
                }
            }
        }

        $formatted_text = wp_kses_post(nl2br($template));
        if (!empty($formatted_text)) {
            wp_update_post(['ID' => $post_id, 'post_content' => $formatted_text]);
            update_post_meta($post_id, 'full_text', $formatted_text);
        }
    }
}
