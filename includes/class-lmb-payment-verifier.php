<?php
if (!defined('ABSPATH')) exit;

class LMB_Payment_Verifier {
    public static function init() {
        add_action('wp_ajax_lmb_payment_action', [__CLASS__, 'ajax_payment_action']);
        
        add_filter('manage_lmb_payment_posts_columns', [__CLASS__, 'set_custom_columns']);
        add_action('manage_lmb_payment_posts_custom_column', [__CLASS__, 'render_custom_columns'], 10, 2);
    }

    public static function set_custom_columns($columns) {
        unset($columns['title'], $columns['date']);
        $columns['client'] = __('Client', 'lmb-core');
        $columns['package'] = __('Package', 'lmb-core');
        $columns['reference'] = __('Reference', 'lmb-core');
        $columns['proof'] = __('Proof', 'lmb-core');
        $columns['status'] = __('Status', 'lmb-core');
        $columns['actions'] = __('Actions', 'lmb-core');
        $columns['date'] = __('Submitted', 'lmb-core');
        return $columns;
    }

    public static function render_custom_columns($col, $post_id) {
        switch ($col) {
            case 'client':
                $user = get_userdata(get_post_meta($post_id, 'user_id', true));
                echo $user ? '<a href="'.get_edit_user_link($user->ID).'">'.esc_html($user->display_name).'</a>' : 'N/A';
                break;
            case 'package':
                echo esc_html(get_the_title(get_post_meta($post_id, 'package_id', true)));
                break;
            case 'reference':
                echo '<strong>'.esc_html(get_post_meta($post_id, 'payment_reference', true)).'</strong>';
                break;
            case 'proof':
                $url = wp_get_attachment_url(get_post_meta($post_id, 'proof_attachment_id', true));
                if ($url) echo '<a href="'.esc_url($url).'" target="_blank" class="button button-small">View Proof</a>';
                break;
            case 'status':
                $status = get_post_meta($post_id, 'payment_status', true);
                echo '<span class="lmb-status-badge lmb-status-'.esc_attr($status).'">'.esc_html(ucfirst($status)).'</span>';
                break;
            case 'actions':
                if (get_post_meta($post_id, 'payment_status', true) === 'pending') {
                    echo '<button class="button button-primary button-small lmb-payment-action" data-action="approve" data-id="'.$post_id.'">'.__('Approve', 'lmb-core').'</button>';
                    echo '<button class="button button-secondary button-small lmb-payment-action" data-action="reject" data-id="'.$post_id.'">'.__('Reject', 'lmb-core').'</button>';
                }
                break;
        }
    }

    public static function ajax_payment_action() {
        check_ajax_referer('lmb_admin_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);

        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
        $action = isset($_POST['payment_action']) ? sanitize_key($_POST['payment_action']) : '';

        $user_id = (int) get_post_meta($payment_id, 'user_id', true);
        $package_id = (int) get_post_meta($payment_id, 'package_id', true);

        if (!$user_id || !$package_id) wp_send_json_error(['message' => 'Payment record is missing data.']);
        
        if ($action === 'approve') {
            $points = (int) get_post_meta($package_id, 'points', true);
            $cost_per_ad = (int) get_post_meta($package_id, 'cost_per_ad', true);
            
            LMB_Points::add($user_id, $points, 'Purchase of package: ' . get_the_title($package_id));
            LMB_Points::set_cost_per_ad($user_id, $cost_per_ad);
            
            update_post_meta($payment_id, 'payment_status', 'approved');
            LMB_Ad_Manager::log_activity(sprintf('Payment #%d approved by %s.', $payment_id, wp_get_current_user()->display_name));
            LMB_Notification_Manager::notify_payment_verified($user_id, $package_id, $points);
            
            wp_send_json_success(['message' => 'Payment approved!']);

        } elseif ($action === 'reject') {
            $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'No reason provided.';
            update_post_meta($payment_id, 'payment_status', 'rejected');
            update_post_meta($payment_id, 'rejection_reason', $reason);
            LMB_Ad_Manager::log_activity(sprintf('Payment #%d rejected by %s.', $payment_id, wp_get_current_user()->display_name));
            // You might want a notification for rejected payments as well.
            
            wp_send_json_success(['message' => 'Payment rejected.']);
        }
    }
}