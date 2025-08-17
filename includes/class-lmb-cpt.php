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
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-media-text',
            'show_in_menu' => 'lmb-core',
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

        // Payment (Bank proof + subscriptions)
        register_post_type('lmb_payment', [
            'label' => __('Payments', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-money-alt',
            'show_in_menu' => 'lmb-core',
        ]);

        // Package (for subscriptions)
        register_post_type('lmb_package', [
            'label' => __('Packages', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-admin-generic',
            'show_in_menu' => 'lmb-core',
        ]);
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
