<?php
/**
 * Plugin Name: LMB Core
 * Description: Legal ads + newspapers + packages, points, invoices, PDF, bank proof workflow, Elementor widgets.
 * Version: 0.3.0
 * Author: yassine rakibi
 * Text Domain: lmb-core
 */

if (!defined('ABSPATH')) { exit; }

// Define constants
if (!defined('LMB_CORE_FILE')) define('LMB_CORE_FILE', __FILE__);
if (!defined('LMB_CORE_DIR'))  define('LMB_CORE_DIR', plugin_dir_path(__FILE__));
if (!defined('LMB_CORE_URL'))  define('LMB_CORE_URL', plugin_dir_url(__FILE__));
if (!defined('LMB_CORE_VERSION')) define('LMB_CORE_VERSION', '0.3.0');

// Includes
require_once LMB_CORE_DIR.'includes/class-lmb-cpt.php';
require_once LMB_CORE_DIR.'includes/class-lmb-points.php';
require_once LMB_CORE_DIR.'includes/class-lmb-pdf-generator.php';
require_once LMB_CORE_DIR.'includes/class-lmb-invoice-handler.php';
require_once LMB_CORE_DIR.'includes/class-lmb-form-handler.php';
require_once LMB_CORE_DIR.'includes/class-lmb-ad-manager.php';
require_once LMB_CORE_DIR.'includes/class-lmb-admin.php';
require_once LMB_CORE_DIR.'includes/class-lmb-payment-verifier.php';
require_once LMB_CORE_DIR.'includes/class-lmb-database-manager.php';

// Elementor widgets
require_once LMB_CORE_DIR.'elementor/class-lmb-elementor-widgets.php';

class LMB_Core {
    public static function init() {
        // Register CPTs & statuses
        add_action('init', ['LMB_CPT', 'register']);
        add_action('init', ['LMB_CPT', 'register_statuses']);

        // Form submission endpoints (front-end legal ad submit)
        add_action('admin_post_nopriv_lmb_submit_legal_ad', ['LMB_Form_Handler', 'handle_legal_ad']);
        add_action('admin_post_lmb_submit_legal_ad',        ['LMB_Form_Handler', 'handle_legal_ad']);

        // Admin actions to accept/deny
        add_action('wp_ajax_lmb_admin_accept_ad', ['LMB_Ad_Manager', 'ajax_accept_ad']);
        add_action('wp_ajax_lmb_admin_deny_ad',   ['LMB_Ad_Manager', 'ajax_deny_ad']);

        // Bank proof upload (users)
        add_action('wp_ajax_lmb_upload_bank_proof',     ['LMB_Payment_Verifier', 'ajax_upload_proof']);
        add_action('wp_ajax_nopriv_lmb_upload_bank_proof', ['LMB_Payment_Verifier', 'ajax_nopriv']);

        // Admin: verify payment proof
        add_action('wp_ajax_lmb_verify_payment', ['LMB_Payment_Verifier', 'ajax_verify_payment']);

        // Admin menu & settings
        add_action('admin_menu', ['LMB_Admin', 'register_menu']);
        add_action('admin_init', ['LMB_Admin', 'register_settings']);

        // Elementor
        add_action('elementor/widgets/register', ['LMB_Elementor_Widgets', 'register_widgets']);

        // Enqueue
        add_action('admin_enqueue_scripts', ['LMB_Admin', 'enqueue']);
        add_action('wp_enqueue_scripts', function(){
            wp_register_style('lmb-core', LMB_CORE_URL.'assets/css/lmb-core.css', [], LMB_CORE_VERSION);
            wp_register_script('lmb-core', LMB_CORE_URL.'assets/js/lmb-core.js', ['jquery'], LMB_CORE_VERSION, true);
            wp_localize_script('lmb-core', 'lmbCore', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('lmb_core_nonce'),
            ]);
        });
    }
}
LMB_Core::init();