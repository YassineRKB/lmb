<?php
/**
 * Plugin Name: LMB Core
 * Description: Elementor-first legal ads platform core.
 * Version: 5.5.0
 * Author: Yassine Rakibi
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) exit;

define('LMB_CORE_VERSION', '5.5.0'); // Updated version
define('LMB_CORE_FILE', __FILE__);
define('LMB_CORE_PATH', plugin_dir_path(__FILE__));
define('LMB_CORE_URL', plugin_dir_url(__FILE__));

// The autoloader is still useful for widgets and other non-critical classes.
spl_autoload_register(function($class) {
    if (strpos($class, 'LMB_') !== 0) return;
    $dirs = ['includes/', 'elementor/widgets/'];
    $file = 'class-' . str_replace('_', '-', strtolower($class)) . '.php';
    foreach ($dirs as $dir) {
        $path = LMB_CORE_PATH . $dir . $file;
        if (file_exists($path)) { require_once $path; return; }
    }
});

// Load plugin textdomain correctly
function lmb_load_textdomain() {
    load_plugin_textdomain('lmb-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'lmb_load_textdomain');


// Main Initialization Function to control load order
function lmb_core_init() {
    // Manually load core classes in the correct dependency order
    require_once LMB_CORE_PATH . 'includes/class-lmb-error-handler.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-database-manager.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-access-control.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-cpt.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-points.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-pdf-generator.php'; 
    require_once LMB_CORE_PATH . 'includes/class-lmb-invoice-handler.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-notification-manager.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-ad-manager.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-form-handler.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-payment-verifier.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-admin.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-user-dashboard.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-ajax-handlers.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-user.php';
    require_once LMB_CORE_PATH . 'elementor/class-lmb-elementor-widgets.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-maintenance-utilities.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-data-manager.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-ad-type-manager.php';

    // Now, initialize the classes
    LMB_Error_Handler::init();
    LMB_Access_Control::init();
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
    LMB_Maintenance_Utilities::init();
    LMB_Data_Manager::init();
    LMB_Ad_Type_Manager::init();
}
add_action('plugins_loaded', 'lmb_core_init');


// Activation Hook
register_activation_hook(__FILE__, function () {
    // We need to include files here too for activation to work
    require_once LMB_CORE_PATH . 'includes/class-lmb-cpt.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-user.php';
    require_once LMB_CORE_PATH . 'includes/class-lmb-database-manager.php';
    
    LMB_CPT::init();
    LMB_User::create_custom_roles();
    LMB_Database_Manager::create_custom_tables();
    flush_rewrite_rules();
    add_option('lmb_admin_feed_refresh_interval', 30);
});


/**
 * UNIFIED ASSET REGISTRATION (Corrected for Elementor loading)
 */
