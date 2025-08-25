<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core (auth, CPTs, points, invoices, payments, PDFs, directories, dashboards).
 * Version: 2.2.3
 * Author: Yassine Rakibi
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LMB_CORE_VERSION', '2.2.3');
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
    add_option('lmb_bank_name', 'Your Bank Name');
    add_option('lmb_bank_iban', 'YOUR-IBAN-RIB-HERE');
    add_option('lmb_default_cost_per_ad', 1);
    add_option('lmb_enable_email_notifications', 1);
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

// Enqueue Frontend Scripts & Styles
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('lmb-core', LMB_CORE_URL . 'assets/css/lmb-core.css', [], LMB_CORE_VERSION);
    wp_enqueue_style('lmb-user-widgets', LMB_CORE_URL . 'assets/css/lmb-user-widgets.css', [], LMB_CORE_VERSION);
    wp_enqueue_script('lmb-core', LMB_CORE_URL . 'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);

    wp_localize_script('lmb-core', 'lmbAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lmb_frontend_ajax_nonce'),
    ]);
});

// Enqueue Admin Scripts & Styles
add_action('admin_enqueue_scripts', function($hook) {
    wp_enqueue_style('lmb-admin-styles', LMB_CORE_URL . 'assets/css/admin.css', [], LMB_CORE_VERSION);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');

    wp_enqueue_script('lmb-admin-scripts', LMB_CORE_URL . 'assets/js/admin.js', ['jquery'], LMB_CORE_VERSION, true);
    wp_localize_script('lmb-admin-scripts', 'lmbAdmin', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lmb_admin_ajax_nonce'),
    ]);
});

/**
 * Registers and enqueues widget-specific scripts and styles for Elementor.
 * This is the standardized way to handle widget assets.
 */
function lmb_register_widget_assets() {
    // Register Scripts
    $scripts = [
        'lmb-balance-manipulation' => 'assets/js/lmb-balance-manipulation.js',
        'lmb-packages-editor' => 'assets/js/lmb-packages-editor.js',
        'lmb-invoices' => 'assets/js/lmb-invoices.js',
        'lmb-legal-ads-receipts' => 'assets/js/lmb-legal-ads-receipts.js',
    ];
    foreach ($scripts as $handle => $path) {
        wp_register_script($handle, LMB_CORE_URL . $path, ['jquery'], LMB_CORE_VERSION, true);
    }

    // Register Styles
    $styles = [
        'lmb-admin-widgets' => 'assets/css/lmb-admin-widgets.css',
        'lmb-user-widgets' => 'assets/css/lmb-user-widgets.css',
    ];
    foreach ($styles as $handle => $path) {
        wp_register_style($handle, LMB_CORE_URL . $path, [], LMB_CORE_VERSION);
    }
}
add_action('elementor/frontend/after_register_scripts', 'lmb_register_widget_assets');
add_action('elementor/editor/before_enqueue_scripts', 'lmb_register_widget_assets');
// documentation is hard dont u agree.
// suffer the same pain as i, my younge padwan