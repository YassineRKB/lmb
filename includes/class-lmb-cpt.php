<?php
if (!defined('ABSPATH')) { exit; }

class LMB_CPT {
    public static function register() {
        // Legal Ad
        register_post_type('legal_ad', [
            'labels' => [
                'name' => __('Legal Ads','lmb-core'),
                'singular_name' => __('Legal Ad','lmb-core'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title','editor','author','custom-fields'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-media-text',
        ]);

        // Newspaper
        register_post_type('newspaper', [
            'labels' => [
                'name' => __('Newspapers','lmb-core'),
                'singular_name' => __('Newspaper','lmb-core'),
            ],
            'public' => true,
            'has_archive' => true,
            'show_ui' => true,
            'supports' => ['title','editor','thumbnail','custom-fields'],
            'menu_icon' => 'dashicons-media-document',
        ]);

        // Package (admin-defined)
        register_post_type('lmb_package', [
            'labels' => [
                'name' => __('Packages','lmb-core'),
                'singular_name' => __('Package','lmb-core'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title','editor','custom-fields'],
            'menu_icon' => 'dashicons-products',
        ]);

        // Order (user subscription intent; proof + verification)
        register_post_type('lmb_order', [
            'labels' => [
                'name' => __('Orders','lmb-core'),
                'singular_name' => __('Order','lmb-core'),
            ],
            'public' => false,
            'show_ui' => true,
            'supports' => ['title','author','custom-fields'],
            'menu_icon' => 'dashicons-clipboard',
        ]);
    }

    public static function register_statuses() {
        register_post_status('pending_admin', [
            'label' => _x('Pending Admin','Status','lmb-core'),
            'public' => false,
            'internal' => true,
            'protected' => true,
            'label_count' => _n_noop('Pending Admin (%s)','Pending Admin (%s)')
        ]);

        register_post_status('denied', [
            'label' => _x('Denied','Status','lmb-core'),
            'public' => false,
            'internal' => true,
            'protected' => true,
            'label_count' => _n_noop('Denied (%s)','Denied (%s)')
        ]);

        // Orders
        register_post_status('proof_submitted', [
            'label' => _x('Proof Submitted','Status','lmb-core'),
            'public' => false,
            'internal' => true,
            'protected' => true,
            'label_count' => _n_noop('Proof Submitted (%s)','Proof Submitted (%s)')
        ]);

        register_post_status('paid_verified', [
            'label' => _x('Paid & Verified','Status','lmb-core'),
            'public' => false,
            'internal' => true,
            'protected' => true,
            'label_count' => _n_noop('Paid & Verified (%s)','Paid & Verified (%s)')
        ]);
    }
}