<?php
if (!defined('ABSPATH')) exit;

class LMB_Ad_Manager {
    public static function init() {
        // User-triggered action from dashboard
        add_action('admin_post_lmb_user_publish_ad', [__CLASS__, 'user_requests_publication']);
        
        // Admin AJAX actions for quick management
        add_action('wp_ajax_lmb_ad_status_change', [__CLASS__, 'ajax_ad_status_change']);
        
        // Admin list table customizations
        add_filter('manage_lmb_legal_ad_posts_columns', [__CLASS__, 'set_custom_columns']);
        add_action('manage_lmb_legal_ad_posts_custom_column', [__CLASS__, 'render_custom_columns'], 10, 2);
        add_action('wp_ajax_lmb_submit_for_review', [__CLASS__, 'ajax_user_submit_for_review']);
    }

    public static function ajax_user_submit_for_review() {
        check_ajax_referer('lmb_frontend_ajax_nonce', 'nonce');

        if (!is_user_logged_in() || !isset($_POST['ad_id'])) {
            wp_send_json_error(['message' => 'Invalid request.']);
        }
        
        $ad_id = intval($_POST['ad_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        self::log_activity(sprintf('Ad #%d submitted for review by %s via AJAX.', $ad_id, wp_get_current_user()->display_name));
        
        wp_send_json_success();
    }

    public static function set_custom_columns($columns) {
        unset($columns['author'], $columns['date']);
        $columns['lmb_client'] = __('Client', 'lmb-core');
        $columns['ad_type'] = __('Ad Type', 'lmb-core');
        $columns['lmb_status'] = __('Status', 'lmb-core');
        $columns['lmb_actions'] = __('Actions', 'lmb-core');
        $columns['date'] = __('Date', 'lmb-core');
        return $columns;
    }
    
    public static function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'lmb_client':
                $client_id = get_post_meta($post_id, 'lmb_client_id', true);
                $user = get_userdata($client_id);
                echo $user ? '<a href="'.get_edit_user_link($client_id).'">'.esc_html($user->display_name).'</a>' : 'N/A';
                break;
                
            case 'lmb_status':
                $status = get_post_meta($post_id, 'lmb_status', true);
                echo '<span class="lmb-status-badge lmb-status-'.esc_attr($status).'">'.esc_html(ucwords(str_replace('_', ' ', $status))).'</span>';
                break;
                
            case 'ad_type':
                echo esc_html(get_post_meta($post_id, 'ad_type', true));
                break;
                
            case 'lmb_actions':
                if (get_post_meta($post_id, 'lmb_status', true) === 'pending_review') {
                    echo '<button class="button button-primary button-small lmb-ad-action" data-action="approve" data-id="'.$post_id.'">'.__('Approve', 'lmb-core').'</button>';
                    echo '<button class="button button-secondary button-small lmb-ad-action" data-action="deny" data-id="'.$post_id.'">'.__('Deny', 'lmb-core').'</button>';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    public static function user_requests_publication() {
        if (!is_user_logged_in() || !isset($_POST['_wpnonce'], $_POST['ad_id']) || !wp_verify_nonce($_POST['_wpnonce'], 'lmb_user_publish_ad')) {
            wp_die('Security check failed.');
        }

        $ad_id = intval($_POST['ad_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != get_current_user_id()) {
            wp_die('Invalid ad or permission denied.');
        }

        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        self::log_activity(sprintf('Ad #%d submitted for review by %s.', $ad_id, wp_get_current_user()->display_name));
        
        wp_safe_redirect(add_query_arg('status', 'pending', home_url('/dashboard')));
        exit;
    }

    public static function ajax_ad_status_change() {
        check_ajax_referer('lmb_admin_ajax_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $action = isset($_POST['ad_action']) ? sanitize_key($_POST['ad_action']) : '';

        if ($action === 'approve') {
            $result = self::approve_ad($ad_id);
            if ($result['success']) {
                wp_send_json_success(['message' => $result['message']]);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } elseif ($action === 'deny') {
            $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'No reason provided.';
            self::deny_ad($ad_id, $reason);
            wp_send_json_success(['message' => 'Ad has been denied.']);
        } else {
            wp_send_json_error(['message' => 'Invalid action.']);
        }
    }

    private static function approve_ad($ad_id) {
        $client_id = (int) get_post_meta($ad_id, 'lmb_client_id', true);
        if (!$client_id) {
            return ['success' => false, 'message' => 'Client ID not found for this ad.'];
        }
        
        $cost = class_exists('LMB_Points') ? LMB_Points::get_cost_per_ad($client_id) : 1;
        
        if (class_exists('LMB_Points') && LMB_Points::get_balance($client_id) < $cost) {
            self::deny_ad($ad_id, 'Insufficient points balance.');
            return ['success' => false, 'message' => 'Client has insufficient points. Ad has been automatically denied.'];
        }
        
        $new_balance = class_exists('LMB_Points') ? LMB_Points::deduct($client_id, $cost, sprintf('Publication of legal ad #%d', $ad_id)) : true;
        if ($new_balance === false) {
            return ['success' => false, 'message' => 'Failed to deduct points. Ad not approved.'];
        }
        
        wp_update_post(['ID' => $ad_id, 'post_status' => 'publish']);
        update_post_meta($ad_id, 'lmb_status', 'published');
        update_post_meta($ad_id, 'approved_by', get_current_user_id());
        update_post_meta($ad_id, 'approved_date', current_time('mysql'));
        
        // Generate PDF only if PDF generator is available
        if (class_exists('LMB_PDF_Generator')) {
            $pdf_url = LMB_PDF_Generator::create_ad_pdf_from_fulltext($ad_id);
            update_post_meta($ad_id, 'ad_pdf_url', $pdf_url);
        }
        
        // Generate invoice only if invoice handler is available
        if (class_exists('LMB_Invoice_Handler')) {
            $invoice_url = LMB_Invoice_Handler::create_ad_publication_invoice($client_id, $ad_id, $cost, $new_balance);
            update_post_meta($ad_id, 'ad_invoice_pdf_url', $invoice_url);
        }
        
        self::log_activity(sprintf('Ad #%d approved by %s. Cost: %d points.', $ad_id, wp_get_current_user()->display_name, $cost));
        
        // Send notification if notification manager is available
        if (class_exists('LMB_Notification_Manager')) {
            LMB_Notification_Manager::notify_ad_approved($client_id, $ad_id);
        }

        return ['success' => true, 'message' => 'Ad approved and published successfully.'];
    }
    
    private static function deny_ad($ad_id, $reason) {
        update_post_meta($ad_id, 'lmb_status', 'denied');
        update_post_meta($ad_id, 'denial_reason', $reason);
        wp_update_post(['ID' => $ad_id, 'post_status' => 'draft']);

        $client_id = get_post_meta($ad_id, 'lmb_client_id', true);
        if ($client_id) {
            if (class_exists('LMB_Notification_Manager')) {
                LMB_Notification_Manager::notify_ad_denied($client_id, $ad_id, $reason);
            }
        }
        self::log_activity(sprintf('Ad #%d denied by %s. Reason: %s', $ad_id, wp_get_current_user()->display_name, $reason));
    }

    public static function log_activity($msg) {
        $log = get_option('lmb_activity_log', []);
        array_unshift($log, ['time' => current_time('mysql'), 'msg'  => $msg, 'user' => get_current_user_id()]);
        if (count($log) > 200) $log = array_slice($log, 0, 200);
        update_option('lmb_activity_log', $log);
    }
}