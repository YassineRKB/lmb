<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core (auth guard by slug, points, ads CPT, newspapers, PDFs, invoices, directories).
 * Version: 1.0.0
 * Author: LMB
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define('LMB_CORE_VERSION', '1.0.0');
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL', plugin_dir_url(__FILE__));

// --- Includes ---
require_once LMB_CORE_PATH . 'includes/class-lmb-cpt.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-error-handler.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-database-manager.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-points.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-access-control.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-acf.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-ad-manager.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-admin.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-form-handler.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-invoice-handler.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-notifications-manager.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-pdf-generator.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-user-dashboard.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-user.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-payment-verifier.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-maintenance_utilities.php';

// Elementor Widgets
require_once LMB_CORE_PATH . 'elementor/class-lmb-elementor-widgets.php';

// Add custom shortcodes as a fallback since you mentioned they work.
add_shortcode('lmb_ads_directory', ['LMB_Elementor_Widgets_Helper', 'ads_directory_shortcode']);
add_shortcode('lmb_newspaper_directory', ['LMB_Elementor_Widgets_Helper', 'newspaper_directory_shortcode']);
add_shortcode('lmb_invoice_widget', ['LMB_Elementor_Widgets_Helper', 'invoice_widget_shortcode']);

// Activation hook
register_activation_hook(__FILE__, function () {
    // Create custom database tables
    LMB_Database_Manager::create_custom_tables();
    
    // Add default options if they don't exist
    add_option('lmb_points_per_ad', 1);
    add_option('lmb_protected_slugs', "/dashboard\n/administration");
    add_option('lmb_staff_roles', "administrator,editor");
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function () {
    // Clear scheduled events
    wp_clear_scheduled_hook('lmb_daily_maintenance');
    
    // Flush rewrite rules
    flush_rewrite_rules();
});