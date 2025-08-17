<?php
if (!defined('ABSPATH')) exit;

class LMB_Payment_Verifier {
    public static function init() {
        // user proof upload
        add_action('admin_post_lmb_upload_bank_proof', [__CLASS__, 'upload_proof']);
        // admin validate
        add_action('admin_post_lmb_admin_validate_payment', [__CLASS__, 'admin_validate_payment']);
        // columns
        add_filter('manage_lmb_payment_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_lmb_payment_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
    }

    public static function cols($cols) {
        $cols['payer']   = __('Payer', 'lmb-core');
        $cols['package'] = __('Package', 'lmb-core');
        $cols['status']  = __('Status', 'lmb-core');
        return $cols;
    }
    public static function col_content($col, $post_id) {
        if ($col==='payer')   echo esc_html(get_post_meta($post_id, 'user_id', true) ?: '-');
        if ($col==='package') echo esc_html(get_post_meta($post_id, 'package_id', true) ?: '-');
        if ($col==='status')  echo esc_html(get_post_meta($post_id, 'payment_status', true) ?: 'pending');
    }

    /** User uploads bank transfer proof (creates lmb_payment post). */
    public static function upload_proof() {
        if (!is_user_logged_in()) wp_die('Auth required.');
        check_admin_referer('lmb_upload_bank_proof');

        $user_id   = get_current_user_id();
        $package_id= (int) ($_POST['package_id'] ?? 0);
        $notes     = sanitize_text_field(wp_unslash($_POST['notes'] ?? ''));

        if (empty($_FILES['proof_file']['name'])) wp_die('No file.');

        $att_id = media_handle_upload('proof_file', 0);
        if (is_wp_error($att_id)) wp_die($att_id->get_error_message());

        $pay_id = wp_insert_post([
            'post_type'   => 'lmb_payment',
            'post_title'  => 'Payment proof by user '.$user_id,
            'post_status' => 'publish',
            'meta_input'  => [
                'user_id'        => $user_id,
                'package_id'     => $package_id,
                'proof_attach_id'=> $att_id,
                'payment_status' => 'pending',
                'notes'          => $notes,
            ]
        ], true);

        if (is_wp_error($pay_id)) wp_die($pay_id->get_error_message());

        LMB_Ad_Manager::log_activity('Payment proof uploaded by user '.$user_id.' for package '.$package_id);

        wp_safe_redirect(add_query_arg(['proof_uploaded'=>1], home_url('/dashboard')));
        exit;
    }

    /** Admin validates a payment → assign package values to user (points + ad cost). */
    public static function admin_validate_payment() {
        if (!current_user_can('edit_others_posts')) wp_die('No permission.');
        check_admin_referer('lmb_admin_validate_payment');

        $pay_id = (int) ($_POST['payment_id'] ?? 0);
        $status = sanitize_text_field($_POST['new_status'] ?? 'approved');
        $points = (int) ($_POST['points'] ?? 0);
        $cost   = (int) ($_POST['cost_per_ad'] ?? 0);

        $user_id = (int) get_post_meta($pay_id, 'user_id', true);

        update_post_meta($pay_id, 'payment_status', $status);
        if ($status === 'approved' && $user_id) {
            if ($points > 0) LMB_Points::add($user_id, $points);
            if ($cost >= 0)  LMB_Points::set_cost_per_ad($user_id, $cost);
            LMB_Ad_Manager::log_activity('Payment #'.$pay_id.' approved. User '.$user_id.' +'.$points.' points, cost/ad '.$cost.'.');
        } else {
            LMB_Ad_Manager::log_activity('Payment #'.$pay_id.' updated status = '.$status.'.');
        }

        wp_safe_redirect(add_query_arg(['payment_updated'=>1], wp_get_referer()));
        exit;
    }
}
