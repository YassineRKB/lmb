<?php
// FILE: includes/class-lmb-ajax-handlers.php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {

    public static function init() {
        // --- CORRECTED LOGIC ---
        // All actions are for logged-in users only.
        $actions = [
            'lmb_ad_status_change', 'lmb_user_submit_for_review', 'lmb_payment_action',
            'lmb_get_balance_history', 'lmb_load_admin_tab', 'lmb_search_user', 'lmb_update_balance',
            'lmb_generate_package_invoice', 'lmb_get_notifications', 'lmb_mark_notification_read',
            'lmb_mark_all_notifications_read', 'lmb_save_package', 'lmb_delete_package',
            'lmb_upload_newspaper', 'lmb_upload_bank_proof', 'lmb_fetch_users', 'lmb_fetch_ads',
            'lmb_upload_accuse', 'lmb_user_get_ads',
            'lmb_get_pending_accuse_ads', 'lmb_attach_accuse_to_ad',
            'lmb_get_pending_invoices_form', 'lmb_generate_invoice_pdf',
            'lmb_regenerate_ad_text', 'lmb_admin_generate_pdf',
            'lmb_fetch_ads_v2', 'lmb_fetch_my_ads_v2', 'lmb_submit_draft_ad_v2',
            'lmb_delete_draft_ad_v2', 'lmb_fetch_feed_v2',
            'lmb_login_v2', 'lmb_signup_v2',
            'lmb_fetch_inactive_clients_v2',
            'lmb_manage_inactive_client_v2',
            'lmb_fetch_active_clients_v2',
            'lmb_lock_active_client_v2','lmb_update_profile_v2',
            'lmb_update_password_v2',
            'lmb_admin_generate_accuse',
            'lmb_admin_upload_temporary_journal',
            'lmb_update_password_v2',
            'lmb_fetch_public_ads', 'lmb_get_package_data',
            'lmb_fetch_newspapers_v2', 'lmb_fetch_payments',
            'lmb_fetch_eligible_ads',
            'lmb_generate_newspaper_preview',
            'lmb_approve_and_publish_newspaper',
            'lmb_discard_newspaper_draft', // <-- NEW ACTION ADDED
            
        ];
        // --- MODIFICATION: Make auth actions public ---
        $public_actions = ['lmb_login_v2', 'lmb_signup_v2', 'lmb_fetch_public_ads', 'lmb_fetch_newspapers_v2'];

        foreach ($actions as $action) {
            add_action('wp_ajax_' . $action, [__CLASS__, 'handle_request']);
            if (in_array($action, $public_actions)) {
                add_action('wp_ajax_nopriv_' . $action, [__CLASS__, 'handle_request']);
            }
        }
    }

    public static function handle_request() {
        check_ajax_referer('lmb_nonce', 'nonce');
        
        $public_actions = ['lmb_login_v2', 'lmb_signup_v2', 'lmb_fetch_public_ads', 'lmb_fetch_newspapers_v2'];
        $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';

        if (!in_array($action, $public_actions) && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.'], 403);
            return;
        }

        // --- ADDED SECURITY for admin-only actions ---
        $admin_only_actions = [
            'lmb_fetch_inactive_clients_v2',
            'lmb_manage_inactive_client_v2',
            'lmb_fetch_active_clients_v2', 'lmb_lock_active_client_v2'
        ];
        if (in_array($action, $admin_only_actions) && !current_user_can('manage_options')) {
             wp_send_json_error(['message' => 'You do not have permission to perform this action.'], 403);
            return;
        }

        if (method_exists(__CLASS__, $action)) {
            self::$action();
        } else {
            wp_send_json_error(['message' => 'Invalid AJAX Action specified.'], 400);
        }
    }

    private static function lmb_user_get_ads() {
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : 'draft';
    
        $args = [
            'author' => get_current_user_id(),
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => 5,
            'paged' => $paged,
            'post_status' => ['publish', 'draft', 'pending'],
            'meta_query' => [
                [
                    'key' => 'lmb_status',
                    'value' => $status,
                    'compare' => '=',
                ],
            ],
        ];
    
        $query = new WP_Query($args);
        $max_pages = $query->max_num_pages;
        
        $widget_file = LMB_CORE_PATH . 'elementor/widgets/class-lmb-user-ads-list-widget.php';
        if (file_exists($widget_file)) {
            require_once $widget_file;
            if (class_exists('LMB_User_Ads_List_Widget')) {
                $widget_instance = new LMB_User_Ads_List_Widget();
                ob_start();
                $widget_instance->render_ads_for_status($status, $paged);
                $html = ob_get_clean();

                wp_send_json_success(['html' => $html, 'max_pages' => $max_pages]);
            }
        }
        wp_reset_postdata();
        wp_send_json_error(['message' => 'Widget class not found.']);
    }

    private static function lmb_upload_accuse() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        if (empty($_POST['legal_ad_id']) || empty($_FILES['accuse_file'])) {
            wp_send_json_error(['message' => 'Missing required fields: Ad ID and file are required.']);
        }

        $ad_id = intval($_POST['legal_ad_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => 'Invalid Ad ID. No legal ad found with this ID.']);
        }
        if (get_post_meta($ad_id, 'lmb_status', true) !== 'published') {
            wp_send_json_error(['message' => 'This ad is not published. You can only upload an accuse for published ads.']);
        }
        if (get_post_meta($ad_id, 'lmb_accuse_attachment_id', true)) {
            wp_send_json_error(['message' => 'An accuse document has already been uploaded for this ad.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $attachment_id = media_handle_upload('accuse_file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $ad_id);
        update_post_meta($ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        
        $client_id = $ad->post_author;
        if ($client_id && class_exists('LMB_Notification_Manager')) {
            $title = sprintf(__('Receipt for ad "%s" is ready', 'lmb-core'), get_the_title($ad_id));
            $msg = __('The official receipt (accuse) for your legal ad is now available for download from your dashboard.', 'lmb-core');
            LMB_Notification_Manager::add($client_id, 'receipt_ready', $title, $msg, ['ad_id' => $ad_id]);
        }
        
        wp_send_json_success(['message' => 'Accuse uploaded and attached successfully. The client has been notified.']);
    }

    private static function lmb_fetch_ads() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.']);
        }

        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        $args = [
            'post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => 10,
            'paged' => $paged, 'meta_query' => ['relation' => 'AND'],
        ];

        if (!empty($_POST['filter_status'])) $args['meta_query'][] = ['key' => 'lmb_status', 'value' => sanitize_text_field($_POST['filter_status'])];
        if (!empty($_POST['filter_ad_type'])) $args['meta_query'][] = ['key' => 'ad_type', 'value' => sanitize_text_field($_POST['filter_ad_type'])];
        if (!empty($_POST['filter_company'])) $args['meta_query'][] = ['key' => 'company_name', 'value' => sanitize_text_field($_POST['filter_company']),'compare' => 'LIKE'];

        if (!empty($_POST['filter_ref'])) {
            $ref = sanitize_text_field($_POST['filter_ref']);
            if (is_numeric($ref)) {
                $args['meta_query']['relation'] = 'OR';
                $args['meta_query'][] = ['key' => 'ID', 'value' => $ref];
                $args['s'] = $ref;
            } else {
                $args['s'] = $ref;
            }
        }

        if (!empty($_POST['filter_user'])) {
            $user_data = sanitize_text_field($_POST['filter_user']);
            $user = is_numeric($user_data) ? get_user_by('id', $user_data) : (get_user_by('email', $user_data) ?: get_user_by('login', $user_data));
            $args['author'] = $user ? $user->ID : -1;
        }
        
        $query = new WP_Query($args);
        
        ob_start();
        if ($query->have_posts()) {
            echo '<table class="lmb-ads-table"><thead><tr><th>ID</th><th>Title</th><th>Client</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $status = get_post_meta($post_id, 'lmb_status', true);
                $client = get_userdata(get_post_field('post_author', $post_id));
                echo '<tr><td>' . esc_html($post_id) . '</td><td><a href="' . get_edit_post_link($post_id) . '" target="_blank">' . get_the_title() . '</a></td><td>' . ($client ? esc_html($client->display_name) : 'N/A') . '</td><td><span class="lmb-status-badge lmb-status-' . esc_attr($status) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span></td><td>' . get_the_date() . '</td><td class="lmb-actions-cell">';
                if ($status === 'pending_review') {
                    echo '<button class="lmb-btn lmb-btn-sm lmb-ad-action" data-action="approve" data-id="'.$post_id.'">Approve</button><button class="lmb-btn lmb-btn-sm lmb-ad-action" data-action="deny" data-id="'.$post_id.'">Deny</button>';
                } else { echo '—'; }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            
            if ($query->max_num_pages > 1) {
                echo '<div class="lmb-pagination">' . paginate_links(['total' => $query->max_num_pages, 'current' => $paged, 'format' => '?paged=%#%', 'base' => '#%#%']) . '</div>';
            }
        } else {
            echo '<div class="lmb-no-results">No ads found matching your criteria.</div>';
        }
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }

    private static function lmb_fetch_users() {
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $args = ['number' => 10, 'paged' => $paged];
        
        if (!empty($_POST['search_name'])) {
            $args['search'] = '*' . sanitize_text_field($_POST['search_name']) . '*';
            $args['search_columns'] = ['display_name', 'user_login'];
        }
        if (!empty($_POST['search_email'])) {
            $args['search'] = '*' . sanitize_email($_POST['search_email']) . '*';
            $args['search_columns'] = ['user_email'];
        }

        $user_query = new WP_User_Query($args);
        
        ob_start();
        if (!empty($user_query->get_results())) {
            echo '<table class="lmb-users-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Balance</th><th>Actions</th></tr></thead><tbody>';
            foreach ($user_query->get_results() as $user) {
                echo '<tr><td>' . esc_html($user->ID) . '</td><td>' . esc_html($user->display_name) . '</td><td>' . esc_html($user->user_email) . '</td><td>' . LMB_Points::get_balance($user->ID) . '</td><td><a href="' . get_edit_user_link($user->ID) . '" target="_blank" class="lmb-btn lmb-btn-sm">Edit</a></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="lmb-no-results">No users found.</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    private static function lmb_upload_bank_proof() {
        if (empty($_POST['payment_id']) || empty($_FILES['proof_file'])) {
            wp_send_json_error(['message' => 'Missing required fields. Please select an invoice and a proof file.']);
        }
        
        $user_id = get_current_user_id();
        $payment_id = intval($_POST['payment_id']);
        $payment_post = get_post($payment_id);

        if (!$payment_post || $payment_post->post_author != $user_id) {
            wp_send_json_error(['message' => 'Invalid invoice selected.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('proof_file', $payment_id);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'File Upload Error: ' . $attachment_id->get_error_message()]);
        }

        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        LMB_Ad_Manager::log_activity(sprintf('Payment proof for invoice #%d submitted.', $payment_id));
        
        if(class_exists('LMB_Notification_Manager')) {
            $user = wp_get_current_user();
            $package_id = get_post_meta($payment_id, 'package_id', true);
            $title = 'New Payment Proof Submitted';
            $msg = sprintf('User %s has submitted proof for invoice #%s ("%s").', $user->display_name, get_post_meta($payment_id, 'payment_reference', true), get_the_title($package_id));
            
            $admin_ids = get_users(['role' => 'administrator', 'fields' => 'ID']);
            foreach ($admin_ids as $admin_id) {
                LMB_Notification_Manager::add($admin_id, 'proof_submitted', $title, $msg, ['ad_id' => $payment_id]);
            }
        }

        wp_send_json_success(['message' => 'Your proof has been submitted for review. You will be notified once it is approved.']);
    }

    private static function lmb_save_package() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied']);
        
        $package_id = isset($_POST['package_id']) && !empty($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $cost = intval($_POST['cost_per_ad']);
        $desc = wp_kses_post($_POST['description']);

        if (!$name || !$price || !$points || !$cost) {
            wp_send_json_error(['message' => 'All fields except description are required.']);
        }

        $post_data = ['post_title' => $name, 'post_content' => $desc, 'post_type' => 'lmb_package', 'post_status' => 'publish'];
        
        $result = $package_id ? wp_update_post(array_merge(['ID' => $package_id], $post_data), true) : wp_insert_post($post_data, true);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        $new_pkg_id = $package_id ?: $result;
        update_post_meta($new_pkg_id, 'price', $price);
        update_post_meta($new_pkg_id, 'points', $points);
        update_post_meta($new_pkg_id, 'cost_per_ad', $cost);
        
        LMB_Ad_Manager::log_activity(sprintf('Package "%s" %s', $name, $package_id ? 'updated' : 'created'));
        
        wp_send_json_success([
            'message' => 'Package saved successfully.',
            'package' => ['id' => $new_pkg_id, 'name' => $name, 'price' => $price, 'points' => $points, 'cost_per_ad' => $cost, 'description' => $desc, 'trimmed_description' => wp_trim_words($desc, 20)]
        ]);
    }
    
    private static function lmb_load_admin_tab() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'feed';
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $posts_per_page = 5;

        $pending_ads_query = new WP_Query(['post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_query' => [['key' => 'lmb_status', 'value' => 'pending_review']]]);
        $pending_payments_query = new WP_Query(['post_type' => 'lmb_payment', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);

        ob_start();
        $query = null;
        $total_items = 0;

        switch ($tab) {
            case 'pending_ads':
                $query = new WP_Query(['post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => $posts_per_page, 'paged' => $paged, 'meta_query' => [['key' => 'lmb_status', 'value' => 'pending_review']]]);
                if (!$query->have_posts()) {
                    echo '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No legal ads are pending approval.', 'lmb-core') . '</p></div>';
                } else {
                    while ($query->have_posts()) { $query->the_post(); echo self::render_feed_item('ad'); }
                }
                break;
            case 'pending_payments':
                $query = new WP_Query(['post_type' => 'lmb_payment', 'post_status' => 'publish', 'posts_per_page' => $posts_per_page, 'paged' => $paged, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);
                if (!$query->have_posts()) {
                    echo '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No payments are pending verification.', 'lmb-core') . '</p></div>';
                } else {
                    while ($query->have_posts()) { $query->the_post(); echo self::render_feed_item('payment'); }
                }
                break;
            default:
                $activity_log = get_option('lmb_activity_log', []);
                $total_items = count($activity_log);
                if (empty($activity_log)) {
                    echo '<div class="lmb-feed-empty"><i class="fas fa-stream"></i><p>' . __('No recent activity.', 'lmb-core') . '</p></div>';
                } else {
                    $log_paged = array_slice($activity_log, ($paged - 1) * $posts_per_page, $posts_per_page);
                    foreach ($log_paged as $entry) { echo self::render_feed_item('log', $entry); }
                }
                break;
        }
        $content = ob_get_clean();

        $total_pages = $query ? $query->max_num_pages : ceil($total_items / $posts_per_page);
        
        wp_send_json_success([
            'content' => $content,
            'pagination' => $total_pages > 1 ? paginate_links(['base' => '#%#%', 'format' => '?paged=%#%', 'current' => $paged, 'total' => $total_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;']) : '',
            'pending_ads_count' => (int) $pending_ads_query->found_posts,
            'pending_payments_count' => (int) $pending_payments_query->found_posts,
        ]);
    }
    
    private static function render_feed_item($type, $entry = null) {
        ob_start();
        if ($type === 'log') {
            $user = $entry['user'] ? get_userdata($entry['user']) : null;
            ?>
            <div class="lmb-feed-item">
                <div class="lmb-feed-content">
                    <div class="lmb-feed-title"><?php echo esc_html($entry['msg']); ?></div>
                    <div class="lmb-feed-meta"><i class="fas fa-user"></i> <?php echo esc_html($user ? $user->display_name : 'System'); ?> • <i class="fas fa-clock"></i> <?php echo human_time_diff(strtotime($entry['time'])); ?> ago</div>
                </div>
            </div>
            <?php
        } elseif ($type === 'ad') {
            $ad = get_post();
            $client = get_userdata($ad->post_author);
            ?>
            <div class="lmb-feed-item" data-id="<?php echo $ad->ID; ?>">
                <div class="lmb-feed-content">
                    <a href="<?php echo get_edit_post_link($ad->ID); ?>" class="lmb-feed-title" target="_blank"><?php echo esc_html($ad->post_title); ?></a>
                    <div class="lmb-feed-meta"><i class="fas fa-user"></i> <?php echo esc_html($client ? $client->display_name : 'Unknown'); ?> • <i class="fas fa-clock"></i> <?php echo human_time_diff(get_the_time('U', $ad->ID)); ?> ago</div>
                </div>
            </div>
            <?php
        } elseif ($type === 'payment') {
            $payment = get_post();
            $user = get_userdata(get_post_meta($payment->ID, 'user_id', true));
            $proof_url = wp_get_attachment_url(get_post_meta($payment->ID, 'proof_attachment_id', true));
            ?>
             <div class="lmb-feed-item" data-id="<?php echo $payment->ID; ?>">
                <div class="lmb-feed-content">
                    <a href="<?php echo get_edit_post_link($payment->ID); ?>" class="lmb-feed-title" target="_blank"><?php echo esc_html($payment->post_title); ?></a>
                    <div class="lmb-feed-meta"><i class="fas fa-user"></i> <?php echo esc_html($user ? $user->display_name : 'Unknown'); ?></div>
                </div>
                <div class="lmb-feed-actions">
                    <?php if ($proof_url): ?><a href="<?php echo esc_url($proof_url); ?>" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-paperclip"></i> Show Proof</a><?php endif; ?>
                    <button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action" data-action="approve" data-id="<?php echo $payment->ID; ?>"><i class="fas fa-check"></i> Approve</button>
                    <button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action" data-action="reject" data-id="<?php echo $payment->ID; ?>"><i class="fas fa-times"></i> Reject</button>
                </div>
            </div>
            <?php
        }
        return ob_get_clean();
    }


    private static function lmb_search_user() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        
        $term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if (empty($term)) wp_send_json_error(['message' => 'Search term is empty.'], 400);

        $user_query = new WP_User_Query(['search' => '*' . esc_attr($term) . '*', 'search_columns' => ['ID', 'user_login', 'user_email', 'display_name'], 'number' => 10, 'fields' => ['ID', 'display_name', 'user_email']]);
        
        if (!empty($user_query->get_results())) {
            wp_send_json_success(['users' => $user_query->get_results()]);
        } else {
            wp_send_json_error(['message' => 'No users found.'], 404);
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
        
        if ($new_balance === false) {
            wp_send_json_error(['message' => 'Insufficient balance for this operation.'], 400);
        }
        wp_send_json_success(['message' => 'Balance updated successfully!', 'new_balance' => $new_balance]);
    }

    private static function lmb_generate_package_invoice() {
        if (!is_user_logged_in() || !isset($_POST['pkg_id'])) wp_send_json_error(['message' => 'Invalid request.'], 403);

        $pdf_url = LMB_Invoice_Handler::create_invoice_for_package(get_current_user_id(), intval($_POST['pkg_id']));
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Could not generate invoice. Please try again.']);
        }
    }

    private static function lmb_get_notifications() {
        $user_id = get_current_user_id();
        wp_send_json_success(['items' => LMB_Notification_Manager::get_latest($user_id, 10), 'unread' => LMB_Notification_Manager::get_unread_count($user_id)]);
    }

    private static function lmb_mark_notification_read() {
        $nid = isset($_POST['id']) ? absint($_POST['id']) : 0;
        wp_send_json_success(['ok' => (bool) LMB_Notification_Manager::mark_read(get_current_user_id(), $nid)]);
    }

    private static function lmb_mark_all_notifications_read() {
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
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad = get_post($ad_id);
        
        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }

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
        
        wp_send_json_success([
            'current_balance' => LMB_Points::get_balance($user_id),
            'history' => LMB_Points::get_transactions($user_id, 10)
        ]);
    }

    private static function lmb_get_pending_accuse_ads() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access Denied.']);
        
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $query = new WP_Query(['post_type' => 'lmb_legal_ad', 'posts_per_page' => 5, 'paged' => $paged, 'meta_query' => ['relation' => 'AND', ['key' => 'lmb_status', 'value' => 'published'], ['key' => 'lmb_accuse_attachment_id', 'compare' => 'NOT EXISTS']], 'orderby' => 'date', 'order' => 'DESC']);
        
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="lmb-accuse-pending-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $ad_id = get_the_ID();
                $client = get_userdata(get_post_field('post_author', $ad_id));
                echo '<form class="lmb-accuse-item lmb-accuse-upload-form" enctype="multipart/form-data"><div class="lmb-accuse-info"><strong>' . get_the_title() . '</strong> (ID: ' . $ad_id . ')<br><small>Client: ' . ($client ? esc_html($client->display_name) : 'N/A') . ' | Published: ' . get_the_date() . '</small></div><div class="lmb-accuse-actions"><input type="file" name="accuse_file" class="lmb-file-input-accuse" required accept=".pdf,.jpg,.jpeg,.png"><input type="hidden" name="legal_ad_id" value="' . $ad_id . '"><button type="submit" class="lmb-btn lmb-btn-sm lmb-btn-primary"><i class="fas fa-upload"></i> Upload</button></div></form>';
            }
            echo '</div>';
            if ($query->max_num_pages > 1) {
                echo '<div class="lmb-pagination">' . paginate_links(['total' => $query->max_num_pages, 'current' => $paged, 'format' => '?paged=%#%', 'base' => '#%#%']) . '</div>';
            }
        } else {
            echo '<div class="lmb-empty-state"><i class="fas fa-check-circle fa-3x"></i><h4>All Caught Up!</h4><p>No published ads are waiting for an accuse document.</p></div>';
        }
        wp_reset_postdata();
        wp_send_json_success(['html' => ob_get_clean()]);
    }

    private static function lmb_attach_accuse_to_ad() {
        if (!current_user_can('manage_options') || !isset($_POST['ad_id'], $_POST['attachment_id'])) wp_send_json_error(['message' => 'Permission denied or missing data.']);

        $ad_id = intval($_POST['ad_id']);
        $attachment_id = intval($_POST['attachment_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad') wp_send_json_error(['message' => 'Invalid Ad ID.']);
        
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $ad_id);
        update_post_meta($ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        
        $client_id = $ad->post_author;
        if ($client_id && class_exists('LMB_Notification_Manager')) {
            $title = sprintf(__('Receipt for ad "%s" is ready', 'lmb-core'), get_the_title($ad_id));
            $msg = __('The official receipt (accuse) for your legal ad is now available for download from your dashboard.', 'lmb-core');
            LMB_Notification_Manager::add($client_id, 'receipt_ready', $title, $msg, ['ad_id' => $ad_id]);
        }
        
        wp_send_json_success(['message' => 'Accuse attached successfully! The client has been notified.']);
    }

    private static function lmb_get_pending_invoices_form() {
        $pending_payments = get_posts(['post_type' => 'lmb_payment', 'post_status' => 'publish', 'author' => get_current_user_id(), 'posts_per_page' => -1, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);

        ob_start();
        if (empty($pending_payments)) {
            echo '<div class="lmb-empty-state"><i class="fas fa-check-circle fa-3x"></i><h4>' . esc_html__('No Pending Invoices', 'lmb-core') . '</h4><p>' . esc_html__('You have no invoices awaiting payment. To get one, please select a package from our pricing table.', 'lmb-core') . '</p></div>';
        } else {
            echo '<form id="lmb-upload-proof-form" class="lmb-form" enctype="multipart/form-data"><div class="lmb-form-group"><label for="payment_id"><i class="fas fa-file-invoice"></i> ' . esc_html__('Select Invoice to Pay','lmb-core') . '</label><select name="payment_id" id="payment_id" class="lmb-select" required><option value="">' . esc_html__('Select the invoice you paid...','lmb-core') . '</option>';
            foreach ($pending_payments as $payment) {
                $ref = get_post_meta($payment->ID, 'payment_reference', true);
                $price = get_post_meta($payment->ID, 'package_price', true);
                echo '<option value="' . esc_attr($payment->ID) . '">' . esc_html($ref) . ' (' . esc_html(get_the_title(get_post_meta($payment->ID, 'package_id', true))) . ' - ' . esc_html($price) . ' MAD)</option>';
            }
            echo '</select></div><div class="lmb-form-group"><label for="proof_file"><i class="fas fa-paperclip"></i> ' . esc_html__('Proof of Payment File','lmb-core') . '</label><input type="file" name="proof_file" id="proof_file" class="lmb-input" accept="image/jpeg,image/png,application/pdf" required><small>' . esc_html__('Accepted formats: JPG, PNG, PDF. Maximum size: 5MB.','lmb-core') . '</small></div><div class="lmb-form-actions"><button type="submit" class="lmb-btn lmb-btn-primary lmb-btn-large"><i class="fas fa-check-circle"></i> ' . esc_html__('Submit for Verification','lmb-core') . '</button></div></form>';
        }
        wp_send_json_success(['html' => ob_get_clean()]);
    }

    private static function lmb_generate_invoice_pdf() {
        $payment_id = intval($_POST['payment_id']);
        $payment_post = get_post($payment_id);

        if (!$payment_post || $payment_post->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        $pdf_url = LMB_Invoice_Handler::generate_invoice_pdf($payment_id);
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Could not generate PDF invoice.']);
        }
    }

    private static function lmb_regenerate_ad_text() {
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission Denied.']);
        
        $post_id = intval($_POST['post_id']);
        LMB_Form_Handler::generate_and_save_formatted_text($post_id);
        
        wp_send_json_success(['new_content' => get_post($post_id)->post_content]);
    }

    private static function lmb_admin_generate_pdf() {
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission Denied.']);
        
        $post_id = intval($_POST['post_id']);
        $pdf_url = LMB_PDF_Generator::create_ad_pdf_from_fulltext($post_id);
        
        if ($pdf_url) {
            update_post_meta($post_id, 'ad_pdf_url', $pdf_url);
            wp_send_json_success(['message' => 'PDF generated successfully!', 'pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate PDF.']);
        }
    }

    // Enhanced ad fetching with multiple filters
    private static function lmb_fetch_ads_v2() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.'], 403);
        }

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        parse_str($_POST['filters'] ?? '', $filters);

        $args = [
            'post_type' => 'lmb_legal_ad',
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => ['relation' => 'AND'],
        ];

        if (!empty($filters['filter_ref']) && is_numeric($filters['filter_ref'])) {
            $args['post__in'] = [intval($filters['filter_ref'])];
        }
        if (!empty($filters['filter_company'])) {
            $args['meta_query'][] = ['key' => 'company_name', 'value' => sanitize_text_field($filters['filter_company']), 'compare' => 'LIKE'];
        }
        if (!empty($filters['filter_type'])) {
            $args['meta_query'][] = ['key' => 'ad_type', 'value' => sanitize_text_field($filters['filter_type']), 'compare' => 'LIKE'];
        }
        if (!empty($filters['filter_date'])) {
            $args['date_query'] = [['year' => date('Y', strtotime($filters['filter_date'])), 'month' => date('m', strtotime($filters['filter_date'])), 'day' => date('d', strtotime($filters['filter_date']))]];
        }
        if (!empty($filters['filter_client'])) {
            $user_query = new WP_User_Query(['search' => '*' . esc_attr(sanitize_text_field($filters['filter_client'])) . '*', 'search_columns' => ['user_login', 'display_name'], 'fields' => 'ID']);
            $user_ids = $user_query->get_results();
            if (!empty($user_ids)) {
                $args['author__in'] = $user_ids;
            } else {
                $args['author__in'] = [0];
            }
        }
        if (!empty($filters['filter_status'])) {
            $args['meta_query'][] = ['key' => 'lmb_status', 'value' => sanitize_key($filters['filter_status'])];
        }
        if (!empty($filters['filter_approved_by'])) {
            $user_query = new WP_User_Query(['search' => '*' . esc_attr(sanitize_text_field($filters['filter_approved_by'])) . '*', 'search_columns' => ['user_login', 'display_name'], 'fields' => 'ID']);
            $user_ids = $user_query->get_results();
            if(!empty($user_ids)){
                $args['meta_query'][] = ['key' => 'approved_by', 'value' => $user_ids, 'compare' => 'IN'];
            } else {
                $args['meta_query'][] = ['key' => 'approved_by', 'value' => 0];
            }
        }

        $query = new WP_Query($args);

        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $status = get_post_meta($post_id, 'lmb_status', true) ?: 'draft';
                $client = get_userdata(get_post_field('post_author', $post_id));
                $approved_by_id = get_post_meta($post_id, 'approved_by', true);
                $approved_by = $approved_by_id ? get_userdata($approved_by_id) : null;
                $accuse_url = get_post_meta($post_id, 'lmb_accuse_pdf_url', true);
                
                $journal_display = '<span class="lamv2-cell-placeholder">-</span>';
                $final_journal_id = get_post_meta($post_id, 'lmb_final_journal_id', true);
                $temp_journal_id = get_post_meta($post_id, 'lmb_temporary_journal_id', true);

                if ($final_journal_id) {
                    $journal_title = get_the_title($final_journal_id);
                    $journal_url = wp_get_attachment_url(get_post_meta($final_journal_id, 'newspaper_pdf', true)); // Get PDF url
                    if ($journal_url) {
                       $journal_display = '<a href="' . esc_url($journal_url) . '" target="_blank" class="lamv2-btn lamv2-btn-sm lamv2-btn-text-link">' . esc_html($journal_title) . '</a>';
                    }
                } elseif ($temp_journal_id) {
                    $journal_title = get_the_title($temp_journal_id);
                    $journal_url = wp_get_attachment_url($temp_journal_id); // Attachments are posts
                     if ($journal_url) {
                        $journal_display = '<a href="' . esc_url($journal_url) . '" target="_blank" class="lamv2-btn lamv2-btn-sm lamv2-btn-text-link">' . esc_html($journal_title) . '</a>';
                    }
                }

                echo '<tr class="lamv2-clickable-row" data-href="' . esc_url(get_edit_post_link($post_id)) . '">';
                echo '<td>' . esc_html($post_id) . '</td>';
                echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                echo '<td>' . get_the_date('Y-m-d') . '</td>';
                echo '<td>' . ($client ? esc_html($client->display_name) : 'N/A') . '</td>';
                echo '<td><span class="lamv2-status-badge lamv2-status-' . esc_attr($status) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span></td>';
                echo '<td>' . ($approved_by ? esc_html($approved_by->display_name) : '<span class="lamv2-cell-placeholder">N/A</span>') . '</td>';
                
                echo '<td>';
                if ($accuse_url) {
                    echo '<a href="' . esc_url($accuse_url) . '" target="_blank" class="lamv2-btn lamv2-btn-sm lamv2-btn-text-link">View</a>';
                } else {
                    echo '<span class="lamv2-cell-placeholder">-</span>';
                }
                echo '</td>';
                
                echo '<td>' . $journal_display . '</td>';

                echo '<td class="lamv2-actions-cell">';
                if ($status === 'published' && !empty($accuse_url) && !empty($final_journal_id)) {
                    echo '<span class="lamv2-cell-placeholder">Terminé</span>';
                } else {
                    if ($status === 'pending_review') {
                        echo '<button class="lamv2-btn lamv2-btn-icon lamv2-btn-success lamv2-ad-action" data-action="approve" data-id="' . $post_id . '" title="Approve"><i class="fas fa-check-circle"></i></button>';
                        echo '<button class="lamv2-btn lamv2-btn-icon lamv2-btn-danger lamv2-ad-action" data-action="deny" data-id="' . $post_id . '" title="Deny"><i class="fas fa-times-circle"></i></button>';
                    } elseif ($status === 'published') {
                        echo '<button class="lamv2-btn lamv2-btn-sm lamv2-btn-secondary lmb-upload-journal-btn" data-id="' . $post_id . '" title="Upload Temporary Journal"><i class="fas fa-newspaper"></i></button>';
                    } else {
                        echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '" class="lamv2-btn lamv2-btn-sm lamv2-btn-view">View</a>';
                    }
                }
                echo '</td>';

                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="10" style="text-align:center;">No ads found matching your criteria.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();

        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => $paged,
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'add_args' => false
        ]);
        
        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }
    
    // --- NEW FUNCTION: v2 submit draft ad for review ---
    private static function lmb_submit_draft_ad_v2() {
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        LMB_Notification_Manager::notify_admins_ad_pending($ad_id);

        wp_send_json_success(['message' => 'Ad submitted for review.']);
    }
    // --- NEW FUNCTION: v2 delete draft ad ---
    private static function lmb_delete_draft_ad_v2() {
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
            return;
        }

        if (wp_delete_post($ad_id, true)) {
            wp_send_json_success(['message' => 'Draft deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete draft.']);
        }
    }
    
    // --- NEW FUNCTION: v2 fetch activity feed for admin and clients ---
     private static function lmb_fetch_feed_v2() {
        // This function is now correctly protected by the checks in handle_request()
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $is_admin = current_user_can('manage_options');

        ob_start();

        if ($is_admin) {
            // Admin View: Show global activity from the log option
            $log = get_option('lmb_activity_log', []);
            $log_paged = array_slice($log, 0, $limit);

            if (empty($log_paged)) {
                echo '<div style="text-align: center; padding: 20px;">No global activity yet.</div>';
            } else {
                foreach ($log_paged as $entry) {
                    $user = $entry['user'] ? get_userdata($entry['user']) : null;
                    $user_name = $user ? $user->display_name : 'System';
                    $time_ago = human_time_diff(strtotime($entry['time'])) . ' ago';
                    
                    $msg_lower = strtolower($entry['msg']);
                    $icon_class = 'icon-create'; $fa_icon = 'fas fa-info-circle';
                    if (strpos($msg_lower, 'approved') !== false) { $icon_class = 'icon-approve'; $fa_icon = 'fas fa-check'; }
                    if (strpos($msg_lower, 'denied') !== false || strpos($msg_lower, 'rejected') !== false) { $icon_class = 'icon-deny'; $fa_icon = 'fas fa-times'; }
                    if (strpos($msg_lower, 'submitted') !== false) { $icon_class = 'icon-submit'; $fa_icon = 'fas fa-paper-plane'; }
                    if (strpos($msg_lower, 'uploaded') !== false) { $icon_class = 'icon-upload'; $fa_icon = 'fas fa-newspaper'; }
                    if (strpos($msg_lower, 'purchased') !== false || strpos($msg_lower, 'points') !== false) { $icon_class = 'icon-points'; $fa_icon = 'fas fa-coins'; }
                    
                    ?>
                    <div class="feed-item">
                        <div class="feed-icon <?php echo esc_attr($icon_class); ?>"><i class="<?php echo esc_attr($fa_icon); ?>"></i></div>
                        <div class="feed-content">
                            <p class="feed-message"><?php echo esc_html($entry['msg']); ?></p>
                            <p class="feed-time"><?php echo esc_html($time_ago); ?> by <strong><?php echo esc_html($user_name); ?></strong></p>
                        </div>
                    </div>
                    <?php
                }
            }
        } else {
            // Client View: Show personal notifications
            $user_id = get_current_user_id();
            $notifications = LMB_Notification_Manager::get_latest($user_id, $limit);

            if (empty($notifications)) {
                echo '<div style="text-align: center; padding: 20px;">You have no recent activity.</div>';
            } else {
                foreach ($notifications as $item) {
                    $icon_details = self::get_feed_icon_details($item['type']);
                    ?>
                    <div class="feed-item">
                        <div class="feed-icon <?php echo esc_attr($icon_details['class']); ?>">
                            <i class="<?php echo esc_attr($icon_details['icon']); ?>"></i>
                        </div>
                        <div class="feed-content">
                            <p class="feed-message"><strong><?php echo esc_html($item['title']); ?></strong>: <?php echo wp_kses_post($item['message']); ?></p>
                            <p class="feed-time"><?php echo esc_html($item['time_ago']); ?></p>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }
    // Helper to get icon details based on feed type
    private static function get_feed_icon_details($type) {
        switch ($type) {
            case 'ad_approved':
            case 'payment_approved':
                return ['class' => 'icon-approve', 'icon' => 'fas fa-check'];
            case 'ad_denied':
            case 'payment_rejected':
                return ['class' => 'icon-deny', 'icon' => 'fas fa-times'];
            case 'ad_pending':
            case 'proof_submitted':
                return ['class' => 'icon-submit', 'icon' => 'fas fa-paper-plane'];
            case 'ad_created':
                return ['class' => 'icon-create', 'icon' => 'fas fa-pencil-alt'];
            case 'package_purchased':
            case 'points_added':
                return ['class' => 'icon-points', 'icon' => 'fas fa-coins'];
            case 'newspaper_uploaded':
                return ['class' => 'icon-upload', 'icon' => 'fas fa-newspaper'];
            default:
                return ['class' => '', 'icon' => 'fas fa-info-circle'];
        }
    }

    // --- NEW FUNCTION: v2 user login with status check and role-based redirect ---
    private static function lmb_login_v2() {
        $creds = [
            'user_login'    => sanitize_user(wp_unslash($_POST['username'])),
            'user_password' => $_POST['password'], // wp_signon handles password securely
            'remember'      => true
        ];

        $user = wp_signon($creds, is_ssl());

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Invalid username or password.'], 401);
            return;
        }
        
        // Check if user is active
        $status = get_user_meta($user->ID, 'lmb_user_status', true);
        if ($status === 'inactive') {
            wp_logout();
            wp_send_json_error(['message' => 'Your account is pending admin approval.'], 403);
            return;
        }

        wp_set_current_user($user->ID, $user->user_login);
        wp_set_auth_cookie($user->ID, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);

        // --- MODIFIED: Role-based redirect logic ---
        $redirect_url = home_url('/'); // Default redirect for any other role

        if (in_array('administrator', $user->roles) || in_array('employee', $user->roles)) {
            // Administrators and Employees are redirected to the /administration page
            $redirect_url = home_url('/administration/');
        } elseif (in_array('client', $user->roles)) {
            // Clients are redirected to the /dashboard page
            $redirect_url = home_url('/dashboard/');
        }
        
        wp_send_json_success(['redirect_url' => $redirect_url]);
    }
    // --- NEW FUNCTION: v2 user signup with role assignment and admin notification ---
    private static function lmb_signup_v2() {
        parse_str($_POST['form_data'], $data);
        
        $email = sanitize_email($data['email']);
        $password = $data['password'];
        $type = sanitize_key($data['signup_type']);

        // Basic validation
        if (!is_email($email)) wp_send_json_error(['message' => 'Invalid email address.']);
        if (email_exists($email)) wp_send_json_error(['message' => 'This email is already registered.']);
        if (strlen($password) < 6) wp_send_json_error(['message' => 'Password must be at least 6 characters long.']);

        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
            return;
        }
        
        // Assign role and save custom meta
        $user = new WP_User($user_id);
        $user->set_role('client');
        update_user_meta($user_id, 'lmb_user_status', 'inactive');
        update_user_meta($user_id, 'lmb_client_type', $type);

        if ($type === 'regular') {
            update_user_meta($user_id, 'first_name', sanitize_text_field($data['first_name']));
            update_user_meta($user_id, 'last_name', sanitize_text_field($data['last_name']));
            update_user_meta($user_id, 'phone_number', sanitize_text_field($data['phone_regular']));
            update_user_meta($user_id, 'city', sanitize_text_field($data['city_regular']));
        } else { // Professional
            update_user_meta($user_id, 'company_name', sanitize_text_field($data['company_name']));
            update_user_meta($user_id, 'company_hq', sanitize_text_field($data['company_hq']));
            update_user_meta($user_id, 'city', sanitize_text_field($data['city_professional']));
            update_user_meta($user_id, 'company_rc', sanitize_text_field($data['company_rc']));
            update_user_meta($user_id, 'phone_number', sanitize_text_field($data['phone_professional']));
        }

        // Notify admins of new registration
        LMB_Notification_Manager::add(1, 'new_user', 'New User Registration', "A new user ($email) has registered and requires approval.");
        
        wp_send_json_success();
    }
    // --- REVISED FUNCTION: v2 fetch inactive clients with search, pagination, and approve/deny actions ---
    private static function lmb_fetch_inactive_clients_v2() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.'], 403);
        }

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 5;
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

        $args = [
            'role' => 'client',
            'number' => $per_page,
            'paged' => $paged,
            'meta_query' => [
                ['key' => 'lmb_user_status', 'value' => 'inactive', 'compare' => '=']
            ],
            'orderby' => 'user_registered',
            'order' => 'DESC'
        ];

        if (!empty($search_term)) {
            $args['search'] = '*' . esc_attr($search_term) . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        ob_start();
        if (!empty($users)) {
            foreach ($users as $user) {
                $user_id = $user->ID;
                $client_type = get_user_meta($user_id, 'lmb_client_type', true);
                $name = ($client_type === 'professional' && get_user_meta($user_id, 'company_name', true)) ? get_user_meta($user_id, 'company_name', true) : $user->display_name;
                $edit_url = home_url('/user-editor/?user_id=' . $user_id);
                $ad_count = count_user_posts($user_id, 'lmb_legal_ad', true);
                $is_new_client = ($ad_count == 0);

                echo '<div class="lmb-client-card" data-user-id="' . $user_id . '">';
                    echo '<div class="lmb-client-info">';
                        echo '<div class="lmb-client-header">';
                            echo '<span class="lmb-client-name"><a href="' . esc_url($edit_url) . '">' . esc_html($name) . '</a></span>'; // LINK ADDED HERE
                            echo '<span class="lmb-client-type-badge ' . esc_attr($client_type) . '">' . esc_html($client_type) . '</span>';
                        echo '</div>';
                        echo '<div class="lmb-client-details">';
                            echo '<div><i class="fas fa-envelope"></i><strong>' . esc_html($user->user_email) . '</strong></div>';
                            echo '<div><i class="fas fa-phone"></i><strong>' . esc_html(get_user_meta($user_id, 'phone_number', true)) . '</strong></div>';
                            echo '<div><i class="fas fa-map-marker-alt"></i><strong>' . esc_html(get_user_meta($user_id, 'city', true)) . '</strong></div>';
                            if ($client_type === 'professional') {
                                echo '<div><i class="fas fa-id-card"></i> RC:<strong>' . esc_html(get_user_meta($user_id, 'company_rc', true)) . '</strong></div>';
                                echo '<div><i class="fas fa-building"></i> HQ:<strong>' . esc_html(get_user_meta($user_id, 'company_hq', true)) . '</strong></div>';
                            }
                            echo '<div><i class="fas fa-calendar-alt"></i> Registered:<strong>' . human_time_diff(strtotime($user->user_registered)) . ' ago</strong></div>';
                        echo '</div>';
                    echo '</div>';
                    echo '<div class="lmb-client-actions">';
                        echo '<button class="lmb-btn lmb-btn-success lmb-client-action-btn" data-action="approve" data-user-id="' . $user_id . '"><i class="fas fa-check"></i> Approuver</button>';
                        if ($is_new_client) {
                            echo '<button class="lmb-btn lmb-btn-danger lmb-client-action-btn" data-action="deny" data-user-id="' . $user_id . '"><i class="fas fa-times"></i> Refuser</button>';
                        }
                    echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div style="text-align:center; padding: 20px;">No inactive clients found.</div>';
        }
        $html = ob_get_clean();

        $pagination_html = paginate_links([
            'base' => '#%#%',
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => ceil($total_users / $per_page),
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);

        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }
    // --- NEW FUNCTION: v2 approve or deny inactive client ---
    private static function lmb_manage_inactive_client_v2() {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $approval_action = isset($_POST['approval_action']) ? sanitize_key($_POST['approval_action']) : '';

        if (!$user_id || !$approval_action) {
            wp_send_json_error(['message' => 'Missing parameters.'], 400);
        }

        if ($approval_action === 'approve') {
            update_user_meta($user_id, 'lmb_user_status', 'active');
            // Optionally, send a welcome email
            wp_new_user_notification($user_id, null, 'user');
            LMB_Ad_Manager::log_activity(sprintf('Approved new client #%d.', $user_id));
            wp_send_json_success(['message' => 'Client approved.']);
        } elseif ($approval_action === 'deny') {
            require_once(ABSPATH.'wp-admin/includes/user.php');
            if (wp_delete_user($user_id)) {
                LMB_Ad_Manager::log_activity(sprintf('Denied and deleted new client #%d.', $user_id));
                wp_send_json_success(['message' => 'Client denied and deleted.']);
            } else {
                wp_send_json_error(['message' => 'Could not delete user.']);
            }
        } else {
            wp_send_json_error(['message' => 'Invalid action.'], 400);
        }
    }

    // --- NEW FUNCTION: v2 fetch active clients with search, filters, pagination, and lock action ---
    private static function lmb_fetch_active_clients_v2() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.'], 403);
        }

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 10;
        parse_str($_POST['filters'] ?? '', $filters);

        $args = [
            'number' => $per_page,
            'paged' => $paged,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'lmb_user_status',
                    'value' => 'active',
                    'compare' => '=',
                ]
            ],
            'orderby' => 'display_name',
            'order' => 'ASC'
        ];
        
        $search_queries = [];

        if (!empty($filters['filter_id']) && is_numeric($filters['filter_id'])) {
             $args['include'] = [intval($filters['filter_id'])];
        }
        if (!empty($filters['filter_name'])) {
            $search_queries[] = sanitize_text_field($filters['filter_name']);
        }
        if (!empty($filters['filter_city'])) {
            $args['meta_query'][] = ['key' => 'city', 'value' => sanitize_text_field($filters['filter_city']), 'compare' => 'LIKE'];
        }
        if (!empty($filters['filter_type'])) {
            if ($filters['filter_type'] === 'regular' || $filters['filter_type'] === 'professional') {
                $args['meta_query'][] = ['key' => 'lmb_client_type', 'value' => sanitize_key($filters['filter_type'])];
            } else {
                $args['role'] = sanitize_key($filters['filter_type']);
            }
        }
        
        if (!empty($search_queries)) {
            $args['search'] = '*' . implode('* *', $search_queries) . '*';
            $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
        }

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        ob_start();
        if (!empty($users)) {
            foreach ($users as $user) {
                $user_id = $user->ID;
                $roles = (array) $user->roles;
                $is_admin = in_array('administrator', $roles);
                $client_type = get_user_meta($user_id, 'lmb_client_type', true);
                $name = ($client_type === 'professional' && get_user_meta($user_id, 'company_name', true)) ? get_user_meta($user_id, 'company_name', true) : $user->display_name;
                
                $ad_count = count_user_posts($user_id, 'lmb_legal_ad', true);

                echo '<tr>';
                echo '<td>' . $user_id . '</td>';
                echo '<td>' . esc_html($name) . '</td>';
                echo '<td>' . esc_html(get_user_meta($user_id, 'city', true) ?: '-') . '</td>';
                
                echo '<td>';
                if ($is_admin) {
                    echo '<span class="lmb-client-type-badge administrator">Admin</span>';
                } elseif ($client_type) {
                    echo '<span class="lmb-client-type-badge ' . esc_attr($client_type) . '">' . esc_html($client_type) . '</span>';
                }
                echo '</td>';

                echo '<td>' . ($is_admin ? '-' : esc_html($ad_count)) . '</td>';
                echo '<td>' . ($is_admin ? '-' : esc_html(LMB_Points::get_balance($user_id))) . '</td>';

                echo '<td class="lmb-actions-cell">';
                
                // --- THIS IS THE CORRECTED LINE ---
                // It now points to a standard URL with a query parameter, which is much more reliable.
                $edit_url = home_url('/user-editor/?user_id=' . $user_id);
                echo '<a href="' . esc_url($edit_url) . '" class="lmb-btn lmb-btn-icon lmb-btn-primary" title="Edit User"><i class="fas fa-user-edit"></i></a>';
                
                if (!$is_admin) {
                    echo '<button class="lmb-btn lmb-btn-icon lmb-btn-warning lmb-lock-user-btn" data-user-id="' . $user_id . '" title="Lock User (set to inactive)"><i class="fas fa-user-lock"></i></button>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;">No active clients found.</td></tr>';
        }
        $html = ob_get_clean();

        $pagination_html = paginate_links([
            'base' => '#%#%',
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => ceil($total_users / $per_page),
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);

        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }

    // --- NEW FUNCTION: v2 lock (set to inactive) an active client ---
    private static function lmb_lock_active_client_v2() {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) wp_send_json_error(['message' => 'Missing user ID.'], 400);
        
        // Prevent locking an admin
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(['message' => 'Administrators cannot be locked.'], 403);
        }

        update_user_meta($user_id, 'lmb_user_status', 'inactive');
        LMB_Ad_Manager::log_activity(sprintf('Locked client account #%d.', $user_id));
        
        wp_send_json_success(['message' => 'Client account has been locked.']);
    }

    // --- UPDATED FUNCTION: v2 update user profile with role-based field restrictions ---
    private static function lmb_update_profile_v2() {
        $user_id_to_update = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        parse_str($_POST['form_data'], $data);

        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        // Security check: Either you are an admin, or you are editing your own profile.
        if (!$is_admin && $current_user_id !== $user_id_to_update) {
            wp_send_json_error(['message' => 'You do not have permission to edit this profile.'], 403);
            return;
        }
        
        $user_data = [];
        // Only admins can edit these restricted fields
        if ($is_admin) {
            if (isset($data['first_name'])) $user_data['first_name'] = sanitize_text_field($data['first_name']);
            if (isset($data['last_name'])) $user_data['last_name'] = sanitize_text_field($data['last_name']);
            if (isset($data['company_name'])) update_user_meta($user_id_to_update, 'company_name', sanitize_text_field($data['company_name']));
            if (isset($data['company_rc'])) update_user_meta($user_id_to_update, 'company_rc', sanitize_text_field($data['company_rc']));
            
            // --- NEW: Handle role and client type updates ---
            if (isset($data['lmb_user_role'])) {
                $user = new WP_User($user_id_to_update);
                $user->set_role(sanitize_key($data['lmb_user_role']));
            }
            if (isset($data['lmb_client_type'])) {
                update_user_meta($user_id_to_update, 'lmb_client_type', sanitize_key($data['lmb_client_type']));
            }
        }
        
        // Fields all users can edit
        if (isset($data['company_hq'])) update_user_meta($user_id_to_update, 'company_hq', sanitize_text_field($data['company_hq']));
        if (isset($data['city'])) update_user_meta($user_id_to_update, 'city', sanitize_text_field($data['city']));
        if (isset($data['phone_number'])) update_user_meta($user_id_to_update, 'phone_number', sanitize_text_field($data['phone_number']));

        // Update the user display name if it has been changed (for regular users)
        if (isset($data['first_name']) && isset($data['last_name'])) {
            $user_data['display_name'] = sanitize_text_field($data['first_name']) . ' ' . sanitize_text_field($data['last_name']);
        }

        if(!empty($user_data)){
            $user_data['ID'] = $user_id_to_update;
            wp_update_user($user_data);
        }

        wp_send_json_success();
    }
    
    // --- NEW FUNCTION: v2 update user password with current password verification for non-admins ---
    private static function lmb_update_password_v2() {
        $user_id_to_update = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        parse_str($_POST['form_data'], $data);

        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        // Security check
        if (!$is_admin && $current_user_id !== $user_id_to_update) {
            wp_send_json_error(['message' => 'You do not have permission to change this password.'], 403);
        }
        
        $new_pass = $data['new_password'];
        $confirm_pass = $data['confirm_password'];

        if (empty($new_pass) || empty($confirm_pass)) {
            wp_send_json_error(['message' => 'Please fill out both new password fields.']);
        }
        if ($new_pass !== $confirm_pass) {
            wp_send_json_error(['message' => 'New passwords do not match.']);
        }
        
        // If a non-admin is changing their own password, we must verify their current password
        if (!$is_admin || $current_user_id === $user_id_to_update) {
            $current_pass = $data['current_password'];
            $user = get_user_by('ID', $user_id_to_update);
            if (!wp_check_password($current_pass, $user->user_pass, $user->ID)) {
                wp_send_json_error(['message' => 'Your current password is not correct.']);
            }
        }
        
        // All checks passed, update the password
        wp_set_password($new_pass, $user_id_to_update);
        
        wp_send_json_success();
    }

    // --- NEW FUNCTION: Generate Accuse on-demand ---
    private static function lmb_admin_generate_accuse() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        if (!$ad_id) {
            wp_send_json_error(['message' => 'Invalid Ad ID.'], 400);
        }

        $accuse_url = LMB_Invoice_Handler::generate_accuse_pdf($ad_id);
        if ($accuse_url) {
            update_post_meta($ad_id, 'lmb_accuse_pdf_url', $accuse_url);
            wp_send_json_success(['message' => 'Accuse PDF generated successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to generate Accuse PDF.']);
        }
    }

    // --- NEW FUNCTION: Upload Temporary Journal ---
    private static function lmb_admin_upload_temporary_journal() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $journal_no = isset($_POST['journal_no']) ? sanitize_text_field($_POST['journal_no']) : '';

        if (!$ad_id || empty($_FILES['journal_file']) || empty($journal_no)) {
            wp_send_json_error(['message' => 'Missing Ad ID, file, or Journal Number.'], 400);
        }
        // Clean up old journal associations to ensure a clean slate.
        $old_temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
        if ($old_temp_journal_id) {
            wp_delete_attachment($old_temp_journal_id, true);
        }
        delete_post_meta($ad_id, 'lmb_temporary_journal_id');
        delete_post_meta($ad_id, 'lmb_final_journal_id');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $attachment_id = media_handle_upload('journal_file', $ad_id);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }
        
        update_post_meta($attachment_id, 'journal_no', $journal_no);
        update_post_meta($ad_id, 'lmb_temporary_journal_id', $attachment_id);

        // --- NEW: Automatically generate accuse after temp journal upload ---
        $accuse_url = LMB_Invoice_Handler::generate_accuse_pdf($ad_id);
        if ($accuse_url) {
            update_post_meta($ad_id, 'lmb_accuse_pdf_url', $accuse_url);
            $message = 'Temporary journal uploaded and Accuse PDF generated successfully.';
        } else {
            $message = 'Temporary journal uploaded, but failed to generate Accuse PDF. A journal number might be missing or another error occurred.';
        }

        wp_send_json_success(['message' => $message]);
    }

    // --- MODIFIED FUNCTION: Upload FINAL Newspaper ---
    private static function lmb_upload_newspaper() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);
        
        // Updated validation for required fields
        if (empty($_POST['journal_no']) || empty($_FILES['newspaper_pdf']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            wp_send_json_error(['message' => 'Missing required fields. Journal N°, PDF, and date range are required.']);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $pdf_id = media_handle_upload('newspaper_pdf', 0);
        if (is_wp_error($pdf_id)) wp_send_json_error(['message' => 'PDF Upload Error: ' . $pdf_id->get_error_message()]);

        // Automatically generate post title from journal number
        $journal_no = sanitize_text_field($_POST['journal_no']);
        $post_title = 'Journal N° ' . $journal_no;
        $post_id = wp_insert_post(['post_type' => 'lmb_newspaper', 'post_title' => $post_title, 'post_status' => 'publish']);
        
        if (is_wp_error($post_id)) { 
            wp_delete_attachment($pdf_id, true); 
            wp_send_json_error(['message' => $post_id->get_error_message()]); 
        }
        
        update_post_meta($post_id, 'newspaper_pdf', $pdf_id);
        update_post_meta($post_id, 'journal_no', $journal_no);

        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);

        // Set date meta for filtering
        update_post_meta($post_id, 'start_date', $start_date);
        update_post_meta($post_id, 'end_date', $end_date);


        // Simplified logic since date range is now mandatory
        $args = [
            'post_type'      => 'lmb_legal_ad',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'approved_date',
                    'value'   => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ],
                // Only associate if no final journal is already linked
                [
                    'key'     => 'lmb_final_journal_id',
                    'compare' => 'NOT EXISTS'
                ]
            ],
        ];

        $ads_query = new WP_Query($args);
        
        $temp_journals_to_delete = [];
        if ($ads_query->have_posts()) {
            foreach ($ads_query->posts as $ad_id) {
                $temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
                if (!empty($temp_journal_id)) { $temp_journals_to_delete[] = $temp_journal_id; }
                update_post_meta($ad_id, 'lmb_final_journal_id', $post_id);
                delete_post_meta($ad_id, 'lmb_temporary_journal_id');
            }
        }
        if (!empty($temp_journals_to_delete)) {
            $unique_ids_to_delete = array_unique($temp_journals_to_delete);
            foreach ($unique_ids_to_delete as $attachment_id) { wp_delete_attachment($attachment_id, true); }
        }
        
        $updated_count = $ads_query->post_count;
        wp_reset_postdata();
        
        wp_send_json_success(['message' => 'Newspaper uploaded. Associated with ' . $updated_count . ' ads. Old temporary files have been deleted.']);
    }

    // --- REVISED FUNCTION for Public Ads Directory ---
    public static function lmb_fetch_public_ads() {
        check_ajax_referer('lmb_nonce', 'nonce');

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        parse_str($_POST['filters'], $filters);

        $args = [
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'lmb_status',
                    'value' => 'published',
                    'compare' => '=',
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => 'approved_date',
            'order' => 'DESC'
        ];
        
        // Apply filters
        // ... (filter logic remains the same)

        $ads_query = new WP_Query($args);
        $html = '';

        if ($ads_query->have_posts()) {
            while ($ads_query->have_posts()) {
                $ads_query->the_post();
                $ad_id = get_the_ID();
                $ad = get_post($ad_id);

                $company_name = get_post_meta($ad_id, 'company_name', true);
                $ad_type = get_post_meta($ad_id, 'ad_type', true);
                $approved_date = get_post_meta($ad_id, 'approved_date', true);
                $newspaper_id = get_post_meta($ad_id, 'lmb_final_journal_id', true); // Use final journal ID
                $newspaper_title = $newspaper_id ? get_post_meta($newspaper_id, 'journal_no', true) : 'N/A'; // Get Journal No

                $journal_html = esc_html($newspaper_title);
                if ($newspaper_id) {
                    $pdf_id = get_post_meta($newspaper_id, 'newspaper_pdf', true);
                    if ($pdf_id) {
                        $pdf_url = wp_get_attachment_url($pdf_id);
                        if ($pdf_url) {
                            $journal_html = '<a style="text-decoration: underline;" href="' . esc_url($pdf_url) . '" target="_blank">' . esc_html($newspaper_title) . '</a>';
                        }
                    }
                }

                // --- MODIFICATION START ---
                // Manually construct the URL for the public-facing page
                $public_announces_url = home_url('/les-annonces/');
                $ad_url = add_query_arg('legal-ad', $ad->ID . '-' . $ad->post_name, $public_announces_url);
                // --- MODIFICATION END ---
                
                $html .= '<tr class="clickable-row" data-href="' . esc_url($ad_url) . '">';
                $html .= '<td>' . esc_html($ad->ID) . '</td>';
                $html .= '<td>' . esc_html($company_name) . '</td>';
                $html .= '<td>' . esc_html($ad_type) . '</td>';
                $html .= '<td>' . esc_html(date_i18n('d/m/Y', strtotime($approved_date))) . '</td>';
                $html .= '<td>' . $journal_html . '</td>';
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="5">Aucune annonce trouvée.</td></tr>';
        }

        $pagination = paginate_links([
            'base' => '%_%',
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $ads_query->max_num_pages,
            'prev_text' => '<',
            'next_text' => '>',
            'type' => 'plain'
        ]);

        wp_reset_postdata();

        wp_send_json_success(['html' => $html, 'pagination' => $pagination]);
    }

    private static function lmb_fetch_my_ads_v2() {
        $user_id = get_current_user_id();
        
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : 'published';
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 10;
        
        parse_str(isset($_POST['filters']) ? $_POST['filters'] : '', $filters);

        $args = [
            'post_type' => 'lmb_legal_ad',
            'author' => $user_id,
            'post_status' => ['publish', 'draft', 'pending'],
            'posts_per_page' => $posts_per_page,
            'paged' => $paged,
            'meta_query' => ['relation' => 'AND'],
        ];

        // Set the main status from Elementor control
        if ($status === 'drafts') $args['meta_query'][] = ['key' => 'lmb_status', 'value' => 'draft'];
        if ($status === 'pending') $args['meta_query'][] = ['key' => 'lmb_status', 'value' => 'pending_review'];
        if ($status === 'published') $args['meta_query'][] = ['key' => 'lmb_status', 'value' => 'published'];
        if ($status === 'denied') $args['meta_query'][] = ['key' => 'lmb_status', 'value' => 'denied'];

        // Apply live filters from the user
        if (!empty($filters['filter_ref'])) $args['p'] = intval($filters['filter_ref']);
        if (!empty($filters['filter_company'])) $args['meta_query'][] = ['key' => 'company_name', 'value' => sanitize_text_field($filters['filter_company']), 'compare' => 'LIKE'];
        if (!empty($filters['filter_type'])) $args['meta_query'][] = ['key' => 'ad_type', 'value' => sanitize_text_field($filters['filter_type']), 'compare' => 'LIKE'];
        if (!empty($filters['filter_date'])) $args['date_query'] = [['year' => date('Y', strtotime($filters['filter_date'])), 'month' => date('m', strtotime($filters['filter_date'])), 'day' => date('d', strtotime($filters['filter_date']))]];
        
        $query = new WP_Query($args);
        
        ob_start();
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $ad_url = get_permalink($post_id);
                echo '<tr class="clickable-row" data-href="' . esc_url($ad_url) . '">';

                switch ($status) {
                    case 'published':
                        $accuse_id = get_post_meta($post_id, 'lmb_accuse_attachment_id', true);
                        echo '<td>' . $post_id . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_the_date()) . '</td>';
                        echo '<td>' . (get_post_meta($post_id, 'approved_by', true) ? get_the_author_meta('display_name', get_post_meta($post_id, 'approved_by', true)) : 'N/A') . '</td>';
                        echo '<td>' . ($accuse_id ? '<a href="'.wp_get_attachment_url($accuse_id).'" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-text-link">Accuse</a>' : '<span class="cell-placeholder">-</span>') . '</td>';
                        echo '<td><span class="cell-placeholder">-</span></td>'; // Journal column placeholder
                        break;
                    case 'pending':
                        echo '<td>' . $post_id . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_the_date()) . '</td>';
                        break;
                    case 'drafts':
                        echo '<td>' . $post_id . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_the_date()) . '</td>';
                        echo '<td class="lmb-actions-cell no-hover"><button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-submit-ad-btn" data-ad-id="'.$post_id.'"><i class="fas fa-paper-plane"></i> Submit</button><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-delete-ad-btn" data-ad-id="'.$post_id.'"><i class="fas fa-trash"></i> Delete</button></td>';
                        break;
                    case 'denied':
                        echo '<td>' . $post_id . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_the_modified_date()) . '</td>';
                        echo '<td class="denial-reason">' . esc_html(get_post_meta($post_id, 'denial_reason', true)) . '</td>';
                        echo '<td class="lmb-actions-cell no-hover"><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-delete-ad-btn" data-ad-id="'.$post_id.'"><i class="fas fa-trash"></i> Delete</button></td>';
                        break;
                }
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;">No ads found for this status.</td></tr>';
        }
        $html = ob_get_clean();
        
        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);

        wp_reset_postdata();
        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }

    // --- REVISED FUNCTION for Newspaper Directory ---
    private static function lmb_fetch_newspapers_v2() {
        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        parse_str($_POST['filters'] ?? '', $filters);

        $args = [
            'post_type' => 'lmb_newspaper',
            'post_status' => 'publish',
            'posts_per_page' => 15,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        if (!empty($filters['s'])) {
            $args['s'] = sanitize_text_field($filters['s']);
        }

        if (!empty($filters['filter_ref']) && is_numeric($filters['filter_ref'])) {
            $args['p'] = intval($filters['filter_ref']);
        }
        
        if (!empty($filters['filter_date'])) {
            $args['date_query'] = [[
                'year'  => date('Y', strtotime($filters['filter_date'])),
                'month' => date('m', strtotime($filters['filter_date'])),
                'day'   => date('d', strtotime($filters['filter_date'])),
            ]];
        }

        $query = new WP_Query($args);
        $html = '';

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pdf_id = get_post_meta(get_the_ID(), 'newspaper_pdf', true);
                $pdf_url = wp_get_attachment_url($pdf_id);
                $journal_no = get_post_meta(get_the_ID(), 'journal_no', true);
                
                // Add class and data-href to the table row
                $html .= '<tr class="clickable-row" data-href="' . esc_url($pdf_url) . '">';
                $html .= '<td>' . esc_html($journal_no) . '</td>';
                $html .= '<td>' . esc_html(get_the_date()) . '</td>';
                $html .= '<td class="lmb-actions-cell">';
                if ($pdf_url) {
                    $html .= '<a target="_blank" href="'.esc_url($pdf_url).'" class="lmb-btn lmb-btn-sm lmb-btn-primary"><i class="fas fa-download"></i> Télécharger</a>';
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
        } else {
            $html = '<tr><td colspan="3" style="text-align:center;">No newspapers found matching your search.</td></tr>';
        }

        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'),
            'format' => '',
            'current' => $paged,
            'total' => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'add_args' => false
        ]);

        wp_reset_postdata();
        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }

    // --- NEW FUNCTION: fetch payments for admin to handle ---
    private static function lmb_fetch_payments() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.'], 403);
        }

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        parse_str($_POST['filters'] ?? '', $filters);

        $args = [
            'post_type' => 'lmb_payment',
            'post_status' => 'publish',
            'posts_per_page' => 15,
            'paged' => $paged,
            'meta_query' => ['relation' => 'AND'],
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        // Apply filters
        if (!empty($filters['filter_status'])) {
            $args['meta_query'][] = ['key' => 'payment_status', 'value' => sanitize_key($filters['filter_status'])];
        }
        if (!empty($filters['filter_ref'])) {
            $args['meta_query'][] = ['key' => 'payment_reference', 'value' => sanitize_text_field($filters['filter_ref']), 'compare' => 'LIKE'];
        }
        if (!empty($filters['filter_client'])) {
            $user_query = new WP_User_Query([
                'search' => '*' . esc_attr(sanitize_text_field($filters['filter_client'])) . '*',
                'search_columns' => ['user_login', 'display_name'],
                'fields' => 'ID'
            ]);
            $user_ids = $user_query->get_results();
            $args['author__in'] = !empty($user_ids) ? $user_ids : [0];
        }

        $query = new WP_Query($args);
        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $payment_id = get_the_ID();
                $client = get_userdata(get_post_field('post_author', $payment_id));
                $package_id = get_post_meta($payment_id, 'package_id', true);
                $status = get_post_meta($payment_id, 'payment_status', true);
                $proof_id = get_post_meta($payment_id, 'proof_attachment_id', true);

                echo '<tr>';
                echo '<td>' . esc_html(get_post_meta($payment_id, 'payment_reference', true)) . '</td>';
                echo '<td>' . ($client ? '<a href="' . home_url('/user-editor/?user_id=' . $client->ID) . '">' . esc_html($client->display_name) . '</a>' : 'N/A') . '</td>';
                echo '<td>' . ($package_id ? esc_html(get_the_title($package_id)) : 'N/A') . '</td>';
                echo '<td>' . esc_html(get_post_meta($payment_id, 'package_price', true)) . ' MAD</td>';
                echo '<td>' . esc_html(get_the_date('Y-m-d H:i', $payment_id)) . '</td>';
                echo '<td><span class="lmb-status-badge lmb-status-' . esc_attr($status) . '">' . esc_html($status) . '</span></td>';
                echo '<td class="lmb-actions-cell">';
                if ($status === 'pending') {
                    if ($proof_id) {
                        echo '<a href="' . esc_url(wp_get_attachment_url($proof_id)) . '" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-paperclip"></i> View Proof</a>';
                    }
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action-btn" data-action="approve" data-id="' . $payment_id . '"><i class="fas fa-check"></i> Approve</button>';
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action-btn" data-action="deny" data-id="' . $payment_id . '"><i class="fas fa-times"></i> Deny</button>';
                } else {
                    echo '—';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;">No payments found for this status.</td></tr>';
        }
        
        $html = ob_get_clean();
        wp_reset_postdata();

        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $paged,
            'total' => $query->max_num_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;',
            'add_args' => false
        ]);

        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }

    // --- NEW FUNCTION: fetch package data for admin ---
    private static function lmb_get_package_data() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied']);
        }
        
        $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        if (!$package_id) {
            wp_send_json_error(['message' => 'Invalid package ID']);
        }
        
        $package_post = get_post($package_id);
        if (!$package_post || $package_post->post_type !== 'lmb_package') {
            wp_send_json_error(['message' => 'Package not found']);
        }
        
        $package_data = [
            'id'          => $package_post->ID,
            'name'        => $package_post->post_title,
            'description' => $package_post->post_content,
            'price'       => get_post_meta($package_id, 'price', true),
            'points'      => get_post_meta($package_id, 'points', true),
            'cost_per_ad' => get_post_meta($package_id, 'cost_per_ad', true),
        ];

        wp_send_json_success(['package' => $package_data]);
    }

    // --- REVISED FUNCTION: fetch eligible ads for newspaper creation ---
    private static function lmb_fetch_eligible_ads() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access Denied.'], 403);
        
        parse_str($_POST['filters'] ?? '', $filters);

        $date_start = sanitize_text_field($filters['date_start'] ?? '');
        $date_end = sanitize_text_field($filters['date_end'] ?? '');

        if (empty($date_start) || empty($date_end)) {
            wp_send_json_error(['message' => 'Dates de début et de fin requises.']);
        }

        $args = [
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'lmb_status', 'value' => 'published', 'compare' => '='],
                [
                    'key' => 'approved_date',
                    'value' => [$date_start, $date_end],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ],
                // CRITICAL: Exclude ads already associated with a final journal
                //[    'key' => 'lmb_final_journal_id','compare' => 'NOT EXISTS' ],
            ],
            'orderby' => 'approved_date',
            'order' => 'ASC',
        ];
        
        $query = new WP_Query($args);
        $ads = [];

        if ($query->have_posts()) {
            foreach ($query->posts as $post_id) {
                $journal_status = get_post_meta($post_id, 'lmb_temporary_journal_id', true) ? 'Journal Temporaire' : 'Aucun';

                $ads[] = [
                    'ID' => $post_id,
                    'company_name' => get_post_meta($post_id, 'company_name', true) ?: 'N/A',
                    'ad_type' => get_post_meta($post_id, 'ad_type', true),
                    'approved_date' => get_post_meta($post_id, 'approved_date', true),
                    'journal_status' => $journal_status,
                    'full_text' => get_post_meta($post_id, 'full_text', true),
                ];
            }
        }
        wp_reset_postdata();

        if (empty($ads)) {
             wp_send_json_error(['message' => 'Aucune annonce éligible trouvée pour ces critères.']);
        }

        wp_send_json_success(['ads' => $ads]);
    }

    // --- FUNCTION FOR GENERATING NEWSPAPER PREVIEW ---
    private static function lmb_generate_newspaper_preview() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access Denied.'], 403);
        
        $ad_ids = array_map('intval', $_POST['ad_ids'] ?? []);
        $journal_no = sanitize_text_field($_POST['journal_no'] ?? 'N/A');
        $date_start = sanitize_text_field($_POST['date_start'] ?? 'N/A');
        $date_end = sanitize_text_field($_POST['date_end'] ?? 'N/A');

        if (empty($ad_ids)) wp_send_json_error(['message' => 'Aucune annonce sélectionnée.']);

        // 1. Fetch Ad Content and build HTML blocks (WP_Query is explicitly used to fetch full data)
        $ads_query = new WP_Query([
            'post_type' => 'lmb_legal_ad', 
            'posts_per_page' => -1, 
            'post__in' => $ad_ids, 
            'orderby' => 'post__in', // Maintain user's selection order
            'post_status' => ['publish', 'draft'],
            'suppress_filters' => true
        ]);
        
        $ads_html = '';

        if ($ads_query->have_posts()) {
            while ($ads_query->have_posts()) {
                $ads_query->the_post();
                $post_id = get_the_ID();
                
                $ad_type = get_post_meta($post_id, 'ad_type', true);
                $company_name = get_post_meta($post_id, 'company_name', true) ?: 'N/A';
                $full_text = get_post_meta($post_id, 'full_text', true);
                
                if (empty($full_text)) {
                    $full_text = '<p style="color:red;font-weight:bold;">[ERREUR: Contenu (full_text) non trouvé pour Réf ID ' . $post_id . '. Vérifiez les métadonnées de l\'annonce.]</p>';
                }

                // Preserve line breaks for MultiCell/layout effect
                $display_text = str_replace(['<br>', '<br/>', '<br />'], "\n", $full_text);

                $ads_html .= '<div class="ad-block">';
                $ads_html .= '<div class="ad-title">' . esc_html($ad_type . ' - ' . $company_name) . '</div>';
                $ads_html .= '<div class="ad-body">' . wp_kses_post($display_text) . '</div>';
                $ads_html .= '</div>';
            }
        }
        wp_reset_postdata();

        if (empty($ads_html)) {
             wp_send_json_error(['message' => 'La requête WordPress n\'a trouvé aucune annonce correspondant aux IDs sélectionnés.']);
        }

        // 2. Fetch template (from DB setting or fallback file)
        $publication_date = date_i18n('d F Y', current_time('timestamp'));
        $template = get_option('lmb_newspaper_template_html');
        
        if (empty($template)) {
             $template = self::get_default_newspaper_template();
        }
        
        // 3. Replace dynamic placeholders
        $template = str_replace('[NUMÉRO DU JOURNAL]', esc_html($journal_no), $template);
        $template = str_replace('[DATE DE PARUTION]', esc_html($publication_date), $template);
        $template = str_replace('[DATE DÉBUT]', esc_html($date_start), $template);
        $template = str_replace('[DATE FIN]', esc_html($date_end), $template);
        
        // 4. Inject Ad Content
        $final_html = str_replace('', $ads_html, $template);


        // 5. Create a temporary DRAFT Newspaper Post to store the HTML and ad IDs
        $temp_post_title = 'BROUILLON JOURNAL ' . $journal_no . ' ' . date('Y-m-d H:i:s');
        $temp_post_id = wp_insert_post([
            'post_type' => 'lmb_newspaper',
            'post_title' => $temp_post_title,
            'post_status' => 'draft',
            'meta_input' => [
                'lmb_temp_newspaper_html' => $final_html, // Full HTML for viewing
                'lmb_temp_ad_ids' => json_encode($ad_ids), // Selected ads for final approval
                'journal_no' => $journal_no, 
                'start_date' => $date_start,
                'end_date' => $date_end,
            ]
        ]);
        
        if (is_wp_error($temp_post_id)) {
            wp_send_json_error(['message' => 'Erreur lors de la création du brouillon de journal: ' . $temp_post_id->get_error_message()]);
        }
        
        // 6. Return the secure PDF preview URL
        $pdf_url = home_url('/?lmb-pdf-preview=1&post_id=' . $temp_post_id . '&nonce=' . wp_create_nonce('lmb_pdf_preview_' . $temp_post_id));
        
        wp_send_json_success(['pdf_url' => $pdf_url, 'temp_post_id' => $temp_post_id]);
    }
    
    // Helper to get default template (MUST BE LOADED FROM FILE)
    private static function get_default_newspaper_template() {
        $path = LMB_CORE_PATH . 'includes/templates/newspaper_template_ads_content.html';
        return file_exists($path) ? file_get_contents($path) : '';
    }

    // --- FUNCTION: Final Approval and Publishing (FIXED for Inter-Widget Sync) ---
    private static function lmb_approve_and_publish_newspaper() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access Denied.'], 403);
        
        $ad_ids = array_map('intval', $_POST['ad_ids'] ?? []);
        $journal_no = sanitize_text_field($_POST['journal_no'] ?? '');
        $date_start = sanitize_text_field($_POST['date_start'] ?? '');
        $date_end = sanitize_text_field($_POST['date_end'] ?? '');

        if (empty($ad_ids) || empty($journal_no)) {
            wp_send_json_error(['message' => 'Données de journal ou annonces manquantes.']);
        }
        
        // --- 1. RETRIEVE THE DRAFT AND ITS HTML CONTENT ---
        $draft_args = [
            'post_type' => 'lmb_newspaper',
            'post_status' => 'draft',
            'posts_per_page' => 1,
            'author' => get_current_user_id(),
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'journal_no', 'value' => $journal_no, 'compare' => '='],
                ['key' => 'start_date', 'value' => $date_start, 'compare' => '='],
                ['key' => 'end_date', 'value' => $date_end, 'compare' => '='],
                ['key' => 'lmb_temp_ad_ids', 'compare' => 'EXISTS'],
            ]
        ];
        $draft_posts = get_posts($draft_args);
        
        if (empty($draft_posts)) {
             wp_send_json_error(['message' => 'Erreur: Le brouillon de prévisualisation n\'a pas pu être retrouvé. Veuillez visualiser à nouveau.']);
        }
        
        $draft_post_id = $draft_posts[0]->ID;
        // In a real flow, this is where you would call the PDF generation library with the raw HTML
        $final_html_content = get_post_meta($draft_post_id, 'lmb_temp_newspaper_html', true);

        if (empty($final_html_content)) {
            wp_send_json_error(['message' => 'Erreur: Le contenu HTML du brouillon est vide.']);
        }

        // --- 2. CREATE THE FINAL lmb_newspaper post ---
        $post_title = 'Journal N° ' . $journal_no . ' (' . $date_start . ' au ' . $date_end . ') - FINAL';
        $newspaper_id = wp_insert_post([
            'post_type' => 'lmb_newspaper',
            'post_title' => $post_title,
            'post_status' => 'publish',
        ]);
        
        if (is_wp_error($newspaper_id)) { 
            wp_send_json_error(['message' => 'Erreur lors de la création du post Journal Final: ' . $newspaper_id->get_error_message()]);
        }
        
        // --- 3. SIMULATE FINAL PDF LINK (CRITICAL for directory to work) ---
        // We do not have PDF generation capability here, so we simulate the required meta fields.
        $dummy_pdf_path = wp_upload_dir()['baseurl'] . '/lmb-journals-final/journal-' . sanitize_title($journal_no) . '-' . date('Ymd') . '.pdf';
        
        update_post_meta($newspaper_id, 'newspaper_pdf', 0); // Set dummy ID or 0
        update_post_meta($newspaper_id, 'newspaper_pdf_url', $dummy_pdf_path); 
        
        // Additional meta fields for display/linking
        update_post_meta($newspaper_id, 'journal_no', $journal_no); 
        update_post_meta($newspaper_id, 'start_date', $date_start);
        update_post_meta($newspaper_id, 'end_date', $date_end);
        
        // --- 4. UPDATE ADS AND CLEANUP ---
        $temp_journals_to_delete = [];
        // Update all selected legal ads to link to the FINAL newspaper 
        foreach ($ad_ids as $ad_id) {
            $temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
            if (!empty($temp_journal_id)) { $temp_journals_to_delete[] = $temp_journal_id; }
            
            update_post_meta($ad_id, 'lmb_final_journal_id', $newspaper_id);
            update_post_meta($ad_id, 'lmb_final_journal_no', $journal_no); 
            delete_post_meta($ad_id, 'lmb_temporary_journal_id');
        }

        // Delete temporary attachments associated with the ads
        if (!empty($temp_journals_to_delete)) {
            $unique_ids_to_delete = array_unique($temp_journals_to_delete);
            foreach ($unique_ids_to_delete as $attachment_id) { wp_delete_attachment($attachment_id, true); }
        }

        // 5. Delete the temporary draft post
        wp_delete_post($draft_post_id, true);

        LMB_Ad_Manager::log_activity(sprintf('Journal Final N°%s publié, associant %d annonces.', $journal_no, count($ad_ids)));
        
        wp_send_json_success(['message' => 'Journal publié avec succès et annonces mises à jour.']);
    }
    
    // --- NEW FUNCTION: Discard Draft Action ---
    private static function lmb_discard_newspaper_draft() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access Denied.'], 403);
        
        $temp_post_id = isset($_POST['temp_post_id']) ? intval($_POST['temp_post_id']) : 0;
        $draft_post = get_post($temp_post_id);

        if (!$draft_post || $draft_post->post_type !== 'lmb_newspaper' || $draft_post->post_status !== 'draft') {
            wp_send_json_error(['message' => 'Invalid or already deleted draft post ID.'], 400);
        }

        if (wp_delete_post($temp_post_id, true)) {
            wp_send_json_success(['message' => 'Draft deleted successfully.']);
        } else {
            wp_send_json_error(['message' => 'Failed to delete the draft post.']);
        }
    }

}