function lmb_register_all_assets() {
    // --- SCRIPTS ---
    // Register lmb-core first, dependent only on jquery.
    wp_register_script('lmb-core', LMB_CORE_URL . 'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);

    $scripts = [
        'lmb-admin-editor'              => 'assets/js/lmb-admin-editor.js',
        'lmb-notifications'             => 'assets/js/lmb-notifications.js',
        'lmb-balance-manipulation'      => 'assets/js/lmb-balance-manipulation.js',
        'lmb-packages-editor'           => 'assets/js/lmb-packages-editor.js',
        'lmb-upload-newspaper'          => 'assets/js/lmb-upload-newspaper.js',
        'lmb-upload-bank-proof'         => 'assets/js/lmb-upload-bank-proof.js',
        'lmb-legal-ads-management-v2'   => 'assets/js/lmb-legal-ads-management-v2.js',
        'lmb-my-legal-ads-v2'           => 'assets/js/lmb-my-legal-ads-v2.js',
        'lmb-feed-v2'                   => 'assets/js/lmb-feed-v2.js',
        'lmb-auth-v2'                   => 'assets/js/lmb-auth-v2.js',
        'lmb-inactive-clients-v2'       => 'assets/js/lmb-inactive-clients-v2.js',
        'lmb-active-clients-v2'         => 'assets/js/lmb-active-clients-v2.js',
        'lmb-profile-v2'                => 'assets/js/lmb-profile-v2.js',
        'lmb-ads-directory-v2'          => 'assets/js/lmb-ads-directory-v2.js',
        'lmb-newspaper-directory-v2'    => 'assets/js/lmb-newspaper-directory-v2.js',
        'lmb-payments-management'       => 'assets/js/lmb-payments-management.js',
        'lmb-generate-newspaper'        => 'assets/js/lmb-generate-newspaper.js',
        'lmb-admin-subscribe-user'      => 'assets/js/lmb-admin-subscribe-user.js',
    ];

    foreach ($scripts as $handle => $path) {
        // Dependencies for all other scripts are lmb-core and elementor-frontend (for widgets)
        $dependency = ['lmb-core', 'elementor-frontend']; 
        
        // Check if the script exists in the repository before registering.
        $script_path = LMB_CORE_PATH . $path;
        if (file_exists($script_path)) {
            wp_register_script($handle, LMB_CORE_URL . $path, $dependency, LMB_CORE_VERSION, true);
        }
    }
    
    // --- STYLES ---
    $styles = [
        'lmb-core'                      => 'assets/css/lmb-core.css',
        'lmb-admin-widgets'             => 'assets/css/lmb-admin-widgets.css',
        'lmb-user-widgets'              => 'assets/css/lmb-user-widgets.css',
        'lmb-notifications'             => 'assets/css/lmb-notifications.css',
        'lmb-admin-widgets-v2'          => 'assets/css/lmb-admin-widgets-v2.css',
        'lmb-user-widgets-v2'           => 'assets/css/lmb-user-widgets-v2.css',
        'lmb-auth-v2'                   => 'assets/css/lmb-auth-v2.css',
        'lmb-profile-v2'                => 'assets/css/lmb-profile-v2.css',
        'lmb-legal-ads-management-v2'   => 'assets/css/lmb-legal-ads-management-v2.css',
        'lmb-my-legal-ads-v2'           => 'assets/css/lmb-my-legal-ads-v2.css',
        'lmb-ads-directory-v2'          => 'assets/css/lmb-ads-directory-v2.css',
        'lmb-newspaper-directory-v2'    => 'assets/css/lmb-newspaper-directory-v2.css',
        'lmb-active-clients-v2'         => 'assets/css/lmb-active-clients-v2.css',
        'lmb-inactive-clients-v2'       => 'assets/css/lmb-inactive-clients-v2.css',
        'lmb-payments-management'       => 'assets/css/lmb-payments-management.css',
        'lmb-packages-editor'           => 'assets/css/lmb-packages-editor.css',
        'lmb-final-newspapers-list'     => 'assets/css/lmb-final-newspapers-list.css',
        'lmb-generate-newspaper'        => 'assets/css/lmb-generate-newspaper.css'
    ];

    foreach ($styles as $handle => $path) {
        $style_path = LMB_CORE_PATH . $path;
        if (file_exists($style_path)) {
            wp_register_style($handle, LMB_CORE_URL . $path, [], LMB_CORE_VERSION);
        }
    }

    // Localize Script only after core is registered
    wp_localize_script('lmb-core', 'lmb_ajax_params', [
        'ajaxurl'    => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('lmb_nonce'),
        'dm_nonce'   => wp_create_nonce('lmb_dm_nonce'), 
        'logout_url' => wp_logout_url(home_url('/')), 
    ]);

    // Enqueue core assets that are always needed
    wp_enqueue_script('lmb-core');
    wp_enqueue_style('lmb-core');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.7.0', true);


    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'lmb_legal_ad' && $screen->base === 'post') {
            wp_enqueue_script('lmb-admin-editor');
        }
        // Also ensure admin styles/scripts needed in wp-admin are available
        wp_enqueue_style('lmb-admin-widgets-v2');
    }
    
    // --- NEW: Conditionally enqueue script for the user editor page ---
    // This check is safe for both frontend and admin context.
    if (is_page('user-editor')) {
        wp_enqueue_script('lmb-admin-subscribe-user');
    }
}
add_action('wp_enqueue_scripts', 'lmb_register_all_assets');
add_action('admin_enqueue_scripts', 'lmb_register_all_assets');


/**
 * Adds a rewrite rule to handle profile URLs like /profile/{userid}.
 */
