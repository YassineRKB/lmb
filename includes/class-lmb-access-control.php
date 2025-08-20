<?php
if (!defined('ABSPATH')) exit;

class LMB_Access_Control {
    public static function init() {
        // --- CHANGE HERE: Use the 'template_redirect' hook ---
        // This hook is the standard and most reliable way to perform redirects before a page loads.
        add_action('template_redirect', [__CLASS__, 'protect_routes']);
    }

    public static function protect_routes() {
        // --- CHANGE HERE: Simplified and corrected the logic ---

        // Protect the /dashboard page for logged-out users
        if (is_page('dashboard') && !is_user_logged_in()) {
            // Redirect them to the login page, and after login, send them back to the dashboard.
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        if (is_page('constitision-sarl') && !is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        if (is_page('constitision-sarl-au') && !is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
        

        // Protect the /administration page for anyone who is not an administrator
        if (is_page('administration') && !current_user_can('manage_options')) {
            // If a user is logged in but not an admin, send them to the homepage.
            // If they are not logged in at all, send them to the login page.
            if (is_user_logged_in()) {
                wp_redirect('dashboard');
            } else {
                wp_redirect(wp_login_url(get_permalink()));
            }
            exit;
        }
    }
}
