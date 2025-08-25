<?php
if (!defined('ABSPATH')) exit;

final class LMB_Elementor_Widgets_Helper {
    // These shortcodes act as fallbacks if you need to use the widgets outside of Elementor
    public static function ads_directory_shortcode($atts=[]) {
        ob_start();
        the_widget('LMB_Ads_Directory_Widget');
        return ob_get_clean();
    }
    public static function newspaper_directory_shortcode($atts=[]) {
        ob_start();
        the_widget('LMB_Newspaper_Directory_Widget');
        return ob_get_clean();
    }
}

// Register the custom "LMB Core Widgets" category in Elementor
add_action('elementor/elements/categories_registered', function($elements_manager) {
    $elements_manager->add_category(
        'lmb-cw-admin',
        [
            'title' => __('LMB Core Admin Widgets', 'lmb-core'),
            'icon' => 'eicon-folder',
        ]
    );
    
    // Add new LMB-2 category
    $elements_manager->add_category(
        'lmb-cw-user',
        [
            'title' => __('LMB Core User Widgets', 'lmb-core'),
            'icon' => 'eicon-apps',
        ]
    );
});

/**
 * --- FIX APPLIED HERE MY DEAR YOUNG PADWAN---
 * This block explicitly loads each widget's file right before it's needed.
 * This prevents any "Class not found" fatal errors that cause the 500 error on save.
 * if you have headaches its good time to meditate for 5min and get back to work
 * if your immunity is getting weak, maybe an std test will do, if that comes out fine
 * drink orange juice for a boost
 */
add_action('elementor/widgets/register', function($widgets_manager) {
    // An array mapping widget class names to their file paths
    $widgets = [
        'LMB_Admin_Stats_Widget' => __DIR__ . '/widgets/class-lmb-admin-stats-widget.php',
        'LMB_Admin_Actions_Widget' => __DIR__ . '/widgets/class-lmb-admin-actions-widget.php',
        'LMB_Upload_Newspaper_Widget' => __DIR__ . '/widgets/class-lmb-upload-newspaper-widget.php',
        'LMB_Notifications_Widget' => __DIR__ . '/widgets/class-lmb-notifications-widget.php',
        'LMB_User_Stats_Widget' => __DIR__ . '/widgets/class-lmb-user-stats-widget.php',
        'LMB_Subscribe_Package_Widget' => __DIR__ . '/widgets/class-lmb-subscribe-package-widget.php',
        'LMB_Upload_Bank_Proof_Widget' => __DIR__ . '/widgets/class-lmb-upload-bank-proof-widget.php',
        'LMB_Ads_Directory_Widget' => __DIR__ . '/widgets/class-lmb-ads-directory-widget.php',
        'LMB_Newspaper_Directory_Widget' => __DIR__ . '/widgets/class-lmb-newspaper-directory-widget.php',
        'LMB_Upload_Accuse_Widget' => __DIR__ . '/widgets/class-lmb-upload-accuse-widget.php',
        'LMB_User_List_Widget' => __DIR__ . '/widgets/class-lmb-user-list-widget.php',
        'LMB_Legal_Ads_List_Widget' => __DIR__ . '/widgets/class-lmb-legal-ads-list-widget.php',
        'LMB_Balance_Manipulation_Widget' => __DIR__ . '/widgets/class-lmb-balance-manipulation-widget.php',
        'LMB_Packages_Editor_Widget' => __DIR__ . '/widgets/class-lmb-packages-editor-widget.php',
        'LMB_Invoices_Widget' => __DIR__ . '/widgets/class-lmb-invoices-widget.php',
        'LMB_Legal_Ads_Receipts_Widget' => __DIR__ . '/widgets/class-lmb-legal-ads-receipts-widget.php',
    ];

    foreach ($widgets as $class_name => $file_path) {
        if (file_exists($file_path)) {
            require_once $file_path;
            if (class_exists($class_name)) {
                $widgets_manager->register(new $class_name());
            }
        }
    }
});