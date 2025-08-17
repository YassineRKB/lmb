<?php
if (!defined('ABSPATH')) exit;

class LMB_CPT {
    public static function init() {
        add_action('init', [__CLASS__, 'register']);
        add_action('init', [__CLASS__, 'register_statuses']);
    }

    public static function register() {
        // Legal Ad
        register_post_type('lmb_legal_ad', [
            'label' => __('Legal Ads', 'lmb-core'),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'has_archive' => 'announces',
            'supports' => ['title'],
            'menu_icon' => 'dashicons-media-text',
            'show_in_menu' => 'lmb-core',
            'rewrite' => ['slug' => 'announces'],
        ]);

        // Newspaper
        register_post_type('lmb_newspaper', [
            'label' => __('Newspapers', 'lmb-core'),
            'public' => true,
            'has_archive' => 'journaux',
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-media-document',
            'rewrite' => ['slug' => 'journaux'],
            'show_in_menu' => 'lmb-core',
        ]);

        // Payment
        register_post_type('lmb_payment', [
            'label' => __('Payments', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-money-alt',
            'show_in_menu' => 'lmb-core',
        ]);

        // Package
        register_post_type('lmb_package', [
            'label' => __('Packages', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-admin-generic',
            'show_in_menu' => 'lmb-core',
        ]);
        
        // Add meta boxes
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_package_meta']);
    }
    
    public static function add_meta_boxes() {
        add_meta_box(
            'lmb_ad_actions',
            __('Ad Actions', 'lmb-core'),
            [__CLASS__, 'render_ad_actions_metabox'],
            'lmb_legal_ad',
            'side',
            'high'
        );
        add_meta_box(
            'lmb_package_details',
            __('Package Details', 'lmb-core'),
            [__CLASS__, 'render_package_metabox'],
            'lmb_package',
            'normal',
            'high'
        );
    }
    
    public static function render_ad_actions_metabox($post) {
        $status = get_post_meta($post->ID, 'lmb_status', true);
        $client_id = get_post_meta($post->ID, 'lmb_client_id', true);
        $client = get_userdata($client_id);
        
        echo '<div class="lmb-ad-actions">';
        echo '<p><strong>Client:</strong> ' . ($client ? esc_html($client->display_name) : 'Unknown') . '</p>';
        echo '<p><strong>Status:</strong> ' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</p>';
        
        if ($status === 'pending_review' && current_user_can('edit_others_posts')) {
            wp_nonce_field('lmb_admin_ad_action', '_lmb_nonce');
            echo '<button class="button button-primary lmb-quick-approve" data-post-id="' . $post->ID . '">Approve & Publish</button>';
            echo '<button class="button lmb-quick-deny" data-post-id="' . $post->ID . '">Deny</button>';
        }
        
        $pdf_url = get_post_meta($post->ID, 'ad_pdf_url', true);
        if ($pdf_url) {
            echo '<p><a href="' . esc_url($pdf_url) . '" target="_blank" class="button">Download Ad PDF</a></p>';
        }
        
        $invoice_url = get_post_meta($post->ID, 'ad_invoice_pdf_url', true);
        if ($invoice_url) {
            echo '<p><a href="' . esc_url($invoice_url) . '" target="_blank" class="button">Download Invoice</a></p>';
        }
        
        echo '</div>';
    }

    public static function render_package_metabox($post) {
        wp_nonce_field('lmb_save_package_meta', 'lmb_package_nonce');
        $price = get_post_meta($post->ID, 'price', true);
        $points = get_post_meta($post->ID, 'points', true);
        $cost_per_ad = get_post_meta($post->ID, 'cost_per_ad', true);
        ?>
        <p>
            <label for="lmb_package_price"><?php _e('Price (MAD)', 'lmb-core'); ?></label>
            <input type="number" id="lmb_package_price" name="lmb_package_price" value="<?php echo esc_attr($price); ?>" />
        </p>
        <p>
            <label for="lmb_package_points"><?php _e('Points', 'lmb-core'); ?></label>
            <input type="number" id="lmb_package_points" name="lmb_package_points" value="<?php echo esc_attr($points); ?>" />
        </p>
        <p>
            <label for="lmb_package_cost_per_ad"><?php _e('Cost per Ad', 'lmb-core'); ?></label>
            <input type="number" id="lmb_package_cost_per_ad" name="lmb_package_cost_per_ad" value="<?php echo esc_attr($cost_per_ad); ?>" />
        </p>
        <?php
    }

    public static function save_package_meta($post_id) {
        if (!isset($_POST['lmb_package_nonce']) || !wp_verify_nonce($_POST['lmb_package_nonce'], 'lmb_save_package_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['lmb_package_price'])) {
            update_post_meta($post_id, 'price', sanitize_text_field($_POST['lmb_package_price']));
        }
        if (isset($_POST['lmb_package_points'])) {
            update_post_meta($post_id, 'points', sanitize_text_field($_POST['lmb_package_points']));
        }
        if (isset($_POST['lmb_package_cost_per_ad'])) {
            update_post_meta($post_id, 'cost_per_ad', sanitize_text_field($_POST['lmb_package_cost_per_ad']));
        }
    }

    public static function register_statuses() {
        register_post_status('pending_review', [
            'label'                     => _x('Pending Review', 'post', 'lmb-core'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>'),
        ]);

        register_post_status('denied', [
            'label'                     => _x('Denied', 'post', 'lmb-core'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Denied <span class="count">(%s)</span>', 'Denied <span class="count">(%s)</span>'),
        ]);
    }
}