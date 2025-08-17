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
});

// Register all of the individual widgets
add_action('elementor/widgets/register', function($widgets_manager){
    require_once __DIR__.'/widgets/class-lmb-admin-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-admin-actions-widget.php';
    require_once __DIR__.'/widgets/class-lmb-user-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-subscribe-package-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-bank-proof-widget.php';
    require_once __DIR__.'/widgets/class-lmb-ads-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-newspaper-directory-widget.php';

    $widgets_manager->register(new \LMB_Admin_Stats_Widget());
    $widgets_manager->register(new \LMB_Admin_Actions_Widget());
    $widgets_manager->register(new \LMB_User_Stats_Widget());
    $widgets_manager->register(new \LMB_Subscribe_Package_Widget());
    $widgets_manager->register(new \LMB_Upload_Bank_Proof_Widget());
    $widgets_manager->register(new \LMB_Ads_Directory_Widget());
    $widgets_manager->register(new \LMB_Newspaper_Directory_Widget());
});