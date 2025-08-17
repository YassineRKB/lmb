<?php
if (!defined('ABSPATH')) exit;

class LMB_Ad_Manager {
    public static function init() {
        // User actions
        add_action('admin_post_lmb_user_publish_ad', [__CLASS__, 'user_publish_ad']);
        
        // Admin actions
        add_action('wp_ajax_lmb_quick_status_change', [__CLASS__, 'ajax_quick_status_change']);
        add_action('admin_post_lmb_admin_accept_ad', [__CLASS__, 'admin_accept_ad']);
        add_action('admin_post_lmb_admin_deny_ad', [__CLASS__, 'admin_deny_ad']);
        
        // Admin list customization
        add_filter('manage_lmb_legal_ad_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_lmb_legal_ad_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
        add_filter('bulk_actions-edit-lmb_legal_ad', [__CLASS__, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-lmb_legal_ad', [__CLASS__, 'handle_bulk_actions'], 10, 3);
    }

    public static function cols($cols) {
        $cols['lmb_client'] = __('Client', 'lmb-core');
        $cols['lmb_status'] = __('LMB Status', 'lmb-core');
        $cols['ad_type'] = __('Ad Type', 'lmb-core');
        $cols['lmb_actions'] = __('Quick Actions', 'lmb-core');
        return $cols;
    }
    
    public static function col_content($col, $post_id) {
        switch ($col) {
            case 'lmb_client':
                $client_id = get_field('lmb_client_id', $post_id);
                $user = get_userdata($client_id);
                echo $user ? esc_html($user->display_name) : '-';
                break;
                
            case 'lmb_status':
                $status = get_field('lmb_status', $post_id);
                $badge_class = 'lmb-status-' . str_replace('_', '-', $status);
                echo '<span class="lmb-status-badge ' . esc_attr($badge_class) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span>';
                break;
                
            case 'ad_type':
                echo esc_html(get_field('ad_type', $post_id));
                break;
                
            case 'lmb_actions':
                $status = get_field('lmb_status', $post_id);
                echo '<div class="lmb-quick-actions-column">';
                
                if ($status === 'pending_review') {
                    echo '<button class="button lmb-quick-approve" data-post-id="' . $post_id . '">Approve</button>';
                    echo '<button class="button lmb-quick-deny" data-post-id="' . $post_id . '">Deny</button>';
                }
                
                echo '<select class="lmb-quick-status-change" data-post-id="' . $post_id . '">';
                echo '<option value="">Change Status...</option>';
                echo '<option value="pending_review">Pending Review</option>';
                echo '<option value="published">Published</option>';
                echo '<option value="denied">Denied</option>';
                echo '</select>';
                
                echo '</div>';
                break;
        }
    }
    
    public static function add_bulk_actions($actions) {
        $actions['lmb_bulk_approve'] = __('Approve Selected', 'lmb-core');
        $actions['lmb_bulk_deny'] = __('Deny Selected', 'lmb-core');
        $actions['lmb_bulk_pending'] = __('Set Pending Review', 'lmb-core');
        return $actions;
    }
    
    public static function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if (!in_array($action, ['lmb_bulk_approve', 'lmb_bulk_deny', 'lmb_bulk_pending'])) {
            return $redirect_to;
        }
        
        $status_map = [
            'lmb_bulk_approve' => 'published',
            'lmb_bulk_deny' => 'denied',
            'lmb_bulk_pending' => 'pending_review'
        ];
        
        $new_status = $status_map[$action];
        $updated = 0;
        
        foreach ($post_ids as $post_id) {
            if ($action === 'lmb_bulk_approve') {
                self::approve_ad($post_id);
            } else {
                update_field('lmb_status', $new_status, $post_id);
            }
            $updated++;
        }
        
        return add_query_arg('lmb_updated', $updated, $redirect_to);
    }
    
    /**
     * AJAX handler for quick status changes
     */
    public static function ajax_quick_status_change() {
        if (!current_user_can('edit_others_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        if (!wp_verify_nonce($_POST['nonce'], 'lmb_admin_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        $post_id = (int) $_POST['post_id'];
        $new_status = sanitize_text_field($_POST['new_status']);
        
        if ($new_status === 'published') {
            $result = self::approve_ad($post_id);
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result['message']);
            }
        } else {
            update_field('lmb_status', $new_status, $post_id);
            wp_send_json_success(['message' => 'Status updated successfully']);
        }
    }

    /** User requests publication → pending_review */
    public static function user_publish_ad() {
        if (!is_user_logged_in()) wp_die('Auth required.');
        check_admin_referer('lmb_user_publish_ad');

        $ad_id = isset($_POST['ad_id']) ? (int) $_POST['ad_id'] : 0;
        $owner = (int) get_field('lmb_client_id', $ad_id);
        if (!$ad_id || $owner !== get_current_user_id()) wp_die('Invalid ad.');

        update_field('lmb_status', 'pending_review', $ad_id);
        
        self::log_activity(sprintf('Ad #%d submitted for review by %s', $ad_id, wp_get_current_user()->user_login));
        
        wp_safe_redirect(add_query_arg(['ad_pending' => 1], home_url('/dashboard')));
        exit;
    }

    /**
     * Approve ad with points deduction and PDF generation
     */
    public static function approve_ad($ad_id) {
        $client_id = (int) get_field('lmb_client_id', $ad_id);
        if (!$client_id) {
            return ['success' => false, 'message' => 'Invalid ad or client'];
        }
        
        $cost = LMB_Points::get_cost_per_ad($client_id);
        if ($cost <= 0) {
            $cost = (int) get_option('lmb_default_cost_per_ad', 1);
        }
        
        // Check if user has enough points
        if (LMB_Points::get_balance($client_id) < $cost) {
            return ['success' => false, 'message' => 'Insufficient points'];
        }
        
        // Deduct points
        $new_balance = LMB_Points::deduct($client_id, $cost, 'Legal ad publication #' . $ad_id);
        if ($new_balance === false) {
            return ['success' => false, 'message' => 'Failed to deduct points'];
        }
        
        // Update status
        update_field('lmb_status', 'published', $ad_id);
        wp_update_post(['ID' => $ad_id, 'post_status' => 'publish']);
        
        // Generate PDFs
        $ad_pdf = LMB_PDF_Generator::create_ad_pdf_from_fulltext($ad_id);
        update_field('ad_pdf_url', $ad_pdf, $ad_id);
        
        $invoice_pdf = LMB_Invoice_Handler::create_ad_publication_invoice($client_id, $ad_id, $cost, $new_balance);
        update_post_meta($ad_id, 'ad_invoice_pdf_url', $invoice_pdf);
        
        // Log activity
        self::log_activity(sprintf(
            'Ad #%d approved by %s. Cost: %d points. Client balance: %d',
            $ad_id,
            wp_get_current_user()->user_login,
            $cost,
            $new_balance
        ));
        
        // Send notification to client
        LMB_Notification_Manager::notify_ad_approved($client_id, $ad_id);
        
        return ['success' => true, 'message' => 'Ad approved successfully', 'points_deducted' => $cost];
    }
    
    /** Admin accepts: publish, deduct points, generate PDFs */
    public static function admin_accept_ad() {
        if (!current_user_can('edit_others_posts')) wp_die('No permission.');
        check_admin_referer('lmb_admin_accept_ad');

        $ad_id = (int) ($_POST['ad_id'] ?? 0);
        $result = self::approve_ad($ad_id);
        
        if ($result['success']) {
            wp_safe_redirect(add_query_arg(['ad_published' => 1], wp_get_referer()));
        } else {
            wp_safe_redirect(add_query_arg(['ad_accept_failed' => urlencode($result['message'])], wp_get_referer()));
        }
        exit;
    }

    /** Admin denies → lmb_denied */
    public static function admin_deny_ad() {
        if (!current_user_can('edit_others_posts')) wp_die('No permission.');
        check_admin_referer('lmb_admin_deny_ad');

        $ad_id  = (int) ($_POST['ad_id'] ?? 0);
        if (!$ad_id) wp_die('Invalid ad.');
        
        update_field('lmb_status', 'denied', $ad_id);
        
        $client_id = get_field('lmb_client_id', $ad_id);
        if ($client_id) {
            LMB_Notification_Manager::notify_ad_denied($client_id, $ad_id);
        }

        self::log_activity(sprintf('Ad #%d denied by %s.', $ad_id, wp_get_current_user()->user_login));

        wp_safe_redirect(add_query_arg(['ad_denied' => 1], wp_get_referer()));
        exit;
    }

    public static function log_activity($msg) {
        $log = get_option('lmb_activity_log', []);
        if (!is_array($log)) $log = [];
        array_unshift($log, [
            'time' => current_time('mysql'), 
            'msg' => sanitize_text_field($msg), 
            'user' => get_current_user_id(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $log = array_slice($log, 0, 200);
        update_option('lmb_activity_log', $log);
    }
}
