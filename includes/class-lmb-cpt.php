<?php
if (!defined('ABSPATH')) { exit; }

class LMB_CPT {
    public static function init() {
        add_action('init', [__CLASS__, 'register_cpts']);
    }

    public static function register_cpts() {
        register_post_type('lmb_legal_ad', [
            'label' => __('Legal Ads', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-media-text',
            'show_in_menu' => 'lmb-core',
        ]);

        register_post_type('lmb_newspaper', [
            'label' => __('Newspapers', 'lmb-core'),
            'public' => true,
            'show_ui' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'newspapers'],
            'supports' => ['title', 'thumbnail'],
            'menu_icon' => 'dashicons-media-document',
            'show_in_menu' => 'lmb-core',
        ]);

        register_post_type('lmb_points_ledger', [
            'label' => __('Points Ledger', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-clipboard',
            'show_in_menu' => 'lmb-core',
        ]);

        register_post_type('lmb_payment', [
            'label' => __('Payments', 'lmb-core'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-money-alt',
            'show_in_menu' => 'lmb-core',
        ]);
    }
}
LMB_CPT::init();
