<?php
if (!defined('ABSPATH')) exit;

class LMB_CPT {
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
        // No need to register statuses here, as we can use meta fields for more flexibility.
    }

    public static function register() {
        // Legal Ad
        register_post_type('lmb_legal_ad', [
            'labels' => ['name' => __('Legal Ads', 'lmb-core'), 'singular_name' => __('Legal Ad', 'lmb-core')],
            'public' => true,
            'publicly_queryable' => true, // This is crucial for single views
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'has_archive' => 'announces',
            'rewrite' => ['slug' => 'announces', 'with_front' => false], // Important for clean URLs
            'supports' => ['title', 'author'],
            'menu_icon' => 'dashicons-media-text',
        ]);

        // Newspaper
        register_post_type('lmb_newspaper', [
            'labels' => ['name' => __('Newspapers', 'lmb-core'), 'singular_name' => __('Newspaper', 'lmb-core')],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'has_archive' => 'journaux',
            'rewrite' => ['slug' => 'journaux', 'with_front' => false],
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-media-document',
        ]);

        // Payment
        register_post_type('lmb_payment', [
            'labels' => ['name' => __('Payments', 'lmb-core'), 'singular_name' => __('Payment', 'lmb-core')],
            'public' => false, // Not for public viewing
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'supports' => ['title'],
            'menu_icon' => 'dashicons-money-alt',
            'capabilities' => ['create_posts' => 'do_not_allow'], // Prevent manual creation
            'map_meta_cap' => true,
        ]);

        // Package
        register_post_type('lmb_package', [
            'labels' => ['name' => __('Packages', 'lmb-core'), 'singular_name' => __('Package', 'lmb-core')],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-products',
        ]);
        
        // Add meta boxes
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_lmb_package', [__CLASS__, 'save_package_meta']);
    }
    
    public static function add_meta_boxes() {
        add_meta_box(
            'lmb_package_details',
            __('Package Details', 'lmb-core'),
            [__CLASS__, 'render_package_metabox'],
            'lmb_package',
            'normal',
            'high'
        );
    }

    public static function render_package_metabox($post) {
        wp_nonce_field('lmb_save_package_meta', 'lmb_package_nonce');
        $price = get_post_meta($post->ID, 'price', true);
        $points = get_post_meta($post->ID, 'points', true);
        $cost_per_ad = get_post_meta($post->ID, 'cost_per_ad', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lmb_package_price"><?php _e('Price (MAD)', 'lmb-core'); ?></label></th>
                <td><input type="number" step="0.01" id="lmb_package_price" name="price" value="<?php echo esc_attr($price); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lmb_package_points"><?php _e('Points Awarded', 'lmb-core'); ?></label></th>
                <td><input type="number" id="lmb_package_points" name="points" value="<?php echo esc_attr($points); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lmb_package_cost_per_ad"><?php _e('New Cost Per Ad (Points)', 'lmb-core'); ?></label></th>
                <td><input type="number" id="lmb_package_cost_per_ad" name="cost_per_ad" value="<?php echo esc_attr($cost_per_ad); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }

    public static function save_package_meta($post_id) {
        if (!isset($_POST['lmb_package_nonce']) || !wp_verify_nonce($_POST['lmb_package_nonce'], 'lmb_save_package_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = ['price', 'points', 'cost_per_ad'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }
}