<?php
if (!defined('ABSPATH')) exit;

class LMB_Ad_Type_Manager {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('wp_ajax_lmb_get_ad_types', [__CLASS__, 'ajax_get_ad_types']);
        add_action('wp_ajax_lmb_add_ad_type', [__CLASS__, 'ajax_add_ad_type']);
        add_action('wp_ajax_lmb_delete_ad_type', [__CLASS__, 'ajax_delete_ad_type']);
        add_action('wp_ajax_lmb_update_ad_type', [__CLASS__, 'ajax_update_ad_type']);
        // NEW AJAX ACTION FOR REFRESH
        add_action('wp_ajax_lmb_refresh_ad_types', [__CLASS__, 'ajax_refresh_ad_types']);
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'lmb-core',
            __('Type d\'annonce', 'lmb-core'),
            __('Ad Types', 'lmb-core'),
            'manage_options',
            'lmb-ad-type-manager',
            [__CLASS__, 'create_admin_page'],
            30
        );
    }

    public static function create_admin_page() {
        ?>
        <div class="wrap" id="lmb-ad-type-manager">
            <h1><?php echo esc_html__('Gérer les types d\'annonces', 'lmb-core'); ?></h1>
            <div id="lmb-ad-type-form-wrapper">
                <input type="text" id="lmb-new-ad-type-name" placeholder="<?php echo esc_attr__('Nouveau type d\'annonce', 'lmb-core'); ?>">
                <button id="lmb-add-ad-type-btn" class="button button-primary"><?php echo esc_html__('Ajouter', 'lmb-core'); ?></button>
                
                <button id="lmb-refresh-ad-types-btn" class="button button-secondary" style="margin-left: 10px;">
                    <i class="fas fa-sync-alt"></i> <?php echo esc_html__('Actualiser depuis les Annonces', 'lmb-core'); ?>
                </button>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php echo esc_html__('ID', 'lmb-core'); ?></th>
                        <th scope="col"><?php echo esc_html__('Nom du type', 'lmb-core'); ?></th>
                        <th scope="col"><?php echo esc_html__('Actions', 'lmb-core'); ?></th>
                    </tr>
                </thead>
                <tbody id="lmb-ad-types-tbody">
                    </tbody>
            </table>
            <div id="lmb-ad-type-migration-feedback" class="notice" style="display:none; margin-top: 15px;"></div>
        </div>
        <?php
    }

    public static function ajax_get_ad_types() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        $ad_types = get_option('lmb_ad_types', []);
        wp_send_json_success($ad_types);
    }

    public static function ajax_add_ad_type() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        $ad_types = get_option('lmb_ad_types', []);
        $new_type = sanitize_text_field($_POST['name']);
        if (!empty($new_type)) {
            $ad_types[] = ['id' => count($ad_types) + 1, 'name' => $new_type];
            update_option('lmb_ad_types', $ad_types);
            wp_send_json_success($ad_types);
        } else {
            wp_send_json_error(['message' => 'Le nom du type d\'annonce ne peut pas être vide.']);
        }
    }

    public static function ajax_delete_ad_type() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        $ad_types = get_option('lmb_ad_types', []);
        $id_to_delete = intval($_POST['id']);
        $ad_types = array_filter($ad_types, function($type) use ($id_to_delete) {
            return $type['id'] !== $id_to_delete;
        });
        update_option('lmb_ad_types', array_values($ad_types));
        wp_send_json_success(array_values($ad_types));
    }

    public static function ajax_update_ad_type() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        $ad_types = get_option('lmb_ad_types', []);
        $id_to_update = intval($_POST['id']);
        $new_name = sanitize_text_field($_POST['name']);
        $old_name = sanitize_text_field($_POST['old_name']); // NEW: Receive old name

        $migration_count = 0;
        
        // 1. Update the master option list
        foreach ($ad_types as &$type) {
            if ($type['id'] === $id_to_update) {
                $type['name'] = $new_name;
                
                // 2. MIGRATION LOGIC: Check for rename and trigger migration
                if ($old_name !== $new_name && !empty($old_name)) {
                    $migration_count = self::migrate_ads_ad_type($old_name, $new_name);
                }
                break;
            }
        }
        update_option('lmb_ad_types', $ad_types);
        
        // 3. Return success with migration count
        wp_send_json_success([
            'message' => 'Type d\'annonce mis à jour avec succès.',
            'migration_count' => $migration_count,
            'ad_types' => $ad_types
        ]);
    }
    
    /**
     * NEW METHOD: AJAX handler for refreshing the ad types list from actual posts.
     */
    public static function ajax_refresh_ad_types() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        
        $count = self::refresh_ad_types_from_ads();
        
        wp_send_json_success(['message' => sprintf(__('%d types d\'annonces uniques ont été trouvés et l\'option a été mise à jour.', 'lmb-core'), $count)]);
    }

    /**
     * NEW CORE FUNCTION: Queries all existing legal ads and generates a fresh list of ad types.
     * This preserves existing IDs and assigns new IDs to newly found types.
     */
    public static function refresh_ad_types_from_ads() {
        global $wpdb;
        
        // Find all unique meta_value entries for 'ad_type' meta_key in 'lmb_legal_ad' posts.
        $unique_types = $wpdb->get_col(
            $wpdb->prepare("
                SELECT DISTINCT pm.meta_value 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                WHERE p.post_type = %s
                AND pm.meta_key = %s
                AND pm.meta_value != ''
            ", 'lmb_legal_ad', 'ad_type')
        );

        $existing_types = get_option('lmb_ad_types', []);
        $new_ad_types = [];
        
        // Assign a unique ID to each, reusing existing IDs if possible.
        $max_id = 0;
        foreach ($existing_types as $type) {
             if (isset($type['id']) && $type['id'] > $max_id) $max_id = $type['id'];
        }
        
        // Map existing names to their IDs
        $current_names = array_column($existing_types, 'name', 'id');
        
        foreach ($unique_types as $type_name) {
            $existing_id = array_search($type_name, $current_names);
            
            if ($existing_id !== false) {
                 // Type name exists, reuse its ID
                 $new_ad_types[] = ['id' => (int)$existing_id, 'name' => $type_name];
            } else {
                 // New type name, assign next available ID
                 $max_id++;
                 $new_ad_types[] = ['id' => $max_id, 'name' => $type_name];
            }
        }
        
        // Sort by ID to ensure consistent order
        usort($new_ad_types, function($a, $b) { return $a['id'] - $b['id']; });

        update_option('lmb_ad_types', $new_ad_types);
        
        return count($new_ad_types);
    }
    
    /**
     * NEW CORE FUNCTION: Migrates the ad_type meta value for all matching posts (Rename Migration).
     */
    public static function migrate_ads_ad_type($old_name, $new_name) {
        global $wpdb;
        
        $updated_count = $wpdb->query(
            $wpdb->prepare("
                UPDATE {$wpdb->postmeta}
                SET meta_value = %s
                WHERE meta_key = %s
                AND meta_value = %s
                AND post_id IN (
                    SELECT ID FROM {$wpdb->posts} WHERE post_type = %s
                )
            ", $new_name, 'ad_type', $old_name, 'lmb_legal_ad')
        );

        return (int) $updated_count;
    }
}