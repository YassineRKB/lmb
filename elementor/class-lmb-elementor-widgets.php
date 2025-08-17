<?php
if (!defined('ABSPATH')) exit;

// ... (Helper class remains the same)

add_action('elementor/elements/categories_registered', function($elements_manager) {
    $elements_manager->add_category(
        'lmb-widgets',
        [
            'title' => __('LMB Core Widgets', 'lmb-core'),
            'icon' => 'eicon-folder',
        ]
    );
});

add_action('elementor/widgets/register', function($widgets_manager){
    // Include all widget files
    require_once __DIR__.'/widgets/class-lmb-admin-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-admin-actions-widget.php';
    require_once __DIR__.'/widgets/class-lmb-user-stats-widget.php'; // New
    require_once __DIR__.'/widgets/class-lmb-subscribe-package-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-bank-proof-widget.php';
    require_once __DIR__.'/widgets/class-lmb-ads-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-newspaper-directory-widget.php';

    // Register all widgets
    $widgets_manager->register(new \LMB_Admin_Stats_Widget());
    $widgets_manager->register(new \LMB_Admin_Actions_Widget());
    $widgets_manager->register(new \LMB_User_Stats_Widget()); // New
    $widgets_manager->register(new \LMB_Subscribe_Package_Widget());
    $widgets_manager->register(new \LMB_Upload_Bank_Proof_Widget());
    $widgets_manager->register(new \LMB_Ads_Directory_Widget());
    $widgets_manager->register(new \LMB_Newspaper_Directory_Widget());
});