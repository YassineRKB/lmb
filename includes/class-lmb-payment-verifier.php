<?php
if (!defined('ABSPATH')) exit;

class LMB_Payment_Verifier {
    public static function init() {
        add_filter('manage_lmb_payment_posts_columns', [__CLASS__, 'set_custom_columns']);
        add_action('manage_lmb_payment_posts_custom_column', [__CLASS__, 'render_custom_columns'], 10, 2);
    }

    public static function set_custom_columns($columns) {
        unset($columns['title'], $columns['date']);
        $columns['client'] = __('Client', 'lmb-core');
        $columns['package'] = __('Package', 'lmb-core');
        $columns['reference'] = __('Référence', 'lmb-core');
        $columns['proof'] = __('Preuve', 'lmb-core');
        $columns['status'] = __('Statut', 'lmb-core');
        $columns['actions'] = __('Actions', 'lmb-core');
        $columns['date'] = __('Soumis', 'lmb-core');
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
                if ($url) echo '<a href="'.esc_url($url).'" target="_blank" class="button button-small">Voir Preuve</a>';
                break;
            case 'status':
                $status = get_post_meta($post_id, 'payment_status', true);
                echo '<span class="lmb-status-badge lmb-status-'.esc_attr($status).'">'.esc_html(ucfirst($status)).'</span>';
                break;
            case 'actions':
                if (get_post_meta($post_id, 'payment_status', true) === 'pending') {
                    echo '<button class="button button-primary button-small lmb-payment-action" data-action="approve" data-id="'.$post_id.'">Approuver</button>';
                    echo '<button class="button button-secondary button-small lmb-payment-action" data-action="reject" data-id="'.$post_id.'">Rejeter</button>';
                }
                break;
        }
    }

    // This function is called by the central AJAX handler, so it doesn't need its own nonce check.
    public static function handle_payment_action($payment_id, $action, $reason = '') {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
            return;
        }

        $user_id = (int) get_post_meta($payment_id, 'user_id', true);
        $package_id = (int) get_post_meta($payment_id, 'package_id', true);

        if (!$user_id || !$package_id) {
            wp_send_json_error(['message' => 'Payment record is missing critical data (user or package ID).']);
            return;
        }
        
        if ($action === 'approve') {
            $points = (int) get_post_meta($package_id, 'points', true);
            $cost_per_ad = (int) get_post_meta($package_id, 'cost_per_ad', true);
            
            LMB_Points::add($user_id, $points, 'Achat du package: ' . get_the_title($package_id));
            LMB_Points::set_cost_per_ad($user_id, $cost_per_ad);
            
            update_post_meta($payment_id, 'payment_status', 'approved');
            LMB_Ad_Manager::log_activity(sprintf('Payment #%d approved by %s.', $payment_id, wp_get_current_user()->display_name));
            if (class_exists('LMB_Notification_Manager')) {
                LMB_Notification_Manager::notify_payment_verified($user_id, $package_id, $points);
            }
            
            wp_send_json_success(['message' => 'Paiement approuvé ! Les points ont été ajoutés au compte du client.']);

        } elseif ($action === 'reject') {
            update_post_meta($payment_id, 'payment_status', 'rejected');
            update_post_meta($payment_id, 'rejection_reason', $reason);
            LMB_Ad_Manager::log_activity(sprintf('Paiement #%d refusee par %s.', $payment_id, wp_get_current_user()->display_name));
            
            wp_send_json_success(['message' => 'Le paiement a été refusee.']);
        }
    }
}