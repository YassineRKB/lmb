<?php
if (!defined('ABSPATH')) exit;

final class LMB_Elementor_Widgets_Helper {
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

add_action('elementor/widgets/register', function($widgets_manager){
    require_once __DIR__.'/widgets/class-lmb-ads-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-newspaper-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-subscribe-package-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-bank-proof-widget.php';
    require_once __DIR__.'/widgets/class-lmb-admin-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-user-dashboard-widget.php';

    $widgets_manager->register(new \LMB_Ads_Directory_Widget());
    $widgets_manager->register(new \LMB_Newspaper_Directory_Widget());
    $widgets_manager->register(new \LMB_Subscribe_Package_Widget());
    $widgets_manager->register(new \LMB_Upload_Bank_Proof_Widget());
    $widgets_manager->register(new \LMB_Admin_Stats_Widget());
    $widgets_manager->register(new \LMB_User_Dashboard_Widget());
});
