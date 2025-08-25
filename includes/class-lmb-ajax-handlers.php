<?php
if (!defined('ABSPATH')) exit;

class LMB_Ajax_Handlers {

    public static function init() {
        // Register all AJAX actions to a single handler
        $ajax_actions = [
            'lmb_ad_status_change',
            'lmb_user_submit_for_review',
            'lmb_payment_action',
            'lmb_get_balance_history',
            'lmb_load_admin_tab',
            'lmb_search_user',
            'lmb_update_balance',
            'lmb_generate_package_invoice',
            'lmb_get_notifications',
            'lmb_mark_notification_read',
            'lmb_mark_all_notifications_read',
            'lmb_save_package',
            'lmb_delete_package',
            'lmb_generate_receipt_pdf',
            'lmb_upload_accuse',
            // Added missing handlers
            'lmb_upload_newspaper',
            'lmb_upload_bank_proof',
            'lmb_fetch_users',
            'lmb_fetch_ads'
        ];

        foreach ($ajax_actions as $action) {
            add_action('wp_ajax_' . $action, [__CLASS__, 'handle_ajax_request']);
        }
    }

    /**
     * Central handler for all AJAX requests.
     */
    public static function handle_ajax_request() {
        check_ajax_referer('lmb_nonce', 'nonce'); 

        $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';

        if (method_exists(__CLASS__, $action)) {
            self::$action();
        } else {
            wp_send_json_error(['message' => 'Invalid AJAX action.'], 400);
        }
    }

    // --- User List AJAX Handler ---
    private static function lmb_fetch_users() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $args = ['number' => 10, 'paged' => $paged, 'orderby' => 'registered', 'order' => 'DESC'];

        if (!empty($_POST['search_name'])) $args['search'] = '*' . sanitize_text_field($_POST['search_name']) . '*';
        if (!empty($_POST['search_email'])) $args['search'] = '*' . sanitize_email($_POST['search_email']) . '*';
        if (!empty($_POST['search_id'])) $args['include'] = [intval($_POST['search_id'])];

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        ob_start();
        if (!empty($users)) {
            echo '<div class="lmb-table-container"><table class="lmb-data-table"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr></thead><tbody>';
            foreach ($users as $user) {
                echo '<tr><td>' . esc_html($user->ID) . '</td><td><strong>' . esc_html($user->display_name) . '</strong><br><small>@' . esc_html($user->user_login) . '</small></td><td>' . esc_html($user->user_email) . '</td><td>' . date_i18n(get_option('date_format'), strtotime($user->user_registered)) . '</td><td><a href="' . esc_url(get_edit_user_link($user->ID)) . '" class="lmb-btn lmb-btn-sm lmb-btn-secondary" target="_blank"><i class="fas fa-user-edit"></i> View</a></td></tr>';
            }
            echo '</tbody></table></div>';
            if ($total_users > 10) {
                echo '<div class="lmb-pagination">';
                echo paginate_links(['base' => '#%#%', 'format' => '', 'current' => $paged, 'total' => ceil($total_users / 10), 'prev_text' => '&laquo;', 'next_text' => '&raquo;']);
                echo '</div>';
            }
        } else {
            echo '<div class="lmb-no-results-container"><div class="lmb-empty-state"><h4>No Users Found</h4><p>No users matched your search criteria.</p></div></div>';
        }
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    // --- Ads List AJAX Handler ---
    private static function lmb_fetch_ads() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied'], 403);
        
        $paged = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $args = ['post_type' => 'lmb_legal_ad', 'post_status' => 'any', 'posts_per_page' => 10, 'paged' => $paged, 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => ['relation' => 'AND']];

