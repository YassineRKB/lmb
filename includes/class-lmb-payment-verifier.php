<?php
if (!defined('ABSPATH')) exit;

class LMB_Payment_Verifier {
    public static function init() {
        // Admin AJAX actions for verifying/rejecting payments
        add_action('wp_ajax_lmb_verify_payment', [__CLASS__, 'ajax_verify_payment']);
        add_action('wp_ajax_lmb_reject_payment', [__CLASS__, 'ajax_reject_payment']);
        
        // Admin list customization
        add_filter('manage_lmb_payment_posts_columns', [__CLASS__, 'set_custom_edit_columns']);
        add_action('manage_lmb_payment_posts_custom_column', [__CLASS__, 'custom_column_content'], 10, 2);
    }

    public static function set_custom_edit_columns($columns) {
        unset($columns['date']);
        $columns['payer'] = __('Client', 'lmb-core');
        $columns['package'] = __('Package', 'lmb-core');
        $columns['proof'] = __('Proof', 'lmb-core');
        $columns['status'] = __('Status', 'lmb-core');
        $columns['actions'] = __('Actions', 'lmb-core');
        $columns['date'] = __('Date', 'lmb-core');
        return $columns;
    }

    public static function custom_column_content($col, $post_id) {
        switch ($col) {
            case 'payer':
                $user_id = get_post_meta($post_id, 'user_id', true);
                $user = get_userdata($user_id);
                echo $user ? esc_html($user->display_name) : 'N/A';
                break;
            case 'package':
                $package_id = get_post_meta($post_id, 'package_id', true);
                echo $package_id ? esc_html(get_the_title($package_id)) : 'N/A';
                break;
            case 'proof':
                $attachment_id = get_post_meta($post_id, 'proof_attachment_id', true);
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    echo '<a href="'.esc_url($url).'" target="_blank" class="button button-small">View Proof</a>';
                } else {
                    echo '—';
                }
                break;
            case 'status':
                $status = get_post_meta($post_id, 'payment_status', true);
                $badge_class = 'lmb-status-' . esc_attr($status);
                echo '<span class="lmb-status-badge ' . $badge_class . '">' . esc_html(ucfirst($status)) . '</span>';
                break;
            case 'actions':
                $status = get_post_meta($post_id, 'payment_status', true);
                if ($status === 'pending') {
                    echo '<button class="button button-primary button-small lmb-verify-payment" data-payment-id="' . $post_id . '">Verify</button>';
                    echo '<button class="button button-small lmb-reject-payment" data-payment-id="' . $post_id . '">Reject</button>';
                } else {
                    echo '—';
                }
                break;
        }
    }

    public static function ajax_verify_payment() {
        check_ajax_referer('lmb_payment_verifier', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        
        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
        if (!$payment_id) {
            wp_send_json_error(['message' => 'Invalid Payment ID.']);
        }

        $user_id = (int) get_post_meta($payment_id, 'user_id', true);
        $package_id = (int) get_post_meta($payment_id, 'package_id', true);

        if (!$user_id || !$package_id) {
            wp_send_json_error(['message' => 'User or Package ID missing from payment record.']);
        }

        $points_to_add = (int) get_post_meta($package_id, 'points', true);
        $cost_per_ad = (int) get_post_meta($package_id, 'cost_per_ad', true);

        // Add points and set new cost per ad
        LMB_Points::add($user_id, $points_to_add, 'Package purchase: ' . get_the_title($package_id));
        LMB_Points::set_cost_per_ad($user_id, $cost_per_ad);
        
        // Update payment status
        update_post_meta($payment_id, 'payment_status', 'approved');

        LMB_Ad_Manager::log_activity(sprintf(
            'Payment #%d approved by %s. Assigned %d points and %d cost/ad to user #%d.',
            $payment_id, wp_get_current_user()->display_name, $points_to_add, $cost_per_ad, $user_id
        ));
        
        // Notify user
        LMB_Notification_Manager::notify_payment_verified($user_id, $package_id, $points_to_add);
        
        wp_send_json_success([
            'message' => 'Payment verified successfully!',
            'points_added' => $points_to_add
        ]);
    }
    
    public static function ajax_reject_payment() {
        check_ajax_referer('lmb_payment_verifier', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        
        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
        if (!$payment_id) {
            wp_send_json_error(['message' => 'Invalid Payment ID.']);
        }
        
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'No reason provided.';
        
        update_post_meta($payment_id, 'payment_status', 'rejected');
        update_post_meta($payment_id, 'rejection_reason', $reason);

        LMB_Ad_Manager::log_activity(sprintf(
            'Payment #%d rejected by %s. Reason: %s',
            $payment_id, wp_get_current_user()->display_name, $reason
        ));
        
        // Optionally, notify the user of rejection
        // LMB_Notification_Manager::notify_payment_rejected($user_id, $reason);
        
        wp_send_json_success(['message' => 'Payment rejected.']);
    }
}