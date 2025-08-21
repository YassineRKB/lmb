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
        'lmb-widgets',
        [
            'title' => __('LMB Core Widgets', 'lmb-core'),
            'icon' => 'eicon-folder',
        ]
    );
    
    // Add new LMB-2 category
    $elements_manager->add_category(
        'lmb-2',
        [
            'title' => __('LMB-2 Widgets', 'lmb-core'),
            'icon' => 'eicon-apps',
        ]
    );
});

// Register all of the individual widgets
add_action('elementor/widgets/register', function($widgets_manager){
    require_once __DIR__.'/widgets/class-lmb-admin-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-admin-actions-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-newspaper-widget.php';
    require_once __DIR__.'/widgets/class-lmb-notifications-widget.php';
    require_once __DIR__.'/widgets/class-lmb-user-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-subscribe-package-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-bank-proof-widget.php';
    require_once __DIR__.'/widgets/class-lmb-ads-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-newspaper-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-accuse-widget.php';
    
    // Register LMB-2 widgets
    require_once __DIR__.'/widgets/class-lmb-user-list-widget.php';
    require_once __DIR__.'/widgets/class-lmb-legal-ads-list-widget.php';
    require_once __DIR__.'/widgets/class-lmb-balance-manipulation-widget.php';
    require_once __DIR__.'/widgets/class-lmb-packages-editor-widget.php';
    require_once __DIR__.'/widgets/class-lmb-invoices-widget.php';
    require_once __DIR__.'/widgets/class-lmb-legal-ads-receipts-widget.php';

    $widgets_manager->register(new \LMB_Admin_Stats_Widget());
    $widgets_manager->register(new \LMB_Admin_Actions_Widget());
    $widgets_manager->register(new \LMB_Upload_Newspaper_Widget());
    $widgets_manager->register(new \LMB_Notifications_Widget());
    $widgets_manager->register(new \LMB_User_Stats_Widget());
    $widgets_manager->register(new \LMB_Subscribe_Package_Widget());
    $widgets_manager->register(new \LMB_Upload_Bank_Proof_Widget());
    $widgets_manager->register(new \LMB_Ads_Directory_Widget());
    $widgets_manager->register(new \LMB_Newspaper_Directory_Widget());
    $widgets_manager->register(new \LMB_Upload_Accuse_Widget());
    
    // Register LMB-2 widgets
    $widgets_manager->register(new \LMB_User_List_Widget());
    $widgets_manager->register(new \LMB_Legal_Ads_List_Widget());
    $widgets_manager->register(new \LMB_Balance_Manipulation_Widget());
    $widgets_manager->register(new \LMB_Packages_Editor_Widget());
    $widgets_manager->register(new \LMB_Invoices_Widget());
    $widgets_manager->register(new \LMB_Legal_Ads_Receipts_Widget());
});