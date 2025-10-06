<?php
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

    public static function create_legal_ad($form_data) {
        $user_id = get_current_user_id();
        if (!$user_id) {
            throw new Exception('User not logged in.');
        }

        $ad_type = isset($form_data['ad_type']) ? sanitize_text_field($form_data['ad_type']) : 'Untitled Ad';
        $company_name = isset($form_data['companyname']) ? sanitize_text_field($form_data['companyname']) : '';
        $ad_title = $ad_type . ($company_name ? ' - ' . $company_name : '') . ' - ' . wp_date('Y-m-d');

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

        update_post_meta($post_id, '_lmb_form_data_json', wp_json_encode($form_data, JSON_UNESCAPED_UNICODE));

        if (isset($form_data['ad_type'])) {
            update_post_meta($post_id, 'ad_type', $form_data['ad_type']);
        }
        if (!empty($company_name)) {
            update_post_meta($post_id, 'company_name', $company_name);
        }
        update_post_meta($post_id, 'lmb_status', 'draft');
        update_post_meta($post_id, 'lmb_client_id', $user_id);

        self::generate_and_save_formatted_text($post_id);
        
        self::log_activity('Nouvelle annonce légale %d créée en tant que brouillon par %s', $post_id, wp_get_current_user()->display_name);
        return $post_id;
    }
    
    private static function log_activity($msg, ...$args) {
        if (class_exists('LMB_Ad_Manager')) {
            LMB_Ad_Manager::log_activity(vsprintf($msg, $args));
        }
    }

    private static function array_keys_to_lower($array) {
        $result = [];
        foreach ($array as $key => $value) {
            $key = strtolower($key);
            if (is_array($value)) {
                $value = self::array_keys_to_lower($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * FINAL FIX: This function now safely cleans the data.
     */
    private static function clean_form_data_values($data) {
        if (is_string($data)) {
            // 1. Replace 'rn' with a proper newline character.
            $data = str_replace('rn', "\n", $data);
            // 2. Specifically replace the 't' that appears after bullet points.
            $data = str_replace(['t', '•t'], ["\t", "•\t"], $data);
            return $data;
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::clean_form_data_values($value);
            }
        }
        return $data;
    }

    public static function generate_and_save_formatted_text($post_id) {
        $ad_type = get_post_meta($post_id, 'ad_type', true);
        $json_data = get_post_meta($post_id, '_lmb_form_data_json', true);

        if (empty($ad_type) || empty($json_data)) {
            return;
        }

        $form_data = json_decode($json_data, true);
        if (!is_array($form_data)) {
            return;
        }

        // Always run the cleaning function to repair old ads and ensure new ones are perfect.
        $cleaned_form_data = self::clean_form_data_values($form_data);
        $data_for_template = self::array_keys_to_lower($cleaned_form_data);

        $all_templates = get_option('lmb_legal_ad_templates', []);
        $template = isset($all_templates[sanitize_key($ad_type)]) ? $all_templates[sanitize_key($ad_type)] : 'Template not found.';
        
        // --- Template Engine (with Unicode support and safe replacements) ---

        // 1. GERANCE PARAGRAPH HELPER (Constitution SARL): {{GERANCE_PARAGRAPH}}
        $template = preg_replace_callback('/{{GERANCE_PARAGRAPH}}/isu', function($matches) use ($data_for_template) {
            $repeater_key = 'gerants';
            $gerants = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? $data_for_template[$repeater_key] : [];
            $count = count($gerants);
            $output = '';

            if ($count === 1) {
                $g = $gerants[0];
                // Cas Singulier: "M/Mme. X, demeurant à Y, est désigné(e) gérant(e) pour une durée indéterminée. La société est engagée par la signature unique de son gérant."
                $output = "<strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}, est désigné(e) gérant(e) pour une durée indéterminée. La société est engagée par la signature unique de son gérant.";
            } elseif ($count > 1) {
                $list = [];
                foreach ($gerants as $g) {
                    // Collecte des gérants pour la liste
                    $list[] = "<strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}";
                }
                
                // Formater la liste avec des virgules et 'et' pour le dernier élément
                $last_item = array_pop($list);
                $list_string = implode(', ', $list) . ' et ' . $last_item;
                
                // Cas Pluriel
                $output = $list_string . ", sont désigné(e) co-gérant(es) pour une durée indéterminée. La société est engagée par la signature séparée de chacun des co-gérants.";
            }
            // IMPORTANT: Ajouter un <br> après le paragraphe pour la mise en page
            return $output . '<br>';
        }, $template);
        
        // 2. Custom Logic for Departing Gerants: {{ANCIENS_GERANTS_PARAGRAPH}}
        $template = preg_replace_callback('/{{ANCIENS_GERANTS_PARAGRAPH}}/isu', function($matches) use ($data_for_template) {
            $repeater_key = 'ancient_gerants';
            $gerants = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? $data_for_template[$repeater_key] : [];
            $count = count($gerants);
            $output = '';
            
            $text_singular = 'Suite à la démission du gérant unique suivant : ';
            $text_plural = 'Suite à la démission des co-gérants suivants : ';

            if ($count >= 1) {
                $list = [];
                foreach ($gerants as $g) {
                    $list[] = "M/Mme. <strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}";
                }
                
                if ($count === 1) {
                    $output = $text_singular . $list[0] . '.';
                } else {
                    $last_item = array_pop($list);
                    $list_string = implode(', ', $list) . ' et ' . $last_item;
                    $output = $text_plural . $list_string . '.';
                }
            }
            return $output . '<br><br>';
        }, $template);


        // 3. Custom Logic for New Gerants and Signature Rule: {{NOUVEAUX_GERANTS_PARAGRAPH}}
        $template = preg_replace_callback('/{{NOUVEAUX_GERANTS_PARAGRAPH}}/isu', function($matches) use ($data_for_template) {
            $repeater_key = 'new_gerants';
            $gerants = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? $data_for_template[$repeater_key] : [];
            $count = count($gerants);
            $output = '';

            if ($count >= 1) {
                $list = [];
                foreach ($gerants as $g) {
                    $list[] = "M/Mme. <strong>{$g['namegerant']}</strong>, demeurant à {$g['addrgerant']}";
                }
                
                // Formater la liste
                $last_item = array_pop($list);
                $list_string = implode(', ', $list) . ' et ' . $last_item;
                
                if ($count === 1) {
                    // Singular Nomination + Singular Signature Rule
                    $output = "La nomination de nouveau gérant(e) unique: {$list_string}. La société sera désormais engagée par la signature unique du gérant.";
                } else {
                    // Plural Nomination + Plural Signature Rule
                    $output = "La nomination des nouveaux co-gérants: {$list_string}. La société sera désormais engagée par la signature séparée de chacun des co-gérants.";
                }
            }
            return $output . '<br><br>';
        }, $template);
        
        // 4. SUMMATION LOGIC: {{sum:repeater_key:field_key}}
        $template = preg_replace_callback('/{{sum:(.*?):(.*?)}}/iu', function($matches) use ($data_for_template) {
            $repeater_key = strtolower(trim($matches[1]));
            $field_key = strtolower(trim($matches[2]));
            $total = 0;
            if (isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key])) {
                foreach ($data_for_template[$repeater_key] as $item) {
                    if (isset($item[$field_key])) $total += (float)$item[$field_key];
                }
            }
            return $total;
        }, $template);

        // 5. CONDITIONAL COUNT LOGIC: {{#ifcount repeater_key > N}}...{{else}}...{{\/ifcount}}
        $template = preg_replace_callback('/{{#ifcount (.*?) > (\d+)}}(.*?){{else}}(.*?){{\/ifcount}}/isu', function($matches) use ($data_for_template) {
            $repeater_key = strtolower(trim($matches[1]));
            $item_count = isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key]) ? count($data_for_template[$repeater_key]) : 0;
            return ($item_count > (int)$matches[2]) ? $matches[3] : $matches[4];
        }, $template);

        // 6. REPEATER LOOP LOGIC: {{#each repeater_key}}...{{\/each}}
        $template = preg_replace_callback('/{{#each (.*?)}}(.*?){{\/each}}/isu', function($matches) use ($data_for_template) {
            $repeater_key = strtolower(trim($matches[1]));
            $inner_template = trim($matches[2]);
            $output = '';
            if (isset($data_for_template[$repeater_key]) && is_array($data_for_template[$repeater_key])) {
                foreach ($data_for_template[$repeater_key] as $item) {
                    $line = $inner_template;
                    $line = preg_replace_callback('/{{(.*?)}}/u', function($inner_matches) use ($item) {
                        $field_key = strtolower(trim($inner_matches[1]));
                        return isset($item[$field_key]) ? $item[$field_key] : '';
                    }, $line);
                    $output .= $line;
                }
            }
            return $output;
        }, $template);

        // 7. SIMPLE PLACEHOLDERS: {{field_key}}
        $template = preg_replace_callback('/{{(.*?)}}/u', function($matches) use ($data_for_template) {
            $key = strtolower(trim($matches[1]));
            return isset($data_for_template[$key]) ? $data_for_template[$key] : '';
        }, $template);

        // Final processing: Sanitize the entire result once and convert newlines.
        $formatted_text = wp_kses_post(nl2br($template, false));
        if (!empty($formatted_text)) {
            wp_update_post(['ID' => $post_id, 'post_content' => $formatted_text]);
            update_post_meta($post_id, 'full_text', $formatted_text);
        }
    }
}