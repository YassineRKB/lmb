<?php
if (!defined('ABSPATH')) exit;

// Register the custom "LMB Core Widgets" category in Elementor
add_action('elementor/elements/categories_registered', function($elements_manager) {
    // old User-Facing Category
    $elements_manager->add_category(
        'lmb-user-widgets',
        [
            'title' => __('LMB USER WIDGETS', 'lmb-core'),
            'icon' => 'eicon-user-circle-o',
        ]
    );
    
    // old Admin-Facing Category
    $elements_manager->add_category(
        'lmb-admin-widgets',
        [
            'title' => __('LMB ADMIN WIDGETS', 'lmb-core'),
            'icon' => 'eicon-lock-user',
        ]
    );

    // New V2 User-Facing Category
    $elements_manager->add_category(
        'lmb-user-widgets-v2',
        [
            'title' => __('LMB USERS V2', 'lmb-core'),
            'icon' => 'eicon-user-circle-o',
        ]
    );
    
    // New V2 Admin-Facing Category
    $elements_manager->add_category(
        'lmb-admin-widgets-v2',
        [
            'title' => __('LMB ADMINS V2', 'lmb-core'),
            'icon' => 'eicon-lock-user',
        ]
    );
});

/**
 * --- FIX APPLIED HERE ---
 * This block explicitly loads each widget's file right before it's needed.
 * This prevents any "Class not found" fatal errors.
 */
add_action('elementor/widgets/register', function($widgets_manager) {
    $widgets = [
        'LMB_Admin_Stats_Widget' => __DIR__ . '/widgets/class-lmb-admin-stats-widget.php',
        'LMB_Upload_Newspaper_Widget' => __DIR__ . '/widgets/class-lmb-upload-newspaper-widget.php',
        'LMB_Notifications_Widget' => __DIR__ . '/widgets/class-lmb-notifications-widget.php',
        'LMB_User_Stats_Widget' => __DIR__ . '/widgets/class-lmb-user-stats-widget.php',
        'LMB_Subscribe_Package_Widget' => __DIR__ . '/widgets/class-lmb-subscribe-package-widget.php',
        'LMB_Upload_Bank_Proof_Widget' => __DIR__ . '/widgets/class-lmb-upload-bank-proof-widget.php',
        'LMB_Ads_Directory_Widget' => __DIR__ . '/widgets/class-lmb-ads-directory-widget.php',
        'LMB_Newspaper_Directory_Widget' => __DIR__ . '/widgets/class-lmb-newspaper-directory-widget.php',
        'LMB_Packages_Editor_Widget' => __DIR__ . '/widgets/class-lmb-packages-editor-widget.php',
        'LMB_Invoices_Widget' => __DIR__ . '/widgets/class-lmb-invoices-widget.php',
        'LMB_Final_Newspapers_List_Widget' => __DIR__ . '/widgets/class-lmb-final-newspapers-list-widget.php',
        //v2 widgets
        'LMB_Legal_Ads_Management_V2_Widget' => __DIR__ . '/widgets/class-lmb-legal-ads-management-v2-widget.php',
        'LMB_My_Legal_Ads_V2_Widget' => __DIR__ . '/widgets/class-lmb-my-legal-ads-v2-widget.php',
        'LMB_Feed_V2_Widget' => __DIR__ . '/widgets/class-lmb-feed-v2-widget.php',
        'LMB_Auth_V2_Widget' => __DIR__ . '/widgets/class-lmb-auth-v2-widget.php',
        'LMB_Inactive_Clients_V2_Widget' => __DIR__ . '/widgets/class-lmb-inactive-clients-v2-widget.php',
        'LMB_Active_Clients_V2_Widget' => __DIR__ . '/widgets/class-lmb-active-clients-v2-widget.php',
        'LMB_Profile_V2_Widget' => __DIR__ . '/widgets/class-lmb-profile-v2-widget.php',
        'LMB_Payments_Management_Widget' => __DIR__ . '/widgets/class-lmb-payments-management-widget.php',
        'LMB_User_Editor_Widget' => __DIR__ . '/widgets/class-lmb-user-editor-widget.php',
        'LMB_Public_Ads_Dir_Widget' => __DIR__ . '/widgets/class-lmb-public-ads-dir-widget.php',
        'LMB_Generate_Newspaper_Widget' => __DIR__ . '/widgets/class-lmb-generate-newspaper-widget.php',
        'LMB_Balance_Manipulation_Widget' => __DIR__ . '/widgets/class-lmb-balance-manipulation-widget.php',
        'LMB_Admin_Subscribe_User_Widget' => __DIR__ . '/widgets/class-lmb-admin-subscribe-user-widget.php', // <-- NEW WIDGET ADDED
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