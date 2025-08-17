<?php
if (!defined('ABSPATH')) exit;

class LMB_Form_Handler {
    public static function init() {
        add_action('admin_post_nopriv_lmb_submit_ad', [__CLASS__, 'submit_ad']);
        add_action('admin_post_lmb_submit_ad', [__CLASS__, 'submit_ad']);
    }

    /**
     * User submits a Legal Ad:
     * - store full HTML in 'full_text' (NO stripping)
     * - set status 'draft' initially (user can publish later from dashboard widget)
     */
    public static function submit_ad() {
        if (!is_user_logged_in()) wp_die('Auth required.');

        check_admin_referer('lmb_submit_ad');

        $user_id   = get_current_user_id();
        $title     = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        $ad_type   = isset($_POST['ad_type']) ? sanitize_text_field(wp_unslash($_POST['ad_type'])) : '';
        $full_html = isset($_POST['full_text']) ? wp_unslash($_POST['full_text']) : '';

        // Create post in draft
        $post_id = wp_insert_post([
            'post_type'   => 'lmb_legal_ad',
            'post_title'  => $title ?: ('Ad by user '.$user_id),
            'post_status' => 'draft',
            'meta_input'  => [
                'ad_owner'   => $user_id,
                'ad_type'    => $ad_type,
                // store raw HTML — do not strip; sanitize only when rendering
                'full_text'  => $full_html,
            ]
        ], true);

        if (is_wp_error($post_id)) wp_die($post_id->get_error_message());

        wp_safe_redirect(add_query_arg(['ad_submitted' => 1, 'ad_id' => $post_id], wp_get_referer() ?: home_url('/dashboard')));
        exit;
    }
}