        if (!empty($_POST['filter_ref'])) $args['s'] = sanitize_text_field($_POST['filter_ref']);
        if (!empty($_POST['filter_company'])) $args['meta_query'][] = ['key' => 'company_name', 'value' => sanitize_text_field($_POST['filter_company']), 'compare' => 'LIKE'];
        if (!empty($_POST['filter_ad_type'])) $args['meta_query'][] = ['key' => 'ad_type', 'value' => sanitize_text_field($_POST['filter_ad_type'])];
        if (!empty($_POST['filter_status'])) $args['meta_query'][] = ['key' => 'lmb_status', 'value' => sanitize_key($_POST['filter_status'])];
        if (!empty($_POST['filter_user'])) {
            $user_term = sanitize_text_field($_POST['filter_user']);
            if (is_numeric($user_term)) $args['author'] = intval($user_term);
            else {
                $user = get_user_by('login', $user_term) ?: get_user_by('email', $user_term);
                if ($user) $args['author'] = $user->ID;
            }
        }
        
        $query = new WP_Query($args);

        ob_start();
        if ($query->have_posts()) {
            echo '<div class="lmb-table-container"><table class="lmb-data-table"><thead><tr><th>ID</th><th>User</th><th>Company</th><th>Type</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
            while ($query->have_posts()) {
                $query->the_post();
                $ad_id = get_the_ID();
                $user = get_userdata(get_the_author_meta('ID'));
                $status = get_post_meta($ad_id, 'lmb_status', true);
                echo '<tr>';
                echo '<td><strong>#' . esc_html($ad_id) . '</strong></td>';
                echo '<td>' . ($user ? esc_html($user->display_name) : 'N/A') . '</td>';
                echo '<td>' . esc_html(get_post_meta($ad_id, 'company_name', true) ?: '-') . '</td>';
                echo '<td>' . esc_html(get_post_meta($ad_id, 'ad_type', true) ?: '-') . '</td>';
                echo '<td><span class="lmb-status-badge lmb-status-' . esc_attr($status) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span></td>';
                echo '<td>' . get_the_date() . '</td>';
                echo '<td><div class="lmb-table-actions">';
                echo '<a href="' . get_edit_post_link($ad_id) . '" class="lmb-btn lmb-btn-sm lmb-btn-secondary" target="_blank"><i class="fas fa-edit"></i> Edit</a>';
                if ($status === 'pending_review') {
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-ad-action" data-action="approve" data-id="' . $ad_id . '"><i class="fas fa-check"></i> Approve</button>';
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-ad-action" data-action="deny" data-id="' . $ad_id . '"><i class="fas fa-times"></i> Deny</button>';
                }
                echo '</div></td></tr>';
            }
            echo '</tbody></table></div>';
            if ($query->max_num_pages > 1) {
                echo '<div class="lmb-pagination">';
                echo paginate_links(['base' => '#%#%', 'format' => '', 'current' => $paged, 'total' => $query->max_num_pages, 'prev_text' => '&laquo;', 'next_text' => '&raquo;']);
                echo '</div>';
            }
        } else {
            echo '<div class="lmb-no-results-container"><div class="lmb-empty-state"><h4>No Ads Found</h4><p>No legal ads matched your search criteria.</p></div></div>';
        }
        wp_reset_postdata();
        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    // --- Upload Handlers ---
    private static function lmb_upload_newspaper() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission denied.']);
        LMB_Upload_Newspaper_Widget::handle_ajax_upload();
    }

    private static function lmb_upload_bank_proof() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'You must be logged in.']);
        LMB_Upload_Bank_Proof_Widget::handle_ajax_upload();
    }
    
    // --- All Other Handlers (Copied from your provided file) ---

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
        LMB_Ad_Manager::log_activity(sprintf('Ad #%d submitted for review by %s.', $ad->post_title, wp_get_current_user()->display_name));
        LMB_Notification_Manager::notify_admins_ad_pending($ad_id);
        wp_send_json_success(['message' => 'Ad submitted successfully.']);
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
        $reason  = isset($_POST['reason']) && !empty($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'Manual balance adjustment';
        $action  = isset($_POST['balance_action']) ? sanitize_key($_POST['balance_action']) : '';
        
        if (!$user_id || !$action) wp_send_json_error(['message' => 'Missing user ID or action.'], 400);

        switch ($action) {
            case 'add': $new_balance = LMB_Points::add($user_id, $amount, $reason); break;
            case 'subtract': $new_balance = LMB_Points::deduct($user_id, $amount, $reason); break;
            case 'set': $new_balance = LMB_Points::set_balance($user_id, $amount, $reason); break;
            default: wp_send_json_error(['message' => 'Invalid action.'], 400);
        }

        if ($new_balance === false) wp_send_json_error(['message' => 'Insufficient balance for subtraction.'], 400);
        wp_send_json_success(['message' => 'Balance updated successfully!', 'new_balance' => $new_balance]);
    }

    private static function lmb_generate_package_invoice() {
        if (!is_user_logged_in() || !isset($_POST['pkg_id'])) wp_send_json_error(['message' => 'Invalid request.']);
        $pdf_url = LMB_Invoice_Handler::generate_package_invoice_pdf_for_user(get_current_user_id(), intval($_POST['pkg_id']));
        if ($pdf_url) wp_send_json_success(['pdf_url' => $pdf_url]);
        else wp_send_json_error(['message' => 'Could not generate PDF invoice.']);
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

    private static function lmb_save_package() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Access denied']);
        $pkg_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $cost = intval($_POST['cost_per_ad']);
        $desc = sanitize_textarea_field($_POST['description']);
        if (!$name || !$price || !$points || !$cost) wp_send_json_error(['message' => 'All fields are required']);

        $post_data = ['post_title' => $name, 'post_content' => $desc, 'post_type' => 'lmb_package', 'post_status' => 'publish'];
        $result = $pkg_id ? wp_update_post(array_merge(['ID' => $pkg_id], $post_data)) : wp_insert_post($post_data);
        if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);
        
        $new_pkg_id = $pkg_id ?: $result;
        update_post_meta($new_pkg_id, 'price', $price);
        update_post_meta($new_pkg_id, 'points', $points);
        update_post_meta($new_pkg_id, 'cost_per_ad', $cost);
        
        LMB_Ad_Manager::log_activity(sprintf('Package "%s" %s', $name, $pkg_id ? 'updated' : 'created'));
        wp_send_json_success(['package_id' => $new_pkg_id, 'message' => 'Package saved successfully.']);
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
    
    private static function lmb_generate_receipt_pdf() {
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Access denied']);
        $ad_id = intval($_POST['ad_id']);
        $ad = get_post($ad_id);
        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != get_current_user_id()) wp_send_json_error(['message' => 'Ad not found or access denied']);
        
        $pdf_url = LMB_Receipt_Generator::create_receipt_pdf($ad_id, get_post_meta($ad_id, 'ad_type', true));
        if ($pdf_url) wp_send_json_success(['pdf_url' => $pdf_url]);
        else wp_send_json_error(['message' => 'Failed to generate PDF']);
    }

    private static function lmb_upload_accuse() {
        if (!current_user_can('manage_options') || !isset($_POST['legal_ad_id']) || empty($_FILES['accuse_file']['name'])) {
            wp_send_json_error(['message' => 'Missing required information.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $legal_ad_id = intval($_POST['legal_ad_id']);
        $accuse_date = sanitize_text_field($_POST['accuse_date']);
        $notes = sanitize_textarea_field($_POST['accuse_notes']);
        $file = $_FILES['accuse_file'];

        $filetype = wp_check_filetype($file['name']);
        if (!in_array($filetype['ext'], ['pdf', 'jpg', 'jpeg', 'png'])) {
            wp_send_json_error(['message' => 'Invalid file type. Please upload a PDF, JPG, or PNG file.']);
        }
        
        $attachment_id = media_handle_upload('accuse_file', 0);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }

        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $legal_ad_id);
        update_post_meta($attachment_id, 'lmb_accuse_date', $accuse_date);
        update_post_meta($attachment_id, 'lmb_accuse_notes', $notes);
        
        LMB_Ad_Manager::log_activity(sprintf('Accuse uploaded for Ad #%d', $legal_ad_id));
        wp_send_json_success(['message' => 'Accuse uploaded and saved successfully.']);
    }
}