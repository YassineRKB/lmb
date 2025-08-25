<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core (auth, CPTs, points, invoices, payments, PDFs, directories, dashboards).
 * Version: 3.0.0 Sparta Fix
 * Author: Yassine Rakibi
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LMB_CORE_VERSION', '3.0.0');
define('LMB_CORE_FILE', __FILE__);
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL', plugin_dir_url(__FILE__));

// Load translations
add_action('init', function() {
    load_plugin_textdomain('lmb-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Autoloader
spl_autoload_register(function($class) {
    if (strpos($class, 'LMB_') !== 0) return;
    $directories = ['includes/', 'elementor/widgets/'];
    $file = 'class-' . str_replace('_', '-', strtolower($class)) . '.php';
    foreach ($directories as $dir) {
        $path = LMB_CORE_PATH . $dir . $file;
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Activation Hook
register_activation_hook(__FILE__, function () {
    LMB_CPT::init();
    LMB_User::create_custom_roles();
    LMB_Database_Manager::create_custom_tables();
    flush_rewrite_rules();
    
    // Default settings
    add_option('lmb_bank_name', 'Your Bank Name');
    add_option('lmb_bank_iban', 'YOUR-IBAN-RIB-HERE');
    add_option('lmb_default_cost_per_ad', 1);
    add_option('lmb_enable_email_notifications', 1);
    add_option('lmb_admin_feed_refresh_interval', 30); // Default 30 seconds
    add_option('lmb_invoice_template_html', '<h1>Invoice {{invoice_number}}</h1><p>Date: {{invoice_date}}</p><hr><h3>Client Details</h3><p>Name: {{user_name}}<br>Email: {{user_email}}</p><hr><h3>Item Details</h3><p><strong>Package:</strong> {{package_name}}<br><strong>Price:</strong> {{package_price}} MAD</p><p><strong>Payment Reference:</strong> {{payment_reference}}</p><hr><h3>Payment Instructions</h3><p>Please make a bank transfer to:<br><strong>Bank:</strong> {{our_bank_name}}<br><strong>IBAN/RIB:</strong> {{our_iban}}</p>');
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('lmb_daily_maintenance');
    flush_rewrite_rules();
});


// Initialize all plugin components
add_action('plugins_loaded', function() {
    LMB_Error_Handler::init();
    LMB_Access_Control::init();
    require_once LMB_CORE_PATH . 'elementor/class-lmb-elementor-widgets.php';
    LMB_CPT::init();
    LMB_Form_Handler::init();
    LMB_Ad_Manager::init();
    LMB_Payment_Verifier::init();
    LMB_Admin::init();
    LMB_User_Dashboard::init();
    LMB_Database_Manager::init();
    LMB_Invoice_Handler::init();
    LMB_Ajax_Handlers::init();
    LMB_Notification_Manager::init();
    new LMB_User();
});

/**
 * Enqueue scripts and styles and unify AJAX parameters.
 */
function lmb_enqueue_assets() {
    // Register main plugin style
    wp_register_style('lmb-core', LMB_CORE_URL . 'assets/css/lmb-core.css', [], LMB_CORE_VERSION);
    wp_enqueue_style('lmb-core');

    // Register main plugin script
    wp_register_script('lmb-core', LMB_CORE_URL . 'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);

    // Unified AJAX parameters for both frontend and admin
    $ajax_params = [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lmb_nonce'), // Unified nonce
    ];
    wp_localize_script('lmb-core', 'lmb_ajax_params', $ajax_params);
    wp_enqueue_script('lmb-core');
}
add_action('wp_enqueue_scripts', 'lmb_enqueue_assets');
add_action('admin_enqueue_scripts', 'lmb_enqueue_assets');


/**
 * Registers widget-specific assets for Elementor.
 */
function lmb_register_widget_assets() {
    // Scripts
    $scripts = [
        'lmb-admin-actions' => 'assets/js/lmb-admin-actions.js',
        'lmb-notifications' => 'assets/js/lmb-notifications.js',
    ];
    foreach ($scripts as $handle => $path) {
        wp_register_script($handle, LMB_CORE_URL . $path, ['jquery', 'lmb-core'], LMB_CORE_VERSION, true);
    }
    // Pass admin settings to the admin-actions script
    wp_localize_script('lmb-admin-actions', 'lmb_admin_settings', [
        'refresh_interval' => (int) get_option('lmb_admin_feed_refresh_interval', 30) * 1000, // in ms
    ]);

    // Styles
    $styles = [
        'lmb-admin-widgets' => 'assets/css/lmb-admin-widgets.css',
    ];
    foreach ($styles as $handle => $path) {
        wp_register_style($handle, LMB_CORE_URL . $path, [], LMB_CORE_VERSION);
    }
}
add_action('elementor/frontend/after_register_scripts', 'lmb_register_widget_assets');
add_action('elementor/editor/before_enqueue_scripts', 'lmb_register_widget_assets');
// documentation is hard dont u agree.
// suffer the same pain as i, my younge padwan
// this is a pain in the buttocks to finish in tight deadlines
// but we made it, we are the chosen ones yay