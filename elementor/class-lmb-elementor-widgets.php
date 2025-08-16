<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Elementor\Widget_Base;

class LMB_Elementor_Widgets {

    public function __construct() {
        add_action('elementor/widgets/register', array($this, 'register_widgets'));
    }

    public function register_widgets($widgets_manager) {
        require_once LMB_CORE_PATH . 'elementor/widgets/class-lmb-ads-directory-widget.php';
        require_once LMB_CORE_PATH . 'elementor/widgets/class-lmb-newspaper-directory-widget.php';
        require_once LMB_CORE_PATH . 'elementor/widgets/class-lmb-invoice-widget.php';

        $widgets_manager->register(new LMB_Ads_Directory_Widget());
        $widgets_manager->register(new LMB_Newspaper_Directory_Widget());
        $widgets_manager->register(new LMB_Invoice_Widget());
    }
}

new LMB_Elementor_Widgets();

class LMB_Elementor_Widgets_Helper {
    public static function ads_directory_shortcode() {
        ob_start();
        $widget = new LMB_Ads_Directory_Widget();
        $widget->render();
        return ob_get_clean();
    }
    public static function newspaper_directory_shortcode() {
        ob_start();
        $widget = new LMB_Newspaper_Directory_Widget();
        $widget->render();
        return ob_get_clean();
    }
    public static function invoice_widget_shortcode() {
        ob_start();
        $widget = new LMB_Invoice_Widget();
        $widget->render();
        return ob_get_clean();
    }
}