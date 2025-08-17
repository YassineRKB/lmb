<?php
if (!defined('ABSPATH')) exit;

class LMB_Access_Control {
    public static function init() {
        add_action('template_redirect', [__CLASS__, 'protect']);
    }
    public static function protect() {
        $slugs = array_filter(array_map('trim', explode("\n", (string) get_option('lmb_protected_slugs', "/dashboard\n/administration"))));
        if (!$slugs) return;
        $req = wp_parse_url(home_url(add_query_arg([],'')), PHP_URL_PATH);
        $cur = wp_parse_url(add_query_arg([],''), PHP_URL_PATH);
        foreach ($slugs as $slug) {
            if (strpos($cur, $slug) === 0) {
                if ($slug === '/administration' && !current_user_can('edit_others_posts')) {
                    auth_redirect();
                } elseif ($slug === '/dashboard' && !is_user_logged_in()) {
                    auth_redirect();
                }
            }
        }
    }
}
