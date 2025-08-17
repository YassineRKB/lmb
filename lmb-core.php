<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core (auth, CPTs, points, invoices, payments, PDFs, directories, dashboards).
 * Version: 1.1.0
 * Author: LMB
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) exit;

define('LMB_CORE_VERSION', '1.1.0');
define('LMB_CORE_FILE', __FILE__);
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL',  plugin_dir_url(__FILE__));

/** Autoloader (simple) */
spl_autoload_register(function($class){
    if (strpos($class, 'LMB_') !== 0) return;
    $map = [
        'includes' => [
            'class-lmb-access-control.php',
            'class-lmb-acf.php',
            'class-lmb-cpt.php',
            'class-lmb-points.php',
            'class-lmb-pdf-generator.php',
            'class-lmb-invoice-handler.php',
            'class-lmb-ad-manager.php',
            'class-lmb-form-handler.php',
            'class-lmb-payment-verifier.php',
            'class-lmb-admin.php',
            'class-lmb-user-dashboard.php',
            'class-lmb-notifications-manager.php',
            'class-lmb-database-manager.php',
            'class-lmb-maintenance_utilities.php',
            'class-lmb-error-handler.php',
            'class-lmb-user.php',
        ],
    ];
    foreach ($map as $dir => $files) {
        foreach ($files as $file) {
            if (stripos($file, strtolower($class)) !== false && file_exists(LMB_CORE_PATH.$dir.'/'.$file)) {
                require_once LMB_CORE_PATH.$dir.'/'.$file;
                return;
            }
        }
    }
});

/** Elementor widgets */
require_once LMB_CORE_PATH.'elementor/class-lmb-elementor-widgets.php';
require_once LMB_CORE_PATH . 'includes/class-lmb-access-control.php';

/** Shortcodes (fallbacks) */
add_shortcode('lmb_ads_directory', ['LMB_Elementor_Widgets_Helper', 'ads_directory_shortcode']);
add_shortcode('lmb_newspaper_directory', ['LMB_Elementor_Widgets_Helper', 'newspaper_directory_shortcode']);

/** Activation */
register_activation_hook(__FILE__, function () {
    // Default settings
    add_option('lmb_invoice_template_html', '<h1>Invoice {{invoice_number}}</h1><p>User: {{user_name}} (ID {{user_id}})</p><p>Package: {{package_name}}</p><p>Price: {{package_price}}</p><p>Bank: {{our_bank_name}}</p><p>IBAN/RIB: {{our_iban}}</p><p>Reference: {{payment_reference}}</p><p>Date: {{invoice_date}}</p>');
    add_option('lmb_protected_slugs', "/dashboard\n/administration");
    add_option('lmb_staff_roles', "administrator,editor");

    // Register CPTs, statuses, rewrites
    LMB_CPT::init();
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
});

/** Assets */
add_action('wp_enqueue_scripts', function(){
    wp_enqueue_style('lmb-core', LMB_CORE_URL.'assets/css/lmb-core.css', [], LMB_CORE_VERSION);
    wp_enqueue_script('lmb-core', LMB_CORE_URL.'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);
});

/** Admin Assets */
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'lmb') !== false || get_post_type() === 'lmb_legal_ad') {
        wp_enqueue_style('lmb-admin', LMB_CORE_URL.'assets/css/admin.css', [], LMB_CORE_VERSION);
        wp_enqueue_script('lmb-admin', LMB_CORE_URL.'assets/js/admin.js', ['jquery'], LMB_CORE_VERSION, true);
        
        wp_localize_script('lmb-admin', 'lmbAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_admin_nonce'),
            'strings' => [
                'confirm_status_change' => __('Are you sure you want to change the status?', 'lmb-core'),
                'status_changed' => __('Status changed successfully.', 'lmb-core'),
                'error_occurred' => __('An error occurred.', 'lmb-core'),
                'confirm_bulk_action' => __('Are you sure you want to perform this bulk action?', 'lmb-core')
            ]
        ]);
    }
    
    // Payment verifier assets
    if ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'lmb_payment') {
        wp_enqueue_script('lmb-payment-verifier', LMB_CORE_URL.'assets/js/payment-verifier.js', ['jquery'], LMB_CORE_VERSION, true);
        wp_localize_script('lmb-payment-verifier', 'lmbPaymentVerifier', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_payment_verifier'),
            'strings' => [
                'confirm_verify' => __('Are you sure you want to verify this payment?', 'lmb-core'),
                'confirm_reject' => __('Are you sure you want to reject this payment?', 'lmb-core'),
                'verified' => __('Payment verified successfully.', 'lmb-core'),
                'rejected' => __('Payment rejected.', 'lmb-core'),
                'error' => __('An error occurred.', 'lmb-core')
            ]
        ]);
    }
});
