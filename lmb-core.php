<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core (auth, CPTs, points, invoices, payments, PDFs, directories, dashboards).
 * Version: 1.2.2
 * Author: LMB
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) exit;

define('LMB_CORE_VERSION', '1.2.2');
define('LMB_CORE_FILE', __FILE__);
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL',  plugin_dir_url(__FILE__));

/**
 * Autoloader for LMB classes.
 * Enforces a strict naming convention: A class like 'LMB_Access_Control'
 * MUST be in a file named 'class-lmb-access-control.php'.
 */
spl_autoload_register(function($class){
    if (strpos($class, 'LMB_') !== 0) {
        return;
    }

    // Converts 'LMB_Access_Control' to 'lmb-access-control'
    $file_part = str_replace('_', '-', strtolower($class));
    
    // Prepends 'class-' and appends '.php'
    $file = 'class-' . $file_part . '.php';
    
    $path = LMB_CORE_PATH . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
});

/** Elementor widgets */
require_once LMB_CORE_PATH.'elementor/class-lmb-elementor-widgets.php';

/** Shortcodes (fallbacks) */
add_shortcode('lmb_ads_directory', ['LMB_Elementor_Widgets_Helper', 'ads_directory_shortcode']);
add_shortcode('lmb_newspaper_directory', ['LMB_Elementor_Widgets_Helper', 'newspaper_directory_shortcode']);

/** Activation */
register_activation_hook(__FILE__, function () {
    // Default settings
    add_option('lmb_invoice_template_html', '<h1>Invoice {{invoice_number}}</h1><p>User: {{user_name}} (ID {{user_id}})</p><p>Package: {{package_name}}</p><p>Price: {{package_price}} MAD</p><p>Bank: {{our_bank_name}}</p><p>IBAN/RIB: {{our_iban}}</p><p>Reference: {{payment_reference}}</p><p>Date: {{invoice_date}}</p>');
    add_option('lmb_protected_slugs', "/dashboard\n/administration");
    add_option('lmb_staff_roles', "administrator,editor");
    add_option('lmb_default_cost_per_ad', 1);
    add_option('lmb_bank_name', 'Your Bank Name');
    add_option('lmb_bank_iban', 'YOUR-IBAN-RIB-HERE');

    // Register CPTs, statuses, rewrites
    LMB_CPT::init();
    LMB_User::create_custom_roles();
    LMB_Database_Manager::create_custom_tables();
    flush_rewrite_rules();
});

/** Deactivation */
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('lmb_daily_maintenance');
    flush_rewrite_rules();
});

/** Init bootstrap */
add_action('plugins_loaded', function(){
    LMB_Access_Control::init();
    LMB_CPT::init();
    LMB_Form_Handler::init();
    LMB_Ad_Manager::init();
    LMB_Payment_Verifier::init();
    LMB_Admin::init();
    LMB_User_Dashboard::init();
    LMB_Notification_Manager::init();
    LMB_Database_Manager::init();
    LMB_Error_Handler::init();
    new LMB_User();
});

/** Assets */
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('lmb-core', LMB_CORE_URL.'assets/css/lmb-core.css', [], LMB_CORE_VERSION);
    wp_enqueue_script('lmb-core', LMB_CORE_URL.'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);
});

/** Admin Assets */
add_action('admin_enqueue_scripts', function($hook) {
    wp_enqueue_style('lmb-admin', LMB_CORE_URL.'assets/css/admin.css', [], LMB_CORE_VERSION);
    
    $screen = get_current_screen();
    if (is_object($screen) && in_array($screen->post_type, ['lmb_legal_ad', 'lmb_payment'])) {
        wp_enqueue_script('lmb-admin', LMB_CORE_URL.'assets/js/admin.js', ['jquery'], LMB_CORE_VERSION, true);
        wp_localize_script('lmb-admin', 'lmbAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_admin_nonce'),
            'strings' => [ /* ... */ ]
        ]);
    }
    
    if (is_object($screen) && $screen->post_type === 'lmb_payment') {
        wp_enqueue_script('lmb-payment-verifier', LMB_CORE_URL.'assets/js/payment-verifier.js', ['jquery'], LMB_CORE_VERSION, true);
        wp_localize_script('lmb-payment-verifier', 'lmbPaymentVerifier', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_payment_verifier'),
            'strings' => [ /* ... */ ]
        ]);
    }
});