<?php
if (!defined('ABSPATH')) exit;

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
    // Base class that all form widgets will use
    require_once __DIR__.'/widgets/class-lmb-form-widget-base.php';
    
    // --- Your New Form Widgets ---
    require_once __DIR__.'/widgets/class-lmb-form-constitution-sarl.php';
    require_once __DIR__.'/widgets/class-lmb-form-constitution-sarl-au.php';
    require_once __DIR__.'/widgets/class-lmb-form-modification-siege.php';
    require_once __DIR__.'/widgets/class-lmb-form-modification-objet.php';
    require_once __DIR__.'/widgets/class-lmb-form-modification-gerant.php';
    require_once __DIR__.'/widgets/class-lmb-form-modification-denomination.php';
    require_once __DIR__.'/widgets/class-lmb-form-modification-capital.php';
    require_once __DIR__.'/widgets/class-lmb-form-modification-cession.php';
    require_once __DIR__.'/widgets/class-lmb-form-dissolution-anticipee.php';
    require_once __DIR__.'/widgets/class-lmb-form-dissolution-cloture.php';

    // --- Other Existing Widgets ---
    require_once __DIR__.'/widgets/class-lmb-admin-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-admin-actions-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-newspaper-widget.php';
    require_once __DIR__.'/widgets/class-lmb-notifications-widget.php';
    require_once __DIR__.'/widgets/class-lmb-user-stats-widget.php';
    require_once __DIR__.'/widgets/class-lmb-subscribe-package-widget.php';
    require_once __DIR__.'/widgets/class-lmb-upload-bank-proof-widget.php';
    require_once __DIR__.'/widgets/class-lmb-ads-directory-widget.php';
    require_once __DIR__.'/widgets/class-lmb-newspaper-directory-widget.php';

    // --- Register New Form Widgets ---
    $widgets_manager->register(new \LMB_Form_Constitution_Sarl_Widget());
    $widgets_manager->register(new \LMB_Form_Constitution_Sarl_Au_Widget());
    $widgets_manager->register(new \LMB_Form_Modification_Siege_Widget());
    $widgets_manager->register(new \LMB_Form_Modification_Objet_Widget());
    $widgets_manager->register(new \LMB_Form_Modification_Gerant_Widget());
    $widgets_manager->register(new \LMB_Form_Modification_Denomination_Widget());
    $widgets_manager->register(new \LMB_Form_Modification_Capital_Widget());
    $widgets_manager->register(new \LMB_Form_Modification_Cession_Widget());
    $widgets_manager->register(new \LMB_Form_Dissolution_Anticipee_Widget());
    $widgets_manager->register(new \LMB_Form_Dissolution_Cloture_Widget());

    // --- Register Other Existing Widgets ---
    $widgets_manager->register(new \LMB_Admin_Stats_Widget());
    $widgets_manager->register(new \LMB_Admin_Actions_Widget());
    $widgets_manager->register(new \LMB_Upload_Newspaper_Widget());
    $widgets_manager->register(new \LMB_Notifications_Widget());
    $widgets_manager->register(new \LMB_User_Stats_Widget());
    $widgets_manager->register(new \LMB_Subscribe_Package_Widget());
    $widgets_manager->register(new \LMB_Upload_Bank_Proof_Widget());
    $widgets_manager->register(new \LMB_Ads_Directory_Widget());
    $widgets_manager->register(new \LMB_Newspaper_Directory_Widget());
});