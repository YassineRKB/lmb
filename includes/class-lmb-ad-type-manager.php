<?php
if (!defined('ABSPATH')) exit;

class LMB_Ad_Type_Manager {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('wp_ajax_lmb_get_ad_types', [__CLASS__, 'ajax_get_ad_types']);
        add_action('wp_ajax_lmb_add_ad_type', [__CLASS__, 'ajax_add_ad_type']);
        add_action('wp_ajax_lmb_delete_ad_type', [__CLASS__, 'ajax_delete_ad_type']);
        add_action('wp_ajax_lmb_update_ad_type', [__CLASS__, 'ajax_update_ad_type']);
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
        </div>
        <?php
    }

    public static function ajax_get_ad_types() {
        // This is a placeholder. You should replace this with your actual data retrieval logic.
        $ad_types = get_option('lmb_ad_types', []);
        wp_send_json_success($ad_types);
    }

    public static function ajax_add_ad_type() {
        check_ajax_referer('lmb_nonce', 'nonce');
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
        $ad_types = get_option('lmb_ad_types', []);
        $id_to_update = intval($_POST['id']);
        $new_name = sanitize_text_field($_POST['name']);
        foreach ($ad_types as &$type) {
            if ($type['id'] === $id_to_update) {
                $type['name'] = $new_name;
                break;
            }
        }
        update_option('lmb_ad_types', $ad_types);
        wp_send_json_success($ad_types);
    }
}