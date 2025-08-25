<?php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {

    public static function init() {
        $actions = [
            'lmb_ad_status_change', 'lmb_user_submit_for_review', 'lmb_payment_action',
            'lmb_get_balance_history', 'lmb_load_admin_tab', 'lmb_search_user', 'lmb_update_balance',
            'lmb_generate_package_invoice', 'lmb_get_notifications', 'lmb_mark_notification_read',
            'lmb_mark_all_notifications_read', 'lmb_save_package', 'lmb_delete_package',
            'lmb_upload_newspaper', 'lmb_upload_bank_proof', 'lmb_fetch_users', 'lmb_fetch_ads'
        ];
        foreach ($actions as $action) {
            add_action('wp_ajax_' . $action, [__CLASS__, 'handle_request']);
        }
    }

    public static function handle_request() {
        check_ajax_referer('lmb_nonce', 'nonce');
        $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';
        if (method_exists(__CLASS__, $action)) {
            self::$action();
        } else {
            wp_send_json_error(['message' => 'Invalid AJAX Action.'], 400);
        }
    }

    private static function lmb_upload_newspaper() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);
        if (empty($_POST['newspaper_title']) || empty($_FILES['newspaper_pdf'])) wp_send_json_error(['message' => 'Missing required fields.']);
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $pdf_id = media_handle_upload('newspaper_pdf', 0);
        if (is_wp_error($pdf_id)) wp_send_json_error(['message' => 'PDF Upload Error: ' . $pdf_id->get_error_message()]);
        
        $thumb_id = null;
        if (!empty($_FILES['newspaper_thumbnail']['name'])) {
            $thumb_id = media_handle_upload('newspaper_thumbnail', 0);
        }

        $post_id = wp_insert_post([
            'post_type' => 'lmb_newspaper',
            'post_title' => sanitize_text_field($_POST['newspaper_title']),
            'post_status' => 'publish',
            'post_date' => sanitize_text_field($_POST['newspaper_date']) . ' 00:00:00',
        ]);
        if (is_wp_error($post_id)) wp_send_json_error(['message' => $post_id->get_error_message()]);

        update_post_meta($post_id, 'newspaper_pdf', $pdf_id);
        if ($thumb_id && !is_wp_error($thumb_id)) set_post_thumbnail($post_id, $thumb_id);

        wp_send_json_success(['message' => 'Newspaper uploaded successfully.']);
    }

    private static function lmb_upload_bank_proof() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'You must be logged in.']);
        if (empty($_POST['package_id']) || empty($_FILES['proof_file'])) wp_send_json_error(['message' => 'Missing required fields.']);
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $user_id = get_current_user_id();
        $attachment_id = media_handle_upload('proof_file', 0);
        if (is_wp_error($attachment_id)) wp_send_json_error(['message' => 'File Upload Error: ' . $attachment_id->get_error_message()]);

        $package_id = intval($_POST['package_id']);
        $payment_id = wp_insert_post([
            'post_type' => 'lmb_payment',
            'post_title' => sprintf('Proof from %s for %s', wp_get_current_user()->display_name, get_the_title($package_id)),
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);
        if (is_wp_error($payment_id)) {
            wp_delete_attachment($attachment_id, true);
            wp_send_json_error(['message' => 'Could not create payment record.']);
        }

        update_post_meta($payment_id, 'user_id', $user_id);
        update_post_meta($payment_id, 'package_id', $package_id);
        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        update_post_meta($payment_id, 'payment_reference', sanitize_text_field($_POST['payment_reference']));
        update_post_meta($payment_id, 'payment_status', 'pending');
        
        LMB_Ad_Manager::log_activity(sprintf('Payment proof #%d submitted.', $payment_id));
        LMB_Notification_Manager::notify_admin('New Payment Proof Submitted', sprintf('User %s submitted proof for "%s".', wp_get_current_user()->display_name, get_the_title($package_id)));

        wp_send_json_success(['message' => 'Your proof has been submitted for review.']);
    }

    private static function lmb_save_package() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied']);
        
        $package_id = isset($_POST['package_id']) && !empty($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $cost = intval($_POST['cost_per_ad']);
        $desc = sanitize_textarea_field($_POST['description']);
        if (!$name || !$price || !$points || !$cost) wp_send_json_error(['message' => 'All fields are required']);

        $post_data = ['post_title' => $name, 'post_content' => $desc, 'post_type' => 'lmb_package', 'post_status' => 'publish'];
        if ($package_id) {
            $post_data['ID'] = $package_id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
        }
        
        if (is_wp_error($result) || $result === 0) wp_send_json_error(['message' => 'Could not save the package.']);
        
        $new_pkg_id = $package_id ?: $result;
        update_post_meta($new_pkg_id, 'price', $price);
        update_post_meta($new_pkg_id, 'points', $points);
        update_post_meta($new_pkg_id, 'cost_per_ad', $cost);
        
        LMB_Ad_Manager::log_activity(sprintf('Package "%s" %s', $name, $package_id ? 'updated' : 'created'));
        wp_send_json_success(['message' => 'Package saved successfully.']);
    }

    // --- All other handlers remain below ---

    private static function lmb_ad_status_change() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad_action = isset($_POST['ad_action']) ? sanitize_key($_POST['ad_action']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        if (!$ad_id || !$ad_action) wp_send_json_error(['message' => 'Missing parameters.'], 400);

        if ($ad_action === 'approve') {
            $result = LMB_Ad_Manager::approve_ad($ad_id);
            if ($result['success']) wp_send_json_success(['message' => $result['message']]);
            else wp_send_json_error(['message' => $result['message']]);
        } elseif ($ad_action === 'deny') {
            LMB_Ad_Manager::deny_ad($ad_id, $reason);
            wp_send_json_success(['message' => 'Ad has been denied.']);
        }
    }
    
    private static function lmb_user_submit_for_review() {
        if (!is_user_logged_in() || !isset($_POST['ad_id'])) wp_send_json_error(['message' => 'Invalid request.']);
        $ad_id = intval($_POST['ad_id']);
        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != get_current_user_id()) wp_send_json_error(['message' => 'Permission denied.']);

        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        LMB_Ad_Manager::log_activity(sprintf('Ad #%d ("%s") submitted for review.', $ad_id, $ad->post_title));
        LMB_Notification_Manager::notify_admins_ad_pending($ad_id);
        wp_send_json_success(['message' => 'Ad submitted for review.']);
    }

    private static function lmb_payment_action() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);
        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
        $action = isset($_POST['payment_action']) ? sanitize_key($_POST['payment_action']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'No reason provided.';
        LMB_Payment_Verifier::handle_payment_action($payment_id, $action, $reason);
    }
    
    private static function lmb_get_balance_history() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) wp_send_json_error(['message' => 'Invalid user ID'], 400);
        wp_send_json_success(['history' => LMB_Points::get_transactions($user_id, 10)]);
    }

    private static function lmb_load_admin_tab() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : 'feed';
        wp_send_json_success(LMB_Admin_Actions_Widget::get_tab_content($tab));
    }

    private static function lmb_search_user() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        $term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if (empty($term)) wp_send_json_error(['message' => 'Search term is empty.'], 400);
        $user_query = new WP_User_Query(['search' => '*' . esc_attr($term) . '*', 'search_columns' => ['ID', 'user_login', 'user_email', 'display_name'], 'number' => 1]);
        $user = $user_query->get_results()[0] ?? null;
        if ($user) {
            wp_send_json_success(['user' => ['ID' => $user->ID, 'display_name' => $user->display_name, 'user_email' => $user->user_email, 'balance' => LMB_Points::get_balance($user->ID)]]);
        } else {
            wp_send_json_error(['message' => 'User not found.'], 404);
        }
    }

    private static function lmb_update_balance() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount  = isset($_POST['amount']) ? absint($_POST['amount']) : 0;
        $reason  = !empty($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'Manual balance adjustment';
        $action  = isset($_POST['balance_action']) ? sanitize_key($_POST['balance_action']) : '';
        if (!$user_id || !$action) wp_send_json_error(['message' => 'Missing user ID or action.'], 400);
        switch ($action) {
            case 'add': $new_balance = LMB_Points::add($user_id, $amount, $reason); break;
            case 'subtract': $new_balance = LMB_Points::deduct($user_id, $amount, $reason); break;
            case 'set': $new_balance = LMB_Points::set_balance($user_id, $amount, $reason); break;
            default: wp_send_json_error(['message' => 'Invalid action.'], 400);
        }
        if ($new_balance === false) wp_send_json_error(['message' => 'Insufficient balance.'], 400);
        wp_send_json_success(['message' => 'Balance updated successfully!', 'new_balance' => $new_balance]);
    }

    private static function lmb_generate_package_invoice() {
        if (!is_user_logged_in() || !isset($_POST['pkg_id'])) wp_send_json_error(['message' => 'Invalid request.'], 403);
        $pdf_url = LMB_Invoice_Handler::generate_package_invoice_pdf_for_user(get_current_user_id(), intval($_POST['pkg_id']));
        if ($pdf_url) wp_send_json_success(['pdf_url' => $pdf_url]);
        else wp_send_json_error(['message' => 'Could not generate invoice.']);
    }

    private static function lmb_get_notifications() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized'], 401);
        $user_id = get_current_user_id();
        wp_send_json_success(['items' => LMB_Notification_Manager::get_latest($user_id, 10), 'unread' => LMB_Notification_Manager::get_unread_count($user_id)]);
    }

    private static function lmb_mark_notification_read() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized'], 401);
        $nid = isset($_POST['id']) ? absint($_POST['id']) : 0;
        wp_send_json_success(['ok' => (bool) LMB_Notification_Manager::mark_read(get_current_user_id(), $nid)]);
    }

    private static function lmb_mark_all_notifications_read() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Unauthorized'], 401);
        wp_send_json_success(['ok' => (bool) LMB_Notification_Manager::mark_all_read(get_current_user_id())]);
    }

    private static function lmb_delete_package() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied']);
        $pkg_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        if (!$pkg_id) wp_send_json_error(['message' => 'Invalid package ID']);
        $package = get_post($pkg_id);
        if (!$package || $package->post_type !== 'lmb_package') wp_send_json_error(['message' => 'Package not found']);
        if (!wp_delete_post($pkg_id, true)) wp_send_json_error(['message' => 'Failed to delete package']);
        LMB_Ad_Manager::log_activity(sprintf('Package "%s" deleted', $package->post_title));
        wp_send_json_success(['message' => 'Package deleted.']);
    }
}