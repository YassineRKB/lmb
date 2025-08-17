<?php
if (!defined('ABSPATH')) exit;

class LMB_Access_Control {
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'protect_routes']);
    }

    public static function protect_routes() {
        // Protect the /dashboard page for logged-out users
        if (is_page('dashboard') && !is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }

        // Protect the /administration page for non-admins
        if (is_page('administration') && !current_user_can('manage_options')) {
            wp_redirect(home_url());
            exit;
        }
    }
}