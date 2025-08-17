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
            'has_archive' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-media-text',
            'show_in_menu' => 'lmb-core',
            'rewrite' => ['slug' => 'announces'],
        ]);

        // Newspaper
        register_post_type('lmb_newspaper', [
            'label' => __('Newspapers', 'lmb-core'),
            'public' => true,
            'has_archive' => true,
            'supports' => ['title'],
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
        
        // Add meta boxes for legal ads
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
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
    }
    
    public static function render_ad_actions_metabox($post) {
        $status = get_field('lmb_status', $post->ID);
        $client_id = get_field('lmb_client_id', $post->ID);
        $client = get_userdata($client_id);
        
        echo '<div class="lmb-ad-actions">';
        echo '<p><strong>Client:</strong> ' . ($client ? esc_html($client->display_name) : 'Unknown') . '</p>';
        echo '<p><strong>Status:</strong> ' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</p>';
        
        if ($status === 'pending_review' && current_user_can('edit_others_posts')) {
            wp_nonce_field('lmb_admin_accept_ad', '_lmb_nonce');
            echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="margin: 10px 0;">';
            echo '<input type="hidden" name="action" value="lmb_admin_accept_ad">';
            echo '<input type="hidden" name="ad_id" value="' . $post->ID . '">';
            echo '<button type="submit" class="button button-primary">Approve & Publish</button>';
            echo '</form>';
            
            echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="margin: 10px 0;">';
            echo '<input type="hidden" name="action" value="lmb_admin_deny_ad">';
            echo '<input type="hidden" name="ad_id" value="' . $post->ID . '">';
            echo '<button type="submit" class="button">Deny</button>';
            echo '</form>';
        }
        
        $pdf_url = get_field('ad_pdf_url', $post->ID);
        if ($pdf_url) {
            echo '<p><a href="' . esc_url($pdf_url) . '" target="_blank" class="button">Download PDF</a></p>';
        }
        
        $invoice_url = get_post_meta($post->ID, 'ad_invoice_pdf_url', true);
        if ($invoice_url) {
            echo '<p><a href="' . esc_url($invoice_url) . '" target="_blank" class="button">Download Invoice</a></p>';
        }
        
        echo '</div>';
    }

    public static function register_statuses() {
        register_post_status('pending_review', [
            'label'                     => _x('Pending Review', 'post', 'lmb-core'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);

        register_post_status('lmb_denied', [
            'label'                     => _x('Denied', 'post', 'lmb-core'),
            'public'                    => false,
            'internal'                  => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
        ]);
    }
}
