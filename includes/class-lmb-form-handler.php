<?php
// FILE: includes/class-lmb-form-handler.php (DEFINITIVE FIX: Universal Base64 Encoding)
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        add_action('elementor_pro/forms/actions/register', [__CLASS__, 'register_elementor_action']);
        add_action('admin_init', [__CLASS__, 'check_elementor_pro']);
    }

    public static function check_elementor_pro() {
        if (!did_action('elementor_pro/init')) {
            LMB_Error_Handler::log_error('Elementor Pro is not active or loaded', ['action' => 'check_elementor_pro']);
        }
    }

    public static function register_elementor_action($form_actions_registrar) {
        try {
            $action_file = LMB_CORE_PATH . 'includes/class-lmb-action-save-ad.php';
            if (file_exists($action_file)) {
                require_once $action_file;
                if (class_exists('LMB_Save_Ad_Action')) {
                    $action = new LMB_Save_Ad_Action();
                    $form_actions_registrar->register($action);
                }
            }
        } catch (Exception $e) {
            LMB_Error_Handler::log_error('Failed to register Elementor action', ['error' => $e->getMessage()]);
        }
    }

    /**
     * A recursive function to Base64 encode all string values in the form data.
     * This makes the data 100% safe for JSON encoding.
     */
    private static function deep_base64_encode($data) {
        if (is_string($data)) {
            return base64_encode($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = self::deep_base64_encode($value);
            }
        }
        return $data;
    }

    /**
     * A recursive function to decode all the Base64 encoded strings.
     */
    private static function deep_base64_decode($data) {
        if (is_string($data)) {
            // Check if the string is potentially Base64 encoded before decoding.
            if (base64_encode(base64_decode($data, true)) === $data) {
                 return base64_decode($data);
            }
            return $data;
        }
        if (is_array($data)) {
            foreach ($data as $key => &$value) {
                $value = self::deep_base64_decode($value);
            }
        }
        return $data;
    }

    public static function create_legal_ad($form_data) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            throw new Exception('User not logged in.');
        }

        // --- DEFINITIVE FIX: UNIVERSAL BASE64 ENCODING ---
        // First, remove any slashes WordPress might have added.
        $form_data = stripslashes_deep($form_data);
        // Now, encode EVERY string field to make it safe.
        $encoded_form_data = self::deep_base64_encode($form_data);
        $encoded_form_data['is_globally_base64_encoded'] = true; // Flag for decoding later
        // --- END OF FIX ---

        $ad_type = isset($form_data['ad_type']) ? sanitize_text_field($form_data['ad_type']) : 'Untitled Ad';
        $company_name = isset($form_data['companyname']) ? sanitize_text_field($form_data['companyname']) : '';
        $ad_title = $company_name;

        $post_id = wp_insert_post([
            'post_type'    => 'lmb_legal_ad',
            'post_title'   => $ad_title,
            'post_status'  => 'draft',
            'post_author'  => $user_id,
            'post_content' => ''
        ], true);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        $json_data = wp_json_encode($encoded_form_data);

        if (json_last_error() !== JSON_ERROR_NONE) {
            LMB_Error_Handler::log_error('JSON Encode Failed After Base64', ['post_id' => $post_id, 'error' => json_last_error_msg()]);
            $error_content = "<strong>TECHNICAL ERROR:</strong> Form data encoding failed. Administrator notified.";
            wp_update_post(['ID' => $post_id, 'post_content' => $error_content]);
            return $post_id;
        }

        update_post_meta($post_id, '_lmb_form_data_json', $json_data);
        if (isset($form_data['ad_type'])) update_post_meta($post_id, 'ad_type', $form_data['ad_type']);
        if (!empty($company_name)) update_post_meta($post_id, 'company_name', $company_name);
        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);

        self::generate_and_save_formatted_text($post_id);
        
        self::log_activity('New legal ad %d created as draft by %s', $post_id, wp_get_current_user()->display_name);
        return $post_id;
    }
    
    private static function log_activity($msg, ...$args) { if (class_exists('LMB_Ad_Manager')) { LMB_Ad_Manager::log_activity(vsprintf($msg, $args)); } }
    private static function array_keys_to_lower($array) { $result = []; foreach ($array as $key => $value) { $key = strtolower($key); if (is_array($value)) { $value = self::array_keys_to_lower($value); } $result[$key] = $value; } return $result; }

    public static function generate_and_save_formatted_text($post_id) {
        $ad_type = get_post_meta($post_id, 'ad_type', true);
        $json_data = get_post_meta($post_id, '_lmb_form_data_json', true);

        if (empty($ad_type) || empty($json_data)) {
            return;
        }

        $encoded_form_data = json_decode(stripslashes($json_data), true);

        if (!is_array($encoded_form_data)) {
            LMB_Error_Handler::log_error('JSON Decode Failed for Ad', ['post_id' => $post_id, 'json_data' => $json_data]);
            $error_content = "<strong>ERROR:</strong> Automatic content generation failed due to invalid form data.";
            wp_update_post(['ID' => $post_id, 'post_content' => $error_content]);
            return;
        }

        // --- DEFINITIVE FIX: DECODE THE DATA ---
        $form_data = $encoded_form_data;
        if (isset($form_data['is_globally_base64_encoded']) && $form_data['is_globally_base64_encoded']) {
            $form_data = self::deep_base64_decode($form_data);
        }
        // --- END OF FIX ---
        
        $data_for_template = self::array_keys_to_lower($form_data);

        $all_templates = get_option('lmb_legal_ad_templates', []);
        $template = isset($all_templates[sanitize_key($ad_type)]) ? $all_templates[sanitize_key($ad_type)] : '';

        if (empty($template)) {
            LMB_Error_Handler::log_error('Template Not Found', ['post_id' => $post_id, 'ad_type' => $ad_type]);
            $error_content = "<strong>ERROR:</strong> Template for ad type '<strong>" . esc_html($ad_type) . "</strong>' not found.";
            wp_update_post(['ID' => $post_id, 'post_content' => $error_content]);
            return;
        }
        
        // (The rest of the template engine remains unchanged)
        $template = preg_replace_callback('/{{GERANCE_PARAGRAPH}}/isu', function($matches) use ($data_for_template) { $repeater_key = 'gerants'; $gerants = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? $data_for_template[$repeater_key] : []; $count = count($gerants); $output = ''; if ($count === 1) { $g = $gerants[0]; $output = "<strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}, est désigné(e) gérant(e) pour une durée indéterminée. La société est engagée par la signature unique de son gérant."; } elseif ($count > 1) { $list = []; foreach ($gerants as $g) { $list[] = "<strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}"; } $last_item = array_pop($list); $list_string = implode(', ', $list) . ' et ' . $last_item; $output = $list_string . ", sont désigné(e) co-gérant(es) pour une durée indéterminée. La société est engagée par la signature séparée de chacun des co-gérants."; } return $output . '<br>'; }, $template);
        $template = preg_replace_callback('/{{ANCIENS_GERANTS_PARAGRAPH}}/isu', function($matches) use ($data_for_template) { $repeater_key = 'ancient_gerants'; $gerants = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? $data_for_template[$repeater_key] : []; $count = count($gerants); $output = ''; $text_singular = 'Suite à la démission du gérant unique suivant : '; $text_plural = 'Suite à la démission des co-gérants suivants : '; if ($count >= 1) { $list = []; foreach ($gerants as $g) { $list[] = "M/Mme. <strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}"; } if ($count === 1) { $output = $text_singular . $list[0] . '.'; } else { $last_item = array_pop($list); $list_string = implode(', ', $list) . ' et ' . $last_item; $output = $text_plural . $list_string . '.'; } } return $output . '<br><br>'; }, $template);
        $template = preg_replace_callback('/{{NOUVEAUX_GERANTS_PARAGRAPH}}/isu', function($matches) use ($data_for_template) { $repeater_key = 'new_gerants'; $gerants = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? $data_for_template[$repeater_key] : []; $count = count($gerants); $output = ''; if ($count >= 1) { $list = []; foreach ($gerants as $g) { $list[] = "M/Mme. <strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}"; } $last_item = array_pop($list); $list_string = implode(', ', $list) . ' et ' . $last_item; if ($count === 1) { $output = "La nomination de nouveau gérant(e) unique: {$list_string}. La société sera désormais engagée par la signature unique du gérant."; } else { $output = "La nomination des nouveaux co-gérants: {$list_string}. La société sera désormais engagée par la signature séparée de chacun des co-gérants."; } } return $output . '<br><br>'; }, $template);
        $template = preg_replace_callback('/{{sum:(.*?):(.*?)}}/iu', function($matches) use ($data_for_template) { $repeater_key = strtolower(trim($matches[1])); $field_key = strtolower(trim($matches[2])); $total = 0; if (isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key])) { foreach ($data_for_template[$repeater_key] as $item) { if (isset($item[$field_key])) $total += (float)$item[$field_key]; } } return $total; }, $template);
        $template = preg_replace_callback('/{{#ifcount (.*?) > (\d+)}}(.*?){{else}}(.*?){{\/ifcount}}/isu', function($matches) use ($data_for_template) { $repeater_key = strtolower(trim($matches[1])); $item_count = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? count($data_for_template[$repeater_key]) : 0; return ($item_count > (int)$matches[2]) ? $matches[3] : $matches[4]; }, $template);
        $template = preg_replace_callback('/{{#each (.*?)}}(.*?){{\/each}}/isu', function($matches) use ($data_for_template) { $repeater_key = strtolower(trim($matches[1])); $inner_template = trim($matches[2]); $output = ''; if (isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key])) { foreach ($data_for_template[$repeater_key] as $item) { $line = $inner_template; $line = preg_replace_callback('/{{(.*?)}}/u', function($inner_matches) use ($item) { $field_key = strtolower(trim($inner_matches[1])); return isset($item[$field_key]) ? $item[$field_key] : ''; }, $line); $output .= $line; } } return $output; }, $template);
        $template = preg_replace_callback('/{{(.*?)}}/u', function($matches) use ($data_for_template) { $key = strtolower(trim($matches[1])); return isset($data_for_template[$key]) ? $data_for_template[$key] : ''; }, $template);

        $formatted_text = wp_kses_post(nl2br($template, false));
        if (!empty($formatted_text)) {
            wp_update_post(['ID' => $post_id, 'post_content' => $formatted_text]);
            update_post_meta($post_id, 'full_text', $formatted_text);
        }
    }
}