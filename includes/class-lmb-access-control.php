<?php
if (!defined('ABSPATH')) exit;

class LMB_Access_Control {
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'protect_routes']);
    }

    public static function protect_routes() {
        // Get protected pages configuration
        $protected_pages = get_option('lmb_protected_pages', []);
        
        // Check if current page is protected
        $current_page_id = get_queried_object_id();
        if (isset($protected_pages[$current_page_id])) {
            $protection_level = $protected_pages[$current_page_id];
            
            switch ($protection_level) {
                case 'logged_in':
                    if (!is_user_logged_in()) {
                        wp_redirect(wp_login_url(get_permalink()));
                        exit;
                    }
                    break;
                    
                case 'admin_only':
                    if (!current_user_can('manage_options')) {
                        if (is_user_logged_in()) {
                            wp_redirect(home_url());
                        } else {
                            wp_redirect(wp_login_url(get_permalink()));
                        }
                        exit;
                    }
                    break;
            }
        }

        // Legacy protection for specific pages (keep for backward compatibility)
        if (is_page('dashboard') && !is_user_logged_in()) {
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
        

        if (is_page('administration') && !current_user_can('manage_options')) {
            if (is_user_logged_in()) {
                wp_redirect(home_url('dashboard'));
            } else {
                wp_redirect(wp_login_url(get_permalink()));
            }
            exit;
        }
    }
}
