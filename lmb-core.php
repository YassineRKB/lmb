<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core.
 * Version: 5.3.0
 * Author: Yassine Rakibi
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) exit;

define('LMB_CORE_VERSION', '5.3.0');
define('LMB_CORE_FILE', __FILE__);
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL', plugin_dir_url(__FILE__));

// Autoloader
spl_autoload_register(function($class) {
    if (strpos($class, 'LMB_') !== 0) return;
    $dirs = ['includes/', 'elementor/widgets/'];
    $file = 'class-' . str_replace('_', '-', strtolower($class)) . '.php';
    foreach ($dirs as $dir) {
        $path = LMB_CORE_PATH . $dir . $file;
        if (file_exists($path)) { require_once $path; return; }
    }
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

// Activation Hook
register_activation_hook(__FILE__, function () {
    LMB_CPT::init();
    LMB_User::create_custom_roles();
    LMB_Database_Manager::create_custom_tables();
    flush_rewrite_rules();
    add_option('lmb_admin_feed_refresh_interval', 30);
});

/**
 * --- CORRECTED AND UNIFIED ASSET REGISTRATION ---
 * This single function now handles registering all scripts and styles for the plugin.
 * Elementor's dependency system will then enqueue them only when a widget is used.
 */
function lmb_register_all_assets() {
    // --- SCRIPTS ---
    $scripts = [
        'lmb-core'                    => 'assets/js/lmb-core.js',
        'lmb-admin-editor'            => 'assets/js/lmb-admin-editor.js',
        'lmb-admin-actions'           => 'assets/js/lmb-admin-actions.js',
        'lmb-notifications'           => 'assets/js/lmb-notifications.js',
        'lmb-balance-manipulation'    => 'assets/js/lmb-balance-manipulation.js',
        'lmb-packages-editor'         => 'assets/js/lmb-packages-editor.js',
        'lmb-upload-newspaper'        => 'assets/js/lmb-upload-newspaper.js',
        'lmb-upload-bank-proof'       => 'assets/js/lmb-upload-bank-proof.js',
        'lmb-admin-lists'             => 'assets/js/lmb-admin-lists.js',
        'lmb-legal-ads-receipts'      => 'assets/js/lmb-legal-ads-receipts.js',
        'lmb-invoices'                => 'assets/js/lmb-invoices.js',
        'lmb-user-ads-list'           => 'assets/js/lmb-user-ads-list.js',
        'lmb-upload-accuse'           => 'assets/js/lmb-upload-accuse.js',
        'lmb-legal-ads-management-v2' => 'assets/js/lmb-legal-ads-management-v2.js',
        'lmb-my-legal-ads-v2'         => 'assets/js/lmb-my-legal-ads-v2.js',
        'lmb-feed-v2'                 => 'assets/js/lmb-feed-v2.js',
        'lmb-auth-v2'                 => 'assets/js/lmb-auth-v2.js',
        'lmb-inactive-clients-v2'     => 'assets/js/lmb-inactive-clients-v2.js',
    ];

    foreach ($scripts as $handle => $path) {
        // Register all scripts. Use 'lmb-core' as a dependency for widget scripts.
        $dependency = ($handle === 'lmb-core') ? ['jquery'] : ['lmb-core'];
        wp_register_script($handle, LMB_CORE_URL . $path, $dependency, LMB_CORE_VERSION, true);
    }
    
    // --- STYLES ---
    $styles = [
        'lmb-core'               => 'assets/css/lmb-core.css',
        'lmb-admin-widgets'      => 'assets/css/lmb-admin-widgets.css',
        'lmb-user-widgets'       => 'assets/css/lmb-user-widgets.css',
        'lmb-notifications'      => 'assets/css/lmb-notifications.css',
        'lmb-admin-widgets-v2'   => 'assets/css/lmb-admin-widgets-v2.css',
        'lmb-user-widgets-v2'    => 'assets/css/lmb-user-widgets-v2.css',
        'lmb-auth-v2'            => 'assets/css/lmb-auth-v2.css',
    ];

    foreach ($styles as $handle => $path) {
        wp_register_style($handle, LMB_CORE_URL . $path, [], LMB_CORE_VERSION);
    }

    // --- CENTRALIZE AJAX PARAMS (CRITICAL) ---
    // This makes 'lmb_ajax_params' available to 'lmb-core.js' and any other script that depends on it.
    wp_localize_script('lmb-core', 'lmb_ajax_params', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('lmb_nonce'),
    ]);

    // Localize other specific params
    wp_localize_script('lmb-admin-actions', 'lmb_admin_settings', [
        'refresh_interval' => (int) get_option('lmb_admin_feed_refresh_interval', 30) * 1000,
    ]);

    // --- ENQUEUE CORE ASSETS ---
    // Enqueue assets that should be loaded on every page (frontend and backend).
    wp_enqueue_script('lmb-core');
    wp_enqueue_style('lmb-core');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);


    // Conditionally enqueue assets for specific admin pages if needed
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'lmb_legal_ad' && $screen->base === 'post') {
            wp_enqueue_script('lmb-admin-editor');
        }
    }
}
// Use the standard WordPress hooks to register all assets. Elementor will now correctly find and enqueue them when its widgets are used.
add_action('wp_enqueue_scripts', 'lmb_register_all_assets');
add_action('admin_enqueue_scripts', 'lmb_register_all_assets');

// We no longer need the separate lmb_register_widget_assets() function or its hooks.