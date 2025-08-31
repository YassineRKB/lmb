<?php
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
            'lmb_login_v2', 'lmb_signup_v2'
        ];
        // --- MODIFICATION: Make auth actions public ---
        $public_actions = ['lmb_login_v2', 'lmb_signup_v2'];

        foreach ($actions as $action) {
            add_action('wp_ajax_' . $action, [__CLASS__, 'handle_request']);
            if (in_array($action, $public_actions)) {
                add_action('wp_ajax_nopriv_' . $action, [__CLASS__, 'handle_request']);
            }
        }
    }

    public static function handle_request() {
        check_ajax_referer('lmb_nonce', 'nonce');
        
        $public_actions = ['lmb_login_v2', 'lmb_signup_v2'];
        $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';

        if (!in_array($action, $public_actions) && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.'], 403);
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

    private static function lmb_upload_newspaper() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);
        if (empty($_POST['newspaper_title']) || empty($_FILES['newspaper_pdf'])) wp_send_json_error(['message' => 'Missing required fields.']);
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $pdf_id = media_handle_upload('newspaper_pdf', 0);
        if (is_wp_error($pdf_id)) wp_send_json_error(['message' => 'PDF Upload Error: ' . $pdf_id->get_error_message()]);
        
        $thumb_id = !empty($_FILES['newspaper_thumbnail']['name']) ? media_handle_upload('newspaper_thumbnail', 0) : null;

        $post_id = wp_insert_post(['post_type' => 'lmb_newspaper', 'post_title' => sanitize_text_field($_POST['newspaper_title']), 'post_status' => 'publish', 'post_date' => sanitize_text_field($_POST['newspaper_date']) . ' 00:00:00']);
        if (is_wp_error($post_id)) wp_send_json_error(['message' => $post_id->get_error_message()]);

        update_post_meta($post_id, 'newspaper_pdf', $pdf_id);
        if ($thumb_id && !is_wp_error($thumb_id)) set_post_thumbnail($post_id, $thumb_id);

        wp_send_json_success(['message' => 'Newspaper uploaded successfully.']);
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
    
        // Apply filters
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
                $args['author__in'] = [0]; // No results
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
                 $args['meta_query'][] = ['key' => 'approved_by', 'value' => 0]; // No results
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
                $accuse_id = get_post_meta($post_id, 'lmb_accuse_attachment_id', true);
                // Placeholder for journal logic
                $journal_name = "Journal XYZ"; 
    
                echo '<tr class="clickable-row" data-href="' . esc_url(get_edit_post_link($post_id)) . '">';
                echo '<td>' . esc_html($post_id) . '</td>';
                echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                echo '<td>' . get_the_date('Y-m-d') . '</td>';
                echo '<td>' . ($client ? esc_html($client->display_name) : 'N/A') . '</td>';
                echo '<td><span class="lmb-status-badge lmb-status-' . esc_attr($status) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span></td>';
                echo '<td>' . ($approved_by ? esc_html($approved_by->display_name) : '<span class="cell-placeholder">N/A</span>') . '</td>';
                
                // Accuse Column
                echo '<td>';
                if ($status === 'published' && $accuse_id) {
                    echo '<a href="' . esc_url(wp_get_attachment_url($accuse_id)) . '" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-text-link">Accuse</a>';
                } else {
                    echo '<span class="cell-placeholder">-</span>';
                }
                echo '</td>';
    
                // Journal Column
                echo '<td>';
                // Replace with your actual journal logic
                if ($status === 'published' && $journal_name) {
                     echo '<a href="#" class="lmb-btn lmb-btn-sm lmb-btn-text-link">Journal</a>';
                } else {
                    echo '<span class="cell-placeholder">-</span>';
                }
                echo '</td>';
    
                // Actions Column
                echo '<td class="lmb-actions-cell">';
                if ($status === 'pending_review') {
                    echo '<button class="lmb-btn lmb-btn-icon lmb-btn-success lmb-ad-action" data-action="approve" data-id="' . $post_id . '" title="Approve"><i class="fas fa-check-circle"></i></button>';
                    echo '<button class="lmb-btn lmb-btn-icon lmb-btn-danger lmb-ad-action" data-action="deny" data-id="' . $post_id . '" title="Deny"><i class="fas fa-times-circle"></i></button>';
                } elseif ($status === 'published') {
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-info" title="Upload Temporary Journal"><i class="fas fa-newspaper"></i></button>';
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-info" title="Generate Accuse"><i class="fas fa-receipt"></i></button>';
                } else {
                     echo '<button class="lmb-btn lmb-btn-sm lmb-btn-view">View</button>';
                }
                echo '</td>';
    
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="10" style="text-align:center;">No ads found matching your criteria.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
    
        wp_send_json_success(['html' => $html, 'pagination' => '']); // Pagination will be added next
    }
    
    // --- NEW FUNCTION: v2 my ads management fetch with filters ---
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

        // Redirect logic
        $redirect_url = home_url('/'); // Default redirect
        if (in_array('administrator', $user->roles) || in_array('employee', $user->roles)) {
            $redirect_url = admin_url();
        } elseif (in_array('client', $user->roles)) {
            $dashboard_page_id = get_option('lmb_client_dashboard_page_id'); // You may need a settings page for this
            if ($dashboard_page_id) {
                $redirect_url = get_permalink($dashboard_page_id);
            }
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


}