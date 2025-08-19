<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core (auth, CPTs, points, invoices, payments, PDFs, directories, dashboards).
 * Version: 2.1.8
 * Author: Yassine Rakibi
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) exit;

define('LMB_CORE_VERSION', '2.1.0');
define('LMB_CORE_FILE', __FILE__);
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL',  plugin_dir_url(__FILE__));

// Autoloader for all classes in the /includes directory
spl_autoload_register(function($class){
    if (strpos($class, 'LMB_') !== 0) {
        return;
    }
    $file = 'class-' . str_replace('_', '-', strtolower($class)) . '.php';
    $path = LMB_CORE_PATH . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
});

// Load Elementor widgets
require_once LMB_CORE_PATH . 'elementor/class-lmb-elementor-widgets.php';

// Register Elementor form action
add_action('elementor_pro/forms/actions/register', function($form_actions_registrar) {
    require_once LMB_CORE_PATH . 'includes/elementor-action-save-ad.php';
    $form_actions_registrar->register(new LMB_Save_Ad_Action());
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
    add_option('lmb_invoice_template_html', '<h1>Invoice {{invoice_number}}</h1><p>Date: {{invoice_date}}</p><hr><h3>Client Details</h3><p>Name: {{user_name}}<br>Email: {{user_email}}</p><hr><h3>Item Details</h3><p><strong>Package:</strong> {{package_name}}<br><strong>Price:</strong> {{package_price}} MAD</p><p><strong>Payment Reference:</strong> {{payment_reference}}</p><hr><h3>Payment Instructions</h3><p>Please make a bank transfer to:<br><strong>Bank:</strong> {{our_bank_name}}<br><strong>IBAN/RIB:</strong> {{our_iban}}</p>');
});

// Deactivation Hook
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('lmb_daily_maintenance');
    flush_rewrite_rules();
});

// Initialize all plugin components on plugins_loaded hook
add_action('plugins_loaded', function(){
    LMB_CPT::init();
    LMB_Form_Handler::init();
    LMB_Ad_Manager::init();
    LMB_Payment_Verifier::init();
    LMB_Admin::init();
    LMB_User_Dashboard::init();
    LMB_Database_Manager::init();
    LMB_Error_Handler::init();
    LMB_Invoice_Handler::init(); // Initialize the invoice handler for AJAX
    new LMB_User();
});

// Enqueue Frontend Scripts & Styles
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('lmb-core', LMB_CORE_URL.'assets/css/lmb-core.css', [], LMB_CORE_VERSION);
    wp_enqueue_script('lmb-core', LMB_CORE_URL.'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);

    // Pass data to our script
    wp_localize_script('lmb-core', 'lmbAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lmb_frontend_ajax_nonce'),
    ]);
    
    // Load Chart.js only when the chart shortcode is present
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'lmb_user_charts')) {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);
    }
});

// Enqueue Admin Scripts & Styles
add_action('admin_enqueue_scripts', function($hook) {
    wp_enqueue_style('lmb-admin-styles', LMB_CORE_URL.'assets/css/admin.css', [], LMB_CORE_VERSION);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');

    wp_enqueue_script('lmb-admin-scripts', LMB_CORE_URL.'assets/js/admin.js', ['jquery'], LMB_CORE_VERSION, true);
    wp_localize_script('lmb-admin-scripts', 'lmbAdmin', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lmb_admin_ajax_nonce'),
    ]);
});