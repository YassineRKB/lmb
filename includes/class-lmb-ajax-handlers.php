<?php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {

    public static function init() {
        // Existing
        add_action('wp_ajax_lmb_get_balance_history', [__CLASS__, 'get_balance_history']);

        // Centralized admin tabs loader (moved out of widget)
        add_action('wp_ajax_lmb_load_admin_tab', [__CLASS__, 'load_admin_tab']);

        // Centralized balance manipulation handlers (moved out of widget)
        add_action('wp_ajax_lmb_search_user', [__CLASS__, 'search_user']);
        add_action('wp_ajax_lmb_update_balance', [__CLASS__, 'update_balance']);
        // handlers for package editor
        add_action('wp_ajax_lmb_save_package', function() {
            check_ajax_referer('lmb_packages_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Access denied']);
            }

            $package_id = intval($_POST['package_id']);
            $name = sanitize_text_field($_POST['name']);
            $price = floatval($_POST['price']);
            $points = intval($_POST['points']);
            $cost_per_ad = intval($_POST['cost_per_ad']);
            $description = sanitize_textarea_field($_POST['description']);

            if (!$name || !$price || !$points || !$cost_per_ad) {
                wp_send_json_error(['message' => __('All fields are required', 'lmb-core')]);
            }

            $post_data = [
                'post_title' => $name,
                'post_content' => $description,
                'post_type' => 'lmb_package',
                'post_status' => 'publish'
            ];

            if ($package_id) {
                // Update existing package
                $post_data['ID'] = $package_id;
                $result = wp_update_post($post_data);
            } else {
                // Create new package
                $result = wp_insert_post($post_data);
            }

            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            $package_id = $package_id ?: $result;

            // Update meta fields
            update_post_meta($package_id, 'price', $price);
            update_post_meta($package_id, 'points', $points);
            update_post_meta($package_id, 'cost_per_ad', $cost_per_ad);

            // Log the action
            if (class_exists('LMB_Ad_Manager')) {
                LMB_Ad_Manager::log_activity(sprintf(
                    'Package "%s" %s by admin %s',
                    $name,
                    $_POST['package_id'] ? 'updated' : 'created',
                    wp_get_current_user()->display_name
                ));
            }

            wp_send_json_success(['package_id' => $package_id]);
        });

        add_action('wp_ajax_lmb_delete_package', function() {
            check_ajax_referer('lmb_packages_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Access denied']);
            }

            $package_id = intval($_POST['package_id']);
            
            if (!$package_id) {
                wp_send_json_error(['message' => __('Invalid package ID', 'lmb-core')]);
            }

            $package = get_post($package_id);
            if (!$package || $package->post_type !== 'lmb_package') {
                wp_send_json_error(['message' => __('Package not found', 'lmb-core')]);
            }

            $result = wp_delete_post($package_id, true);
            
            if (!$result) {
                wp_send_json_error(['message' => __('Failed to delete package', 'lmb-core')]);
            }

            // Log the action
            if (class_exists('LMB_Ad_Manager')) {
                LMB_Ad_Manager::log_activity(sprintf(
                    'Package "%s" deleted by admin %s',
                    $package->post_title,
                    wp_get_current_user()->display_name
                ));
            }

            wp_send_json_success();
        });
        add_action('wp_ajax_lmb_generate_invoice_pdf', function() {
            check_ajax_referer('lmb_invoice_nonce', 'nonce');
            
            if (!is_user_logged_in()) {
                wp_send_json_error(['message' => 'Access denied']);
            }
            
            $payment_id = intval($_POST['payment_id']);
            $user_id = get_current_user_id();
            
            // Verify payment belongs to current user
            $payment_user_id = get_post_meta($payment_id, 'user_id', true);
            if ($payment_user_id != $user_id) {
                wp_send_json_error(['message' => 'Access denied']);
            }
            
            $payment = get_post($payment_id);
            if (!$payment || $payment->post_type !== 'lmb_payment') {
                wp_send_json_error(['message' => 'Payment not found']);
            }
            
            $package_id = get_post_meta($payment_id, 'package_id', true);
            $package = get_post($package_id);
            $package_price = get_post_meta($package_id, 'price', true);
            $payment_reference = get_post_meta($payment_id, 'payment_reference', true);
            
            // Generate invoice PDF
            try {
                if (class_exists('LMB_Invoice_Handler')) {
                    $pdf_url = LMB_Invoice_Handler::create_package_invoice(
                        $user_id,
                        $package_id,
                        $package_price,
                        $package ? $package->post_content : '',
                        $payment_reference ?: 'INV-' . $payment_id
                    );
                } else {
                    wp_send_json_error(['message' => 'Invoice handler not available']);
                }
                
                if ($pdf_url) {
                    wp_send_json_success(['pdf_url' => $pdf_url]);
                } else {
                    wp_send_json_error(['message' => 'Failed to generate PDF']);
                }
            } catch (Exception $e) {
                wp_send_json_error(['message' => $e->getMessage()]);
            }
        });
    }

    public function __construct() {
        add_action('wp_ajax_lmb_upload_accuse', [$this, 'handle_upload_accuse']);
        add_action('wp_ajax_lmb_generate_receipt_pdf', [$this, 'handle_generate_receipt_pdf']);
    }

    public function handle_upload_accuse() {
        check_ajax_referer('lmb_upload_accuse_nonce', '_wpnonce');

        if (!current_user_can('manage_options') || !isset($_POST['legal_ad_id']) || empty($_FILES['accuse_file']['name'])) {
            wp_send_json_error(['message' => __('Missing required information.', 'lmb-core')]);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $legal_ad_id = intval($_POST['legal_ad_id']);
        $accuse_date = sanitize_text_field($_POST['accuse_date']);
        $notes = sanitize_textarea_field($_POST['accuse_notes']);
        $file = $_FILES['accuse_file'];

        $legal_ad = get_post($legal_ad_id);
        if (!$legal_ad || $legal_ad->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => __('Invalid legal ad selected.', 'lmb-core')]);
        }

        $filetype = wp_check_filetype($file['name']);
        if (!in_array($filetype['ext'], ['pdf', 'jpg', 'jpeg', 'png'])) {
            wp_send_json_error(['message' => __('Invalid file type. Please upload a PDF, JPG, or PNG file.', 'lmb-core')]);
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            wp_send_json_error(['message' => __('File too large. Maximum size is 10MB.', 'lmb-core')]);
        }

        // Upload the file
        $attachment_id = media_handle_upload('accuse_file', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        // Save metadata
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $legal_ad_id);
        update_post_meta($attachment_id, 'lmb_accuse_date', $accuse_date);
        update_post_meta($attachment_id, 'lmb_accuse_notes', $notes);

        wp_send_json_success(['message' => __('Accuse uploaded and saved successfully.', 'lmb-core')]);
    }
    public static function get_balance_history() {
        check_ajax_referer('lmb_balance_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid user ID'], 400);
        }

        if (!class_exists('LMB_Points') || !method_exists('LMB_Points', 'get_transactions')) {
            wp_send_json_error(['message' => 'Points system unavailable'], 500);
        }

        $transactions = LMB_Points::get_transactions($user_id, 10);
        $history = [];

        foreach ($transactions as $t) {
            $history[] = [
                'date'   => isset($t['date']) ? $t['date'] : '',
                'type'   => isset($t['type']) ? $t['type'] : '',
                'amount' => isset($t['points']) ? intval($t['points']) : 0,
                'note'   => isset($t['note']) ? $t['note'] : '',
            ];
        }

        wp_send_json_success(['history' => $history]);
    }

    public static function load_admin_tab() {
        check_ajax_referer('lmb_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $tab = isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';
        $content = '';
        $pending_ads_count = 0;
        $pending_payments_count = 0;

        switch ($tab) {
            case 'feed':
                $content = self::render_activity_feed();
                break;

            case 'actions':
                $content = self::render_quick_actions();
                break;

            case 'pending-ads':
                $res = self::render_pending_ads();
                $content = $res['content'];
                $pending_ads_count = $res['count'];
                break;

            case 'pending-payments':
                $res = self::render_pending_payments();
                $content = $res['content'];
                $pending_payments_count = $res['count'];
                break;

            default:
                $content = '<p>' . esc_html__('Invalid tab', 'lmb-core') . '</p>';
        }

        wp_send_json_success([
            'content' => $content,
            'pending_ads_count' => $pending_ads_count,
            'pending_payments_count' => $pending_payments_count,
        ]);
    }

    private static function render_activity_feed() {
        $activity_log = get_option('lmb_activity_log', []);

        if (empty($activity_log)) {
            return '<div class="lmb-feed-empty"><i class="fas fa-stream"></i><p>' .
                esc_html__('No recent activity.', 'lmb-core') . '</p></div>';
        }

        $content = '<div class="lmb-activity-feed">';
        foreach (array_slice($activity_log, 0, 10) as $entry) {
            $user = isset($entry['user']) ? get_userdata($entry['user']) : null;
            $user_name = $user ? $user->display_name : esc_html__('Unknown User', 'lmb-core');

            $content .= '<div class="lmb-feed-item">';
            $content .= '  <div class="lmb-feed-content">';
            $content .= '    <div class="lmb-feed-title">' . esc_html($entry['msg'] ?? '') . '</div>';
            $content .= '    <div class="lmb-feed-meta">';
            $content .= '      <i class="fas fa-user"></i> ' . esc_html($user_name);
            if (!empty($entry['time'])) {
                $content .= ' • <i class="fas fa-clock"></i> ' . esc_html(human_time_diff(strtotime($entry['time'])) . ' ' . __('ago', 'lmb-core'));
            }
            $content .= '    </div>';
            $content .= '  </div>';
            $content .= '</div>';
        }
        $content .= '</div>';

        return $content;
    }

    private static function render_quick_actions() {
        $actions = [
            [
                'title' => __('Upload New Newspaper', 'lmb-core'),
                'icon'  => 'fas fa-plus-circle',
                'url'   => admin_url('post-new.php?post_type=lmb_newspaper'),
                'description' => __('Add a new newspaper edition', 'lmb-core'),
            ],
            [
                'title' => __('Manage Legal Ads', 'lmb-core'),
                'icon'  => 'fas fa-gavel',
                'url'   => admin_url('edit.php?post_type=lmb_legal_ad'),
                'description' => __('Review and manage legal ads', 'lmb-core'),
            ],
            [
                'title' => __('Review Payments', 'lmb-core'),
                'icon'  => 'fas fa-credit-card',
                'url'   => admin_url('edit.php?post_type=lmb_payment'),
                'description' => __('Verify or deny user payment proofs', 'lmb-core'),
            ],
            [
                'title' => __('Invoices', 'lmb-core'),
                'icon'  => 'fas fa-file-invoice',
                'url'   => admin_url('edit.php?post_type=lmb_invoice'),
                'description' => __('Browse and manage invoices', 'lmb-core'),
            ],
        ];

        $out  = '<div class="lmb-actions-grid">';
        foreach ($actions as $a) {
            $out .= '<div class="lmb-action-card">';
            $out .= '  <a class="lmb-action-link" href="' . esc_url($a['url']) . '">';
            $out .= '    <div class="lmb-action-icon"><i class="' . esc_attr($a['icon']) . '"></i></div>';
            $out .= '    <div class="lmb-action-title">' . esc_html($a['title']) . '</div>';
            $out .= '    <div class="lmb-action-desc">' . esc_html($a['description']) . '</div>';
            $out .= '  </a>';
            $out .= '</div>';
        }
        $out .= '</div>';

        return $out;
    }

    private static function render_pending_ads() {
        $pending_ads = get_posts([
            'post_type'      => 'lmb_legal_ad',
            'post_status'    => 'any',
            'posts_per_page' => 10,
            'meta_query'     => [
                [
                    'key'     => 'lmb_status',
                    'value'   => 'pending_review',
                    'compare' => '=',
                ],
            ],
        ]);

        $count = is_array($pending_ads) ? count($pending_ads) : 0;

        if (empty($pending_ads)) {
            return [
                'content' => '<div class="lmb-feed-empty"><i class="fas fa-clipboard-check"></i><p>' .
                    esc_html__('No legal ads are pending approval.', 'lmb-core') . '</p></div>',
                'count' => 0,
            ];
        }

        $out  = '<div class="lmb-pending-ads-feed">';
        foreach ($pending_ads as $post) {
            $title = get_the_title($post);
            $user_id = get_post_meta($post->ID, 'user_id', true);
            $user = $user_id ? get_userdata($user_id) : null;

            $out .= '<div class="lmb-feed-item">';
            $out .= '  <div class="lmb-feed-content">';
            $out .= '    <div class="lmb-feed-title">' . esc_html($title) . '</div>';
            $out .= '    <div class="lmb-feed-meta">';
            if ($user) {
                $out .= '      <i class="fas fa-user"></i> ' . esc_html($user->display_name) . ' • ';
            }
            $out .= '      <i class="fas fa-clock"></i> ' . esc_html(get_the_date('', $post));
            $out .= '    </div>';
            $out .= '    <div class="lmb-actions">';
            $out .= '      <button class="lmb-btn lmb-approve lmb-ad-action" data-action="approve" data-id="' . intval($post->ID) . '">' . esc_html__('Approve', 'lmb-core') . '</button>';
            $out .= '      <button class="lmb-btn lmb-deny lmb-ad-action" data-action="deny" data-id="' . intval($post->ID) . '">' . esc_html__('Deny', 'lmb-core') . '</button>';
            $out .= '    </div>';
            $out .= '  </div>';
            $out .= '</div>';
        }
        $out .= '</div>';

        return ['content' => $out, 'count' => $count];
    }

    private static function render_pending_payments() {
        $pending = get_posts([
            'post_type'      => 'lmb_payment',
            'post_status'    => 'any',
            'posts_per_page' => 10,
            'meta_query'     => [
                [
                    'key'     => 'payment_status',
                    'value'   => 'pending',
                    'compare' => '=',
                ],
            ],
        ]);

        $count = is_array($pending) ? count($pending) : 0;

        if (empty($pending)) {
            return [
                'content' => '<div class="lmb-feed-empty"><i class="fas fa-receipt"></i><p>' .
                    esc_html__('No payments are pending verification.', 'lmb-core') . '</p></div>',
                'count' => 0,
            ];
        }

        $out  = '<div class="lmb-pending-payments-feed">';
        foreach ($pending as $payment) {
            $user_id  = get_post_meta($payment->ID, 'user_id', true);
            $user     = $user_id ? get_userdata($user_id) : null;
            $reference = get_post_meta($payment->ID, 'payment_reference', true);

            $out .= '<div class="lmb-feed-item">';
            $out .= '  <div class="lmb-feed-content">';
            $out .= '    <div class="lmb-feed-title">' . esc_html(get_the_title($payment)) . '</div>';
            $out .= '    <div class="lmb-feed-meta">';
            if ($user) {
                $out .= '      <i class="fas fa-user"></i> ' . esc_html($user->display_name) . ' • ';
            }
            $out .= '      <i class="fas fa-hashtag"></i> ' . esc_html($reference);
            $out .= '    </div>';
            $out .= '    <div class="lmb-actions">';
            $out .= '      <button class="lmb-btn lmb-approve lmb-payment-action" data-action="approve" data-id="' . intval($payment->ID) . '">' . esc_html__('Approve', 'lmb-core') . '</button>';
            $out .= '      <button class="lmb-btn lmb-deny lmb-payment-action" data-action="deny" data-id="' . intval($payment->ID) . '">' . esc_html__('Deny', 'lmb-core') . '</button>';
            $out .= '    </div>';
            $out .= '  </div>';
            $out .= '</div>';
        }
        $out .= '</div>';

        return ['content' => $out, 'count' => $count];
    }

    public static function search_user() {
        check_ajax_referer('lmb_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if ($term === '') {
            wp_send_json_success(['results' => []]);
        }

        // Try by ID
        if (is_numeric($term)) {
            $u = get_user_by('ID', (int) $term);
            if ($u) {
                wp_send_json_success(['results' => [[
                    'id'   => $u->ID,
                    'text' => $u->display_name . ' (' . $u->user_email . ')',
                ]]]);
            }
        }

        // By email or login or display_name
        $args = [
            'search'         => '*' . esc_attr($term) . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number'         => 10,
        ];
        $users = get_users($args);
        $results = [];
        foreach ($users as $u) {
            $results[] = [
                'id'   => $u->ID,
                'text' => $u->display_name . ' (' . $u->user_email . ')',
            ];
        }

        wp_send_json_success(['results' => $results]);
    }

    public static function update_balance() {
        check_ajax_referer('lmb_admin_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount  = isset($_POST['amount']) ? intval($_POST['amount']) : 0;
        $reason  = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        $type    = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'add';

        if (!$user_id || !$amount) {
            wp_send_json_error(['message' => 'Missing user or amount'], 400);
        }

        if (!class_exists('LMB_Points')) {
            wp_send_json_error(['message' => 'Points system unavailable'], 500);
        }

        if ($type === 'remove') {
            $amount = -abs($amount);
        }

        $ok = LMB_Points::add_points($user_id, $amount, $reason ?: 'Manual update');
        if (!$ok) {
            wp_send_json_error(['message' => 'Failed to update balance'], 500);
        }

        wp_send_json_success(['message' => 'Balance updated']);
    }

    public function handle_generate_receipt_pdf() {
        check_ajax_referer('lmb_receipt_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Access denied', 'lmb-core')]);
        }

        $ad_id = intval($_POST['ad_id']);
        $ad_type = sanitize_text_field($_POST['ad_type']);
        $user_id = get_current_user_id();

        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != $user_id) {
            wp_send_json_error(['message' => __('Ad not found or access denied', 'lmb-core')]);
        }

        try {
            $pdf_url = LMB_Receipt_Generator::create_receipt_pdf($ad_id, $ad_type);
            if ($pdf_url) {
                wp_send_json_success(['pdf_url' => $pdf_url]);
            } else {
                wp_send_json_error(['message' => __('Failed to generate PDF', 'lmb-core')]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
}
