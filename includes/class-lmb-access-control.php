<?php
if (!defined('ABSPATH')) { exit; }

class LMB_Access_Control {
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'protect']);
    }

    public static function protect() {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) return;

        $raw = trim((string) get_option('lmb_protected_slugs', "/dashboard\n/administration"));
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
        if (empty($lines)) return;

        $current_path = parse_url(add_query_arg([]), PHP_URL_PATH);
        if (!$current_path) return;

        foreach ($lines as $slug) {
            if (strpos($current_path, $slug) === 0) {
                if (!is_user_logged_in()) {
                    // Redirect to WPUM login page if exists, else wp-login.php
                    $login_url = site_url('/auth');
                    wp_redirect($login_url);
                    exit;
                }
            }
        }
    }
}
LMB_Access_Control::init();
