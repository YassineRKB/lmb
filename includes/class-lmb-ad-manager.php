<?php
if (!defined('ABSPATH')) exit;

class LMB_Ad_Manager {
    public static function init() {
        // User actions
        add_action('admin_post_lmb_user_publish_ad', [__CLASS__, 'user_publish_ad']);
        
        // Admin AJAX actions for quick changes
        add_action('wp_ajax_lmb_quick_status_change', [__CLASS__, 'ajax_quick_status_change']);
        
        // Admin list customization
        add_filter('manage_lmb_legal_ad_posts_columns', [__CLASS__, 'set_custom_edit_columns']);
        add_action('manage_lmb_legal_ad_posts_custom_column', [__CLASS__, 'custom_column_content'], 10, 2);
        add_filter('bulk_actions-edit-lmb_legal_ad', [__CLASS__, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-lmb_legal_ad', [__CLASS__, 'handle_bulk_actions'], 10, 3);
    }

    public static function set_custom_edit_columns($columns) {
        unset($columns['date']);
        $columns['lmb_client'] = __('Client', 'lmb-core');
        $columns['ad_type'] = __('Ad Type', 'lmb-core');
        $columns['lmb_status'] = __('Status', 'lmb-core');
        $columns['lmb_actions'] = __('Actions', 'lmb-core');
        $columns['date'] = __('Date', 'lmb-core');
        return $columns;
    }
    
    public static function custom_column_content($column, $post_id) {
        switch ($column) {
            case 'lmb_client':
                $client_id = get_post_meta($post_id, 'lmb_client_id', true);
                $user = get_userdata($client_id);
                echo $user ? esc_html($user->display_name) : 'N/A';
                break;
                
            case 'lmb_status':
                $status = get_post_meta($post_id, 'lmb_status', true);
                $badge_class = 'lmb-status-' . esc_attr(str_replace('_', '-', $status));
                echo '<span class="lmb-status-badge ' . $badge_class . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span>';
                break;
                
            case 'ad_type':
                echo esc_html(get_post_meta($post_id, 'ad_type', true));
                break;
                
            case 'lmb_actions':
                $status = get_post_meta($post_id, 'lmb_status', true);
                if ($status === 'pending_review') {
                    echo '<button class="button button-small lmb-quick-approve" data-post-id="' . $post_id . '">Approve</button>';
                    echo '<button class="button button-small lmb-quick-deny" data-post-id="' . $post_id . '">Deny</button>';
                } else {
                    echo '—';
                }
                break;
        }
    }
    
    public static function add_bulk_actions($actions) {
        $actions['lmb_bulk_approve'] = __('Approve & Publish', 'lmb-core');
        $actions['lmb_bulk_deny'] = __('Deny Selected', 'lmb-core');
        return $actions;
    }
    
    public static function handle_bulk_actions($redirect_to, $action, $post_ids) {
        $updated = 0;
        foreach ($post_ids as $post_id) {
            if ($action === 'lmb_bulk_approve') {
                self::approve_ad($post_id);
            } elseif ($action === 'lmb_bulk_deny') {
                self::deny_ad($post_id);
            }
            $updated++;
        }
        return add_query_arg('lmb_updated', $updated, $redirect_to);
    }
    
    public static function ajax_quick_status_change() {
        check_ajax_referer('lmb_admin_nonce', 'nonce');
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $new_status = isset($_POST['new_status']) ? sanitize_key($_POST['new_status']) : '';
        
        if ($new_status === 'published') {
            $result = self::approve_ad($post_id);
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        } elseif ($new_status === 'denied') {
            self::deny_ad($post_id);
            wp_send_json_success(['message' => 'Ad denied successfully.']);
        } else {
            wp_send_json_error(['message' => 'Invalid status.']);
        }
    }

    public static function user_publish_ad() {
        if (!is_user_logged_in() || !isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'lmb_user_publish_ad')) {
            wp_die('Security check failed.');
        }

        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad = get_post($ad_id);
        
        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            wp_die('Invalid ad.');
        }

        $owner_id = (int) get_post_meta($ad_id, 'lmb_client_id', true);
        if ($owner_id !== get_current_user_id() && !current_user_can('edit_others_posts')) {
            wp_die('You do not have permission to edit this ad.');
        }

        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        self::log_activity(sprintf('Ad #%d submitted for review by %s.', $ad_id, wp_get_current_user()->display_name));
        
        wp_safe_redirect(add_query_arg(['ad_pending' => 1], home_url('/dashboard')));
        exit;
    }

    public static function approve_ad($ad_id) {
        $client_id = (int) get_post_meta($ad_id, 'lmb_client_id', true);
        if (!$client_id) {
            return ['success' => false, 'message' => 'Client ID not found for this ad.'];
        }
        
        $cost = LMB_Points::get_cost_per_ad($client_id);
        if ($cost <= 0) {
            $cost = (int) get_option('lmb_default_cost_per_ad', 1);
        }
        
        if (LMB_Points::get_balance($client_id) < $cost) {
            self::deny_ad($ad_id, 'Insufficient points balance.');
            return ['success' => false, 'message' => 'Client has insufficient points. Ad has been denied.'];
        }
        
        $new_balance = LMB_Points::deduct($client_id, $cost, 'Legal ad publication #' . $ad_id);
        if ($new_balance === false) {
            return ['success' => false, 'message' => 'Failed to deduct points.'];
        }
        
        update_post_meta($ad_id, 'lmb_status', 'published');
        wp_update_post(['ID' => $ad_id, 'post_status' => 'publish']);
        
        // Generate PDFs
        $ad_pdf_url = LMB_PDF_Generator::create_ad_pdf_from_fulltext($ad_id);
        update_post_meta($ad_id, 'ad_pdf_url', $ad_pdf_url);
        
        $invoice_pdf_url = LMB_Invoice_Handler::create_ad_publication_invoice($client_id, $ad_id, $cost, $new_balance);
        update_post_meta($ad_id, 'ad_invoice_pdf_url', $invoice_pdf_url);
        
        self::log_activity(sprintf(
            'Ad #%d approved by %s. Cost: %d points. Client new balance: %d',
            $ad_id, wp_get_current_user()->display_name, $cost, $new_balance
        ));
        
        LMB_Notification_Manager::notify_ad_approved($client_id, $ad_id);
        return ['success' => true, 'message' => 'Ad approved successfully.'];
    }
    
    public static function deny_ad($ad_id, $reason = 'Denied by administrator.') {
        update_post_meta($ad_id, 'lmb_status', 'denied');
        update_post_meta($ad_id, 'denial_reason', $reason);
        wp_update_post(['ID' => $ad_id, 'post_status' => 'draft']);

        $client_id = get_post_meta($ad_id, 'lmb_client_id', true);
        if ($client_id) {
            LMB_Notification_Manager::notify_ad_denied($client_id, $ad_id, $reason);
        }

        self::log_activity(sprintf('Ad #%d denied by %s.', $ad_id, wp_get_current_user()->display_name));
    }

    public static function log_activity($msg) {
        $log = get_option('lmb_activity_log', []);
        if (!is_array($log)) $log = [];
        array_unshift($log, [
            'time' => current_time('mysql'), 
            'msg'  => sanitize_text_field($msg), 
            'user' => get_current_user_id()
        ]);
        if (count($log) > 200) {
            $log = array_slice($log, 0, 200);
        }
        update_option('lmb_activity_log', $log);
    }
}