function lmb_add_profile_rewrite_rule() {
    add_rewrite_rule(
        '^profile/(\d+)/?$',
        'index.php?pagename=profile&userid=$matches[1]',
        'top'
    );
    // Correct rewrite for user editor
    add_rewrite_rule(
        '^user-editor/(\d+)/?$',
        'index.php?pagename=user-editor&user_id=$matches[1]',
        'top'
    );
}
add_action('init', 'lmb_add_profile_rewrite_rule');


/**
 * Registers 'userid' and 'user_id' as a public query variable so WordPress recognizes it.
 */
function lmb_register_query_vars($vars) {
    $vars[] = 'userid';
    $vars[] = 'user_id'; // Add user_id as well
    return $vars;
}
add_filter('query_vars', 'lmb_register_query_vars');


/**
 * PDF Preview Handler (Simulates PDF display by showing raw HTML)
 * This function intercepts the temporary PDF URL generated by the AJAX call.
 */
function lmb_handle_pdf_preview() {
    if (isset($_GET['lmb-pdf-preview']) && $_GET['lmb-pdf-preview'] == '1' && isset($_GET['post_id'])) {
        
        $post_id = intval($_GET['post_id']);
        $nonce = sanitize_text_field($_GET['nonce'] ?? '');

        // Security check
        if (!current_user_can('manage_options') || !wp_verify_nonce($nonce, 'lmb_pdf_preview_' . $post_id)) {
            wp_die('Accès refusé ou lien expiré.', 'Erreur de Prévisualisation', ['response' => 403]);
        }
        
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'lmb_newspaper' || $post->post_status !== 'draft') {
            wp_die('Journal de prévisualisation non valide ou déjà supprimé.', 'Erreur de Contenu', ['response' => 404]);
        }

        $html_content = get_post_meta($post_id, 'lmb_temp_newspaper_html', true);

        if (empty($html_content)) {
            wp_die('Contenu du journal non trouvé.', 'Erreur de Contenu', ['response' => 404]);
        }
        
        // Output the HTML directly to simulate the PDF content view in the new tab
        // This is sent with Content-Type: text/html, but it contains the A4 CSS template
        header('Content-Type: text/html; charset=utf-8');
        echo $html_content;
        
        exit;
    }
}
add_action('init', 'lmb_handle_pdf_preview');
// Hide WordPress Admin Bar
add_filter('show_admin_bar', '__return_false');
// Shortcode to display current logged-in user's display name
function lmb_show_current_user_name_shortcode() {
    if ( is_user_logged_in() ) {
        $current_user = wp_get_current_user();
        if ( $current_user->exists() ) {
            return esc_html( $current_user->display_name );
        }
    }
    return ''; // Return nothing if the user is not logged in.
}
add_shortcode('show_username', 'lmb_show_current_user_name_shortcode');
// --- NEW FUNCTION TO LOAD ASSETS ON OUR ADMIN PAGE ---
function lmb_load_data_manager_assets($hook) {
    // 'lmb-core_page_lmb-data-manager' is the specific hook for our new page
    if ($hook != 'lmb-core_page_lmb-data-manager') {
        return;
    }
    wp_enqueue_script('lmb-data-manager', LMB_CORE_URL . 'assets/js/lmb-data-manager.js', ['jquery'], LMB_CORE_VERSION, true);
    wp_enqueue_style('lmb-data-manager', LMB_CORE_URL . 'assets/css/lmb-data-manager.css', [], LMB_CORE_VERSION);
    
    // We need to localize the script here as well to pass the nonce
    wp_localize_script('lmb-data-manager', 'lmb_ajax_params', [
        'ajaxurl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('lmb_dm_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'lmb_load_data_manager_assets');
function lmb_load_ad_type_manager_assets($hook) {
    if ($hook != 'lmb-core_page_lmb-ad-type-manager') {
        return;
    }
    wp_enqueue_script('lmb-ad-type-manager', LMB_CORE_URL . 'assets/js/lmb-ad-type-manager.js', ['jquery'], LMB_CORE_VERSION, true);
    wp_enqueue_style('lmb-ad-type-manager', LMB_CORE_URL . 'assets/css/lmb-ad-type-manager.css', [], LMB_CORE_VERSION);

    wp_localize_script('lmb-ad-type-manager', 'lmb_ajax_params', [
        'ajaxurl'  => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('lmb_nonce'),
    ]);
}
add_action('admin_enqueue_scripts', 'lmb_load_ad_type_manager_assets');