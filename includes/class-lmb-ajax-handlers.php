<?php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {

    public static function init() {
        $actions = [
            'lmb_ad_status_change', 'lmb_user_submit_for_review', 'lmb_payment_action',
            'lmb_get_balance_history', 'lmb_load_admin_tab', 'lmb_search_user', 'lmb_update_balance',
            'lmb_generate_package_invoice', 'lmb_get_notifications', 'lmb_mark_notification_read',
            'lmb_mark_all_notifications_read', 'lmb_save_package', 'lmb_delete_package',
            'lmb_upload_newspaper', 'lmb_upload_bank_proof', 'lmb_fetch_users', 'lmb_fetch_ads',
            'lmb_upload_accuse', 'lmb_user_get_ads',
            'lmb_get_pending_accuse_ads', 'lmb_attach_accuse_to_ad',
            'lmb_get_pending_invoices_form', 'lmb_generate_invoice_pdf',
            'lmb_regenerate_ad_text', 'lmb_admin_generate_pdf'
        ];
        foreach ($actions as $action) {
            add_action('wp_ajax_' . $action, [__CLASS__, 'handle_request']);
            add_action('wp_ajax_nopriv_' . $action, [__CLASS__, 'handle_request']);
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

    private static function lmb_user_get_ads() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }
    
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $status = isset($_POST['status']) ? sanitize_key($_POST['status']) : 'draft';
    
        // Arguments for the query to get the post count for pagination
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
    
        // We need to run the query to get the total number of pages
        $query = new WP_Query($args);
        $max_pages = $query->max_num_pages;
        wp_reset_postdata();

        // Now, get the HTML content by calling the widget's render method
        $widget_file = LMB_CORE_PATH . 'elementor/widgets/class-lmb-user-ads-list-widget.php';
        if (file_exists($widget_file)) {
            require_once $widget_file;
            if (class_exists('LMB_User_Ads_List_Widget')) {
                // We need to create a temporary instance of the widget to access its methods
                $widget_instance = new LMB_User_Ads_List_Widget();
                
                // Capture the output of the render method
                ob_start();
                // This is a private method, so we'll have to make it public to call it.
                // Or, we can duplicate the logic here, but it's better to make it public.
                // Let's assume we made it public for this example.
                // See the updated widget file in the next step.
                $widget_instance->render_ads_for_status($status, $paged);
                $html = ob_get_clean();

                wp_send_json_success([
                    'html' => $html,
                    'max_pages' => $max_pages
                ]);
            }
        }
    
        wp_send_json_error(['message' => 'Widget class not found.']);
    }

    // --- REVISED FUNCTION ---
    private static function lmb_upload_accuse() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        if (empty($_POST['legal_ad_id']) || empty($_FILES['accuse_file'])) {
            wp_send_json_error(['message' => 'Missing required fields: Ad ID and file are required.']);
        }

        $ad_id = intval($_POST['legal_ad_id']);
        $ad = get_post($ad_id);

        // --- ADDED VALIDATION ---
        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => 'Invalid Ad ID. No legal ad found with this ID.']);
        }
        if (get_post_meta($ad_id, 'lmb_status', true) !== 'published') {
            wp_send_json_error(['message' => 'This ad is not published. You can only upload an accuse for published ads.']);
        }
        if (get_post_meta($ad_id, 'lmb_accuse_attachment_id', true)) {
            wp_send_json_error(['message' => 'An accuse document has already been uploaded for this ad.']);
        }
        // --- END VALIDATION ---

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $attachment_id = media_handle_upload('accuse_file', 0); // Uploads the file to the media library

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        // Link the attachment to the ad and vice-versa
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $ad_id);
        update_post_meta($ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        
        // Notify the user who submitted the ad
        $client_id = $ad->post_author;
        if ($client_id && class_exists('LMB_Notification_Manager')) {
            $title = sprintf(__('Receipt for ad "%s" is ready', 'lmb-core'), get_the_title($ad_id));
            $msg = __('The official receipt (accuse) for your legal ad is now available for download from your dashboard.', 'lmb-core');
            LMB_Notification_Manager::add($client_id, 'receipt_ready', $title, $msg, ['ad_id' => $ad_id]);
        }
        
        wp_send_json_success(['message' => 'Accuse uploaded and attached successfully. The client has been notified.']);
    }

    // --- REVISED FUNCTION ---
    private static function lmb_fetch_ads() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.']);
        }

        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        $args = [
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'any',
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => ['relation' => 'AND'],
        ];

        // --- FILTERING LOGIC ---

        // Filter by Status
        if (!empty($_POST['filter_status'])) {
            $args['meta_query'][] = [
                'key' => 'lmb_status', 
                'value' => sanitize_text_field($_POST['filter_status'])
            ];
        }

        // Filter by Ad Type
        if (!empty($_POST['filter_ad_type'])) {
            $args['meta_query'][] = [
                'key' => 'ad_type', 
                'value' => sanitize_text_field($_POST['filter_ad_type'])
            ];
        }

        // Filter by Company Name
        if (!empty($_POST['filter_company'])) {
            $args['meta_query'][] = [
                'key' => 'company_name', 
                'value' => sanitize_text_field($_POST['filter_company']),
                'compare' => 'LIKE'
            ];
        }

        // Filter by Reference/ID or Title
        if (!empty($_POST['filter_ref'])) {
            $ref = sanitize_text_field($_POST['filter_ref']);
            if (is_numeric($ref)) {
                // If it's a number, search by ID and title/content
                $args['meta_query']['relation'] = 'OR';
                $args['meta_query'][] = ['key' => 'ID', 'value' => $ref];
                $args['s'] = $ref;
            } else {
                // If it's text, just search title/content
                $args['s'] = $ref;
            }
        }

        // Filter by User
        if (!empty($_POST['filter_user'])) {
            $user_data = sanitize_text_field($_POST['filter_user']);
            if (is_numeric($user_data)) {
                $args['author'] = intval($user_data);
            } else {
                $user = get_user_by('email', $user_data) ?: get_user_by('login', $user_data);
                if ($user) {
                    $args['author'] = $user->ID;
                } else {
                    // No user found, so force no results
                    $args['author'] = -1;
                }
            }
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
                echo '<tr>';
                echo '<td>' . esc_html($post_id) . '</td>';
                echo '<td><a href="' . get_edit_post_link($post_id) . '" target="_blank">' . get_the_title() . '</a></td>';
                echo '<td>' . ($client ? esc_html($client->display_name) : 'N/A') . '</td>';
                echo '<td><span class="lmb-status-badge lmb-status-' . esc_attr($status) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span></td>';
                echo '<td>' . get_the_date() . '</td>';
                echo '<td class="lmb-actions-cell">';
                if ($status === 'pending_review') {
                    echo '<button class="lmb-btn lmb-btn-sm lmb-ad-action" data-action="approve" data-id="'.$post_id.'">Approve</button>';
                    echo '<button class="lmb-btn lmb-btn-sm lmb-ad-action" data-action="deny" data-id="'.$post_id.'">Deny</button>';
                } else {
                    echo '—';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            
            // Pagination
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1) {
                echo '<div class="lmb-pagination">' . paginate_links(['total' => $total_pages, 'current' => $paged, 'format' => '?paged=%#%', 'base' => '#%#%']) . '</div>';
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
        $users = $user_query->get_results();
        
        ob_start();
        if (!empty($users)) {
            echo '<table class="lmb-users-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Balance</th><th>Actions</th></tr></thead><tbody>';
            foreach ($users as $user) {
                echo '<tr>';
                echo '<td>' . esc_html($user->ID) . '</td>';
                echo '<td>' . esc_html($user->display_name) . '</td>';
                echo '<td>' . esc_html($user->user_email) . '</td>';
                echo '<td>' . LMB_Points::get_balance($user->ID) . '</td>';
                echo '<td><a href="' . get_edit_user_link($user->ID) . '" target="_blank" class="lmb-btn lmb-btn-sm">Edit</a></td>';
                echo '</tr>';
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

    // --- REVISED FUNCTION ---
    private static function lmb_upload_bank_proof() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }
        
        // --- FIX: Changed the check from 'package_id' to 'payment_id' ---
        if (empty($_POST['payment_id']) || empty($_FILES['proof_file'])) {
            wp_send_json_error(['message' => 'Missing required fields. Please select an invoice and a proof file.']);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $user_id = get_current_user_id();
        $payment_id = intval($_POST['payment_id']);
        $payment_post = get_post($payment_id);

        // Security check: ensure the payment belongs to the current user
        if (!$payment_post || $payment_post->post_author != $user_id) {
            wp_send_json_error(['message' => 'Invalid invoice selected.']);
        }

        // Handle the file upload
        $attachment_id = media_handle_upload('proof_file', $payment_id); // Associate upload with payment post
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'File Upload Error: ' . $attachment_id->get_error_message()]);
        }

        // Update the payment post with the proof attachment ID
        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        
        LMB_Ad_Manager::log_activity(sprintf('Payment proof for invoice #%d submitted.', $payment_id));
        
        // Notify admins that a new proof is ready for verification
        if(class_exists('LMB_Notification_Manager')) {
            $user = wp_get_current_user();
            $package_id = get_post_meta($payment_id, 'package_id', true);
            $title = 'New Payment Proof Submitted';
            $msg = sprintf('User %s has submitted proof for invoice #%s ("%s").', $user->display_name, get_post_meta($payment_id, 'payment_reference', true), get_the_title($package_id));
            
            // This is a new helper function we should add to the notification manager
            // For now, we'll just get the admin IDs directly.
            $admin_ids = get_users(['role' => 'administrator', 'fields' => 'ID']);
            foreach ($admin_ids as $admin_id) {
                LMB_Notification_Manager::add($admin_id, 'proof_submitted', $title, $msg, ['ad_id' => $payment_id]);
            }
        }

        wp_send_json_success(['message' => 'Your proof has been submitted for review. You will be notified once it is approved.']);
    }

    // --- REVISED FUNCTION ---
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
        
        if ($package_id) {
            $post_data['ID'] = $package_id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        $new_pkg_id = $package_id ?: $result;
        update_post_meta($new_pkg_id, 'price', $price);
        update_post_meta($new_pkg_id, 'points', $points);
        update_post_meta($new_pkg_id, 'cost_per_ad', $cost);
        
        LMB_Ad_Manager::log_activity(sprintf('Package "%s" %s', $name, $package_id ? 'updated' : 'created'));
        
        // --- ADDED: Return the full package data for dynamic updates ---
        wp_send_json_success([
            'message' => 'Package saved successfully.',
            'package' => [
                'id' => $new_pkg_id,
                'name' => $name,
                'price' => $price,
                'points' => $points,
                'cost_per_ad' => $cost,
                'description' => $desc,
                'trimmed_description' => wp_trim_words($desc, 20)
            ]
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
    
    // --- REVISED FUNCTION ---
    private static function lmb_get_balance_history() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(['message' => 'Invalid user ID'], 400);
        }
        
        // Always fetch the current balance and the transaction history separately
        $current_balance = LMB_Points::get_balance($user_id);
        $history = LMB_Points::get_transactions($user_id, 10);

        wp_send_json_success([
            'current_balance' => $current_balance,
            'history' => $history
        ]);
    }

    // --- REVISED FUNCTION ---
    private static function lmb_load_admin_tab() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : 'feed';
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $posts_per_page = 5;

        // --- Fetch counts for badges ---
        $pending_ads_query = new WP_Query(['post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_query' => [['key' => 'lmb_status', 'value' => 'pending_review']]]);
        $pending_payments_query = new WP_Query(['post_type' => 'lmb_payment', 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);

        ob_start();

        switch ($tab) {
            case 'pending_ads':
                $ads_paged_query = new WP_Query(['post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => $posts_per_page, 'paged' => $paged, 'meta_query' => [['key' => 'lmb_status', 'value' => 'pending_review']]]);
                if (!$ads_paged_query->have_posts()) {
                    echo '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No legal ads are pending approval.', 'lmb-core') . '</p></div>';
                } else {
                    echo '<div class="lmb-pending-ads-feed">';
                    while ($ads_paged_query->have_posts()) {
                        $ads_paged_query->the_post();
                        $ad = get_post();
                        $client = get_userdata($ad->post_author);
                        // --- ACTION BUTTONS REMOVED FROM THIS VIEW ---
                        echo '<div class="lmb-feed-item" data-id="' . $ad->ID . '"><div class="lmb-feed-content"><a href="' . get_edit_post_link($ad->ID) . '" class="lmb-feed-title" target="_blank">' . esc_html($ad->post_title) . '</a><div class="lmb-feed-meta"><i class="fas fa-user"></i> ' . ($client ? esc_html($client->display_name) : 'Unknown') . ' • <i class="fas fa-clock"></i> ' . human_time_diff(get_the_time('U', $ad->ID)) . ' ago</div></div></div>';
                    }
                    echo '</div>';
                }
                break;

            // ... (pending_payments and feed cases remain the same as the previous version) ...
            case 'pending_payments':
                $payments_paged_query = new WP_Query(['post_type' => 'lmb_payment', 'post_status' => 'publish', 'posts_per_page' => $posts_per_page, 'paged' => $paged, 'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]]);
                $posts = $payments_paged_query->posts;
                if (empty($posts)) {
                    echo '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No payments are pending verification.', 'lmb-core') . '</p></div>';
                } else {
                    echo '<div class="lmb-pending-payments-feed">';
                    foreach ($posts as $payment) {
                        $user = get_userdata(get_post_meta($payment->ID, 'user_id', true));
                        $proof_url = wp_get_attachment_url(get_post_meta($payment->ID, 'proof_attachment_id', true));
                        echo '<div class="lmb-feed-item" data-id="' . $payment->ID . '"><div class="lmb-feed-content"><a href="' . get_edit_post_link($payment->ID) . '" class="lmb-feed-title" target="_blank">' . esc_html($payment->post_title) . '</a><div class="lmb-feed-meta"><i class="fas fa-user"></i> ' . ($user ? esc_html($user->display_name) : 'Unknown') . '</div></div><div class="lmb-feed-actions">' . ($proof_url ? '<a href="'.esc_url($proof_url).'" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-paperclip"></i> Show Proof</a>' : '') . '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action" data-action="approve" data-id="' . $payment->ID . '"><i class="fas fa-check"></i> Approve</button><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action" data-action="reject" data-id="' . $payment->ID . '"><i class="fas fa-times"></i> Reject</button></div></div>';
                    }
                    echo '</div>';
                }
                break;
            
            case 'feed':
            default:
                $activity_log = get_option('lmb_activity_log', []);
                if (empty($activity_log)) {
                    echo '<div class="lmb-feed-empty"><i class="fas fa-stream"></i><p>' . __('No recent activity.', 'lmb-core') . '</p></div>';
                } else {
                    $total_items = count($activity_log);
                    $log_paged = array_slice($activity_log, ($paged - 1) * $posts_per_page, $posts_per_page);

                    echo '<div class="lmb-activity-feed">';
                    foreach ($log_paged as $entry) {
                        $user = $entry['user'] ? get_userdata($entry['user']) : null;
                        $user_name = $user ? $user->display_name : 'System';
                        $time_ago = human_time_diff(strtotime($entry['time'])) . ' ago';
                        echo '<div class="lmb-feed-item"><div class="lmb-feed-content"><div class="lmb-feed-title">' . esc_html($entry['msg']) . '</div><div class="lmb-feed-meta"><i class="fas fa-user"></i> ' . esc_html($user_name) . ' • <i class="fas fa-clock"></i> ' . $time_ago . '</div></div></div>';
                    }
                    echo '</div>';
                }
                break;
        }

        $content = ob_get_clean();

        // Generate pagination HTML
        $pagination_html = '';
        $total_pages = 1;
        if ($tab === 'pending_ads') $total_pages = $ads_paged_query->max_num_pages;
        if ($tab === 'pending_payments') $total_pages = $payments_paged_query->max_num_pages;
        if ($tab === 'feed') $total_pages = ceil($total_items / $posts_per_page);

        if ($total_pages > 1) {
            $pagination_html = paginate_links([
                'base' => '#%#%',
                'format' => '?paged=%#%',
                'current' => $paged,
                'total' => $total_pages,
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
            ]);
        }

        wp_send_json_success([
            'content' => $content,
            'pagination' => $pagination_html,
            'pending_ads_count' => (int) $pending_ads_query->found_posts,
            'pending_payments_count' => (int) $pending_payments_query->found_posts,
        ]);
    }

    // --- REVISED FUNCTION ---
    private static function lmb_search_user() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }
        $term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if (empty($term)) {
            wp_send_json_error(['message' => 'Search term is empty.'], 400);
        }

        $user_query = new WP_User_Query([
            'search' => '*' . esc_attr($term) . '*',
            'search_columns' => ['ID', 'user_login', 'user_email', 'display_name'],
            'number' => 10, // Limit to 10 results for performance
            'fields' => ['ID', 'display_name', 'user_email']
        ]);

        $users = $user_query->get_results();

        if (!empty($users)) {
            wp_send_json_success(['users' => $users]);
        } else {
            wp_send_json_error(['message' => 'No users found.'], 404);
        }
    }

    // --- REVISED FUNCTION ---
    private static function lmb_update_balance() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied'], 403);
        }
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount  = isset($_POST['amount']) ? absint($_POST['amount']) : 0;
        $reason  = !empty($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'Manual balance adjustment';
        $action  = isset($_POST['balance_action']) ? sanitize_key($_POST['balance_action']) : '';

        if (!$user_id || !$action) {
            wp_send_json_error(['message' => 'Missing user ID or action.'], 400);
        }
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

    // --- REVISED FUNCTION ---
    private static function lmb_generate_package_invoice() {
        if (!is_user_logged_in() || !isset($_POST['pkg_id'])) {
            wp_send_json_error(['message' => 'Invalid request.'], 403);
        }

        // The handler now calls our robust, centralized invoice creation method
        $pdf_url = LMB_Invoice_Handler::create_invoice_for_package(get_current_user_id(), intval($_POST['pkg_id']));
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Could not generate invoice. Please try again.']);
        }
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

    // --- REVISED FUNCTION ---
    private static function lmb_get_pending_accuse_ads() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access Denied.']);
        }
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $args = [
            'post_type' => 'lmb_legal_ad', 'posts_per_page' => 5, 'paged' => $paged,
            'meta_query' => [
                'relation' => 'AND',
                ['key' => 'lmb_status', 'value' => 'published'],
                ['key' => 'lmb_accuse_attachment_id', 'compare' => 'NOT EXISTS']
            ],
            'orderby' => 'date', 'order' => 'DESC'
        ];
        $query = new WP_Query($args);
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="lmb-accuse-pending-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $ad_id = get_the_ID();
                $client = get_userdata(get_post_field('post_author', $ad_id));
                // --- THIS HTML IS NOW A FORM FOR EACH ITEM ---
                echo '<form class="lmb-accuse-item lmb-accuse-upload-form" enctype="multipart/form-data">
                        <div class="lmb-accuse-info">
                            <strong>' . get_the_title() . '</strong> (ID: ' . $ad_id . ')<br>
                            <small>Client: ' . ($client ? esc_html($client->display_name) : 'N/A') . ' | Published: ' . get_the_date() . '</small>
                        </div>
                        <div class="lmb-accuse-actions">
                            <input type="file" name="accuse_file" class="lmb-file-input-accuse" required accept=".pdf,.jpg,.jpeg,.png">
                            <input type="hidden" name="legal_ad_id" value="' . $ad_id . '">
                            <button type="submit" class="lmb-btn lmb-btn-sm lmb-btn-primary">
                                <i class="fas fa-upload"></i> Upload
                            </button>
                        </div>
                      </form>';
            }
            echo '</div>';
            $total_pages = $query->max_num_pages;
            if ($total_pages > 1) {
                echo '<div class="lmb-pagination">' . paginate_links(['total' => $total_pages, 'current' => $paged, 'format' => '?paged=%#%', 'base' => '#%#%']) . '</div>';
            }
        } else {
            echo '<div class="lmb-empty-state"><i class="fas fa-check-circle fa-3x"></i><h4>All Caught Up!</h4><p>No published ads are waiting for an accuse document.</p></div>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        wp_send_json_success(['html' => $html]);
    }

    // --- NEW FUNCTION ---
    private static function lmb_attach_accuse_to_ad() {
        if (!current_user_can('manage_options') || !isset($_POST['ad_id'], $_POST['attachment_id'])) {
            wp_send_json_error(['message' => 'Permission denied or missing data.']);
        }

        $ad_id = intval($_POST['ad_id']);
        $attachment_id = intval($_POST['attachment_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => 'Invalid Ad ID.']);
        }
        
        // Link the attachment to the ad
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $ad_id);
        update_post_meta($ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        
        // Notify the user
        $client_id = $ad->post_author;
        if ($client_id && class_exists('LMB_Notification_Manager')) {
            $title = sprintf(__('Receipt for ad "%s" is ready', 'lmb-core'), get_the_title($ad_id));
            $msg = __('The official receipt (accuse) for your legal ad is now available for download from your dashboard.', 'lmb-core');
            LMB_Notification_Manager::add($client_id, 'receipt_ready', $title, $msg, ['ad_id' => $ad_id]);
        }
        
        wp_send_json_success(['message' => 'Accuse attached successfully! The client has been notified.']);
    }

    // --- REVISED FUNCTION ---
    private static function lmb_get_pending_invoices_form() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Not logged in.']);
        }

        $pending_payments = get_posts([
            'post_type' => 'lmb_payment',
            'post_status' => 'publish',
            'author' => get_current_user_id(),
            'posts_per_page' => -1,
            'meta_query' => [['key' => 'payment_status', 'value' => 'pending']]
        ]);

        ob_start();
        if (empty($pending_payments)) {
            echo '<div class="lmb-empty-state">
                    <i class="fas fa-check-circle fa-3x"></i>
                    <h4>' . esc_html__('No Pending Invoices', 'lmb-core') . '</h4>
                    <p>' . esc_html__('You have no invoices awaiting payment. To get one, please select a package from our pricing table.', 'lmb-core') . '</p>
                  </div>';
        } else {
            echo '<form id="lmb-upload-proof-form" class="lmb-form" enctype="multipart/form-data">
                    <div class="lmb-form-group">
                        <label for="payment_id"><i class="fas fa-file-invoice"></i> ' . esc_html__('Select Invoice to Pay','lmb-core') . '</label>
                        <select name="payment_id" id="payment_id" class="lmb-select" required>
                            <option value="">' . esc_html__('Select the invoice you paid...','lmb-core') . '</option>';
            foreach ($pending_payments as $payment) {
                $ref = get_post_meta($payment->ID, 'payment_reference', true);
                $price = get_post_meta($payment->ID, 'package_price', true);
                echo '<option value="' . esc_attr($payment->ID) . '">' . esc_html($ref) . ' (' . esc_html(get_the_title(get_post_meta($payment->ID, 'package_id', true))) . ' - ' . esc_html($price) . ' MAD)</option>';
            }
            echo '</select>
                    </div>
                    <div class="lmb-form-group">
                        <label for="proof_file"><i class="fas fa-paperclip"></i> ' . esc_html__('Proof of Payment File','lmb-core') . '</label>
                        <input type="file" name="proof_file" id="proof_file" class="lmb-input" accept="image/jpeg,image/png,application/pdf" required>
                        <small>' . esc_html__('Accepted formats: JPG, PNG, PDF. Maximum size: 5MB.','lmb-core') . '</small>
                    </div>
                    <div class="lmb-form-actions">
                        <button type="submit" class="lmb-btn lmb-btn-primary lmb-btn-large"><i class="fas fa-check-circle"></i> ' . esc_html__('Submit for Verification','lmb-core') . '</button>
                    </div>
                </form>';
        }
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    // --- NEW FUNCTION ---
    private static function lmb_generate_invoice_pdf() {
        if (!is_user_logged_in() || !isset($_POST['payment_id'])) {
            wp_send_json_error(['message' => 'Invalid request.'], 403);
        }

        $payment_id = intval($_POST['payment_id']);
        $payment_post = get_post($payment_id);

        // Security check: ensure the invoice belongs to the current user
        if (!$payment_post || $payment_post->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }

        // Call the existing PDF generation logic
        $pdf_url = LMB_Invoice_Handler::generate_invoice_pdf($payment_id);
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Could not generate PDF invoice.']);
        }
    }

    // Add this new function inside the LMB_Ajax_Handlers class
    private static function lmb_regenerate_ad_text() {
        if (!current_user_can('edit_posts') || !isset($_POST['post_id'])) {
            wp_send_json_error(['message' => 'Permission Denied.']);
        }

        $post_id = intval($_POST['post_id']);
        
        // This is your existing function to generate the text
        LMB_Form_Handler::generate_and_save_formatted_text($post_id);

        // Get the updated content to send back to the editor
        $post = get_post($post_id);
        
        wp_send_json_success(['new_content' => $post->post_content]);
    }

    // Add this new function inside the LMB_Ajax_Handlers class
    private static function lmb_admin_generate_pdf() {
        if (!current_user_can('edit_posts') || !isset($_POST['post_id'])) {
            wp_send_json_error(['message' => 'Permission Denied.']);
        }

        $post_id = intval($_POST['post_id']);

        // This is your existing function to generate the PDF
        $pdf_url = LMB_PDF_Generator::create_ad_pdf_from_fulltext($post_id);
        
        if ($pdf_url) {
            update_post_meta($post_id, 'ad_pdf_url', $pdf_url);
            wp_send_json_success(['message' => 'PDF generated successfully!', 'pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate PDF.']);
        }
    }
}