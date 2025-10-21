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
            'lmb_discard_newspaper_draft', 'lmb_manipulate_balance',
            'lmb_admin_subscribe_user_to_package',
            'lmb_update_ad_date', 'lmb_associate_final_newspaper', 'lmb_fetch_eligible_ads_for_newspaper',
            'lmb_clean_ad_association', 'lmb_trigger_manual_cleanup',
            
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
        $action = isset($_POST['action']) ? sanitize_key($_POST['action']) : '';

        // Actions that use custom nonces or are public bypass the default nonce check
        $custom_nonce_actions = ['lmb_update_ad_date'];
        $public_actions = ['lmb_login_v2', 'lmb_signup_v2', 'lmb_fetch_public_ads', 'lmb_fetch_newspapers_v2'];
        
        if (!in_array($action, array_merge($custom_nonce_actions, $public_actions))) {
            // This is the default nonce check from the original file
            check_ajax_referer('lmb_nonce', 'nonce');
        }

        if (!in_array($action, $public_actions) && !is_user_logged_in()) {
            wp_send_json_error(['message' => 'Vous devez être connecté.'], 403);
            return;
        }

        // --- ADDED SECURITY for admin-only actions ---
        $admin_only_actions = [
            'lmb_fetch_inactive_clients_v2',
            'lmb_manage_inactive_client_v2',
            'lmb_fetch_active_clients_v2', 'lmb_lock_active_client_v2',
            'lmb_admin_subscribe_user_to_package', 'lmb_trigger_manual_cleanup',
        ];
        if (in_array($action, $admin_only_actions) && !current_user_can('manage_options')) {
             wp_send_json_error(['message' => 'Vous n\'avez pas la permission d\'effectuer cette action.'], 403);
            return;
        }

        if (method_exists(__CLASS__, $action)) {
            self::$action();
        } else {
            wp_send_json_error(['message' => 'Action AJAX non valide spécifiée.'], 400);
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
            wp_send_json_error(['message' => 'Permission refusée.']);
        }
        if (empty($_POST['legal_ad_id']) || empty($_FILES['accuse_file'])) {
            wp_send_json_error(['message' => 'Champs requis manquants : L\'ID de l\'annonce et le fichier sont obligatoires.']);
        }

        $ad_id = intval($_POST['legal_ad_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => 'ID d\'annonce non valide. Aucune annonce légale trouvée avec cet ID.']);
        }
        if (get_post_meta($ad_id, 'lmb_status', true) !== 'published') {
            wp_send_json_error(['message' => 'Cette annonce n\'est pas publiée. Vous ne pouvez télécharger un accusé que pour les annonces publiées.']);
        }
        if (get_post_meta($ad_id, 'lmb_accuse_attachment_id', true)) {
            wp_send_json_error(['message' => 'Un accusé a déjà été téléchargé pour cette annonce.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $attachment_id = media_handle_upload('accuse_file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'Erreur de téléchargement du fichier : ' . $attachment_id->get_error_message()]);
        }

        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $ad_id);
        update_post_meta($ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        
        $client_id = $ad->post_author;
        if ($client_id && class_exists('LMB_Notification_Manager')) {
            $title = sprintf(__('Receipt for ad "%s" is ready', 'lmb-core'), get_the_title($ad_id));
            $msg = __('The official receipt (accuse) for your legal ad is now available for download from your dashboard.', 'lmb-core');
            LMB_Notification_Manager::add($client_id, 'receipt_ready', $title, $msg, ['ad_id' => $ad_id]);
        }
        
        wp_send_json_success(['message' => 'Accusé téléchargé et joint avec succès. Le client a été notifié.']);
    }

    private static function lmb_fetch_ads() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Accès refusé.']);
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
                    echo '<button class="lmb-btn lmb-btn-sm lmb-ad-action" data-action="approve" data-id="'.$post_id.'">Approuver</button><button class="lmb-btn lmb-btn-sm lmb-ad-action" data-action="deny" data-id="'.$post_id.'">Refuser</button>';
                } else { echo '—'; }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
            
            if ($query->max_num_pages > 1) {
                echo '<div class="lmb-pagination">' . paginate_links(['total' => $query->max_num_pages, 'current' => $paged, 'format' => '?paged=%#%', 'base' => '#%#%']) . '</div>';
            }
        } else {
            echo '<div class="lmb-no-results">Aucune annonce trouvée correspondant à vos critères.</div>';
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
            echo '<table class="lmb-users-table"><thead><tr><th>ID</th><th>Nom</th><th>Email</th><th>Solde</th><th>Actions</th></tr></thead><tbody>';
            foreach ($user_query->get_results() as $user) {
                echo '<tr><td>' . esc_html($user->ID) . '</td><td>' . esc_html($user->display_name) . '</td><td>' . esc_html($user->user_email) . '</td><td>' . LMB_Points::get_balance($user->ID) . '</td><td><a href="' . get_edit_user_link($user->ID) . '" target="_blank" class="lmb-btn lmb-btn-sm">Modifier</a></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<div class="lmb-no-results">Aucun utilisateur trouvé.</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    private static function lmb_upload_bank_proof() {
        if (empty($_POST['payment_id']) || empty($_FILES['proof_file'])) {
            wp_send_json_error(['message' => 'Champs requis manquants. Veuillez sélectionner une facture et un justificatif de paiement.']);
        }
        
        $user_id = get_current_user_id();
        $payment_id = intval($_POST['payment_id']);
        $payment_post = get_post($payment_id);

        if (!$payment_post || $payment_post->post_author != $user_id) {
            wp_send_json_error(['message' => 'Facture sélectionnée non valide.']);
        }

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $attachment_id = media_handle_upload('proof_file', $payment_id);
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => 'Erreur de téléchargement du fichier : ' . $attachment_id->get_error_message()]);
        }

        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        LMB_Ad_Manager::log_activity(sprintf('Justificatif de paiement pour la facture #%d soumis.', $payment_id));
        
        if(class_exists('LMB_Notification_Manager')) {
            $user = wp_get_current_user();
            $package_id = get_post_meta($payment_id, 'package_id', true);
            $title = 'Nouveau justificatif de paiement soumis';
            $msg = sprintf('L\'utilisateur %s a soumis un justificatif pour la facture #%s ("%s").', $user->display_name, get_post_meta($payment_id, 'payment_reference', true), get_the_title($package_id));
            
            $admin_ids = get_users(['role' => 'administrator', 'fields' => 'ID']);
            foreach ($admin_ids as $admin_id) {
                LMB_Notification_Manager::add($admin_id, 'proof_submitted', $title, $msg, ['ad_id' => $payment_id]);
            }
        }

        wp_send_json_success(['message' => 'Votre justificatif a été soumis pour examen. Vous serez averti dès son approbation.']);
    }

    private static function lmb_save_package() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé']);

        $package_id = isset($_POST['package_id']) && !empty($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $cost = intval($_POST['cost_per_ad']);
        $desc = wp_kses_post($_POST['description']);
        $client_visible = isset($_POST['client_visible']) && $_POST['client_visible'] === '1'; // <-- GET NEW VALUE

        if (!$name || !$price || !$points || !$cost) {
            wp_send_json_error(['message' => 'Tous les champs sauf la description sont obligatoires.']);
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
        update_post_meta($new_pkg_id, 'client_visible', $client_visible); // <-- SAVE NEW VALUE

        LMB_Ad_Manager::log_activity(sprintf('Forfait "%s" %s', $name, $package_id ? 'mis à jour' : 'créé'));

        // <-- MODIFIED RESPONSE TO INCLUDE NEW DATA
        wp_send_json_success([
            'message' => 'Forfait enregistré avec succès.',
            'package' => [
                'id' => $new_pkg_id,
                'name' => $name,
                'price' => $price,
                'points' => $points,
                'cost_per_ad' => $cost,
                'description' => $desc,
                'trimmed_description' => wp_trim_words($desc, 20),
                'client_visible' => $client_visible
            ]
        ]);
    }
    
    private static function lmb_load_admin_tab() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé'], 403);
        
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
                    <?php if ($proof_url): ?><a href="<?php echo esc_url($proof_url); ?>" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-paperclip"></i> Voir Justificatif</a><?php endif; ?>
                    <button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action" data-action="approve" data-id="<?php echo $payment->ID; ?>"><i class="fas fa-check"></i> Approuver</button>
                    <button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action" data-action="reject" data-id="<?php echo $payment->ID; ?>"><i class="fas fa-times"></i> Rejeter</button>
                </div>
            </div>
            <?php
        }
        return ob_get_clean();
    }


    private static function lmb_search_user() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé'], 403);
        
        $term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        if (empty($term)) wp_send_json_error(['message' => 'Le terme de recherche est vide.'], 400);

        $user_query = new WP_User_Query(['search' => '*' . esc_attr($term) . '*', 'search_columns' => ['ID', 'user_login', 'user_email', 'display_name'], 'number' => 10, 'fields' => ['ID', 'display_name', 'user_email']]);
        
        if (!empty($user_query->get_results())) {
            wp_send_json_success(['users' => $user_query->get_results()]);
        } else {
            wp_send_json_error(['message' => 'Aucun utilisateur trouvé.'], 404);
        }
    }

    private static function lmb_update_balance() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé'], 403);
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount  = isset($_POST['amount']) ? absint($_POST['amount']) : 0;
        $reason  = !empty($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'Ajustement manuel du solde';
        $action  = isset($_POST['balance_action']) ? sanitize_key($_POST['balance_action']) : '';

        if (!$user_id || !$action) wp_send_json_error(['message' => 'ID utilisateur ou action manquant.'], 400);
        
        switch ($action) {
            case 'add': $new_balance = LMB_Points::add($user_id, $amount, $reason); break;
            case 'subtract': $new_balance = LMB_Points::deduct($user_id, $amount, $reason); break;
            case 'set': $new_balance = LMB_Points::set_balance($user_id, $amount, $reason); break;
            default: wp_send_json_error(['message' => 'Action non valide.'], 400);
        }
        
        if ($new_balance === false) {
            wp_send_json_error(['message' => 'Solde insuffisant pour cette opération.'], 400);
        }
        wp_send_json_success(['message' => 'Solde mis à jour avec succès!', 'new_balance' => $new_balance]);
    }

    private static function lmb_generate_package_invoice() {
        if (!is_user_logged_in() || !isset($_POST['pkg_id'])) wp_send_json_error(['message' => 'Requête invalide.'], 403);

        $pdf_url = LMB_Invoice_Handler::create_invoice_for_package(get_current_user_id(), intval($_POST['pkg_id']));
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Impossible de générer la facture. Veuillez réessayer.']);
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
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé']);
        
        $pkg_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        if (!$pkg_id) wp_send_json_error(['message' => 'ID de forfait non valide']);
        
        $package = get_post($pkg_id);
        if (!$package || $package->post_type !== 'lmb_package') wp_send_json_error(['message' => 'Forfait non trouvé']);
        
        if (!wp_delete_post($pkg_id, true)) wp_send_json_error(['message' => 'Échec de la suppression du forfait']);
        
        LMB_Ad_Manager::log_activity(sprintf('Forfait "%s" supprimé', $package->post_title));
        wp_send_json_success(['message' => 'Forfait supprimé avec succès.']);
    }

    private static function lmb_ad_status_change() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission refusée.']);
        
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad_action = isset($_POST['ad_action']) ? sanitize_key($_POST['ad_action']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$ad_id || !$ad_action) wp_send_json_error(['message' => 'Paramètres manquants.'], 400);

        if ($ad_action === 'approve') {
            $result = LMB_Ad_Manager::approve_ad($ad_id);
            if ($result['success']) wp_send_json_success(['message' => $result['message']]);
            else wp_send_json_error(['message' => $result['message']]);
        } elseif ($ad_action === 'deny') {
            LMB_Ad_Manager::deny_ad($ad_id, $reason);
            wp_send_json_success(['message' => 'L\'annonce a été refusée.']);
        }
    }
    
    private static function lmb_user_submit_for_review() {
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad = get_post($ad_id);
        
        if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission refusée.']);
        }

        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        LMB_Ad_Manager::log_activity(sprintf('Ad #%d ("%s") soumis pour examen.', $ad_id, $ad->post_title));
        LMB_Notification_Manager::notify_admins_ad_pending($ad_id);
        wp_send_json_success(['message' => 'Annonce soumise pour examen.']);
    }

    private static function lmb_payment_action() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission refusée.']);
        
        $payment_id = isset($_POST['payment_id']) ? intval($_POST['payment_id']) : 0;
        $action = isset($_POST['payment_action']) ? sanitize_key($_POST['payment_action']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : 'Aucune raison fournie.';
        LMB_Payment_Verifier::handle_payment_action($payment_id, $action, $reason);
    }
    
    private static function lmb_get_balance_history() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé'], 403);
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) wp_send_json_error(['message' => 'ID utilisateur non valide'], 400);
        
        wp_send_json_success([
            'current_balance' => LMB_Points::get_balance($user_id),
            'history' => LMB_Points::get_transactions($user_id, 10)
        ]);
    }

    private static function lmb_get_pending_accuse_ads() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé.']);
        
        $paged = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $query = new WP_Query(['post_type' => 'lmb_legal_ad', 'posts_per_page' => 5, 'paged' => $paged, 'meta_query' => ['relation' => 'AND', ['key' => 'lmb_status', 'value' => 'published'], ['key' => 'lmb_accuse_attachment_id', 'compare' => 'NOT EXISTS']], 'orderby' => 'date', 'order' => 'DESC']);
        
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="lmb-accuse-pending-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $ad_id = get_the_ID();
                $client = get_userdata(get_post_field('post_author', $ad_id));
                echo '<form class="lmb-accuse-item lmb-accuse-upload-form" enctype="multipart/form-data"><div class="lmb-accuse-info"><strong>' . get_the_title() . '</strong> (ID: ' . $ad_id . ')<br><small>Client: ' . ($client ? esc_html($client->display_name) : 'N/A') . ' | Publiée: ' . get_the_date() . '</small></div><div class="lmb-accuse-actions"><input type="file" name="accuse_file" class="lmb-file-input-accuse" required accept=".pdf,.jpg,.jpeg,.png"><input type="hidden" name="legal_ad_id" value="' . $ad_id . '"><button type="submit" class="lmb-btn lmb-btn-sm lmb-btn-primary"><i class="fas fa-upload"></i> Télécharger</button></div></form>';
            }
            echo '</div>';
            if ($query->max_num_pages > 1) {
                echo '<div class="lmb-pagination">' . paginate_links(['total' => $query->max_num_pages, 'current' => $paged, 'format' => '?paged=%#%', 'base' => '#%#%']) . '</div>';
            }
        } else {
            echo '<div class="lmb-empty-state"><i class="fas fa-check-circle fa-3x"></i><h4>Tout est à jour !</h4><p>Aucune annonce publiée n\'attend de document d\'accusé.</p></div>';
        }
        wp_reset_postdata();
        wp_send_json_success(['html' => ob_get_clean()]);
    }

    private static function lmb_attach_accuse_to_ad() {
        if (!current_user_can('manage_options') || !isset($_POST['ad_id'], $_POST['attachment_id'])) wp_send_json_error(['message' => 'Permission refusée ou données manquantes.']);

        $ad_id = intval($_POST['ad_id']);
        $attachment_id = intval($_POST['attachment_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad') wp_send_json_error(['message' => 'ID d\'annonce non valide.']);
        
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $ad_id);
        update_post_meta($ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        
        $client_id = $ad->post_author;
        if ($client_id && class_exists('LMB_Notification_Manager')) {
            $title = sprintf(__('Receipt for ad "%s" is ready', 'lmb-core'), get_the_title($ad_id));
            $msg = __('The official receipt (accuse) for your legal ad is now available for download from your dashboard.', 'lmb-core');
            LMB_Notification_Manager::add($client_id, 'receipt_ready', $title, $msg, ['ad_id' => $ad_id]);
        }
        
        wp_send_json_success(['message' => 'Accusé joint avec succès ! Le client a été notifié.']);
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
            wp_send_json_error(['message' => 'Permission refusée.'], 403);
        }

        $pdf_url = LMB_Invoice_Handler::generate_invoice_pdf($payment_id);
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Impossible de générer la facture PDF.']);
        }
    }

    private static function lmb_regenerate_ad_text() {
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission refusée.']);
        
        $post_id = intval($_POST['post_id']);
        
        // This function explicitly calls the handler to regenerate the text.
        // This is the action that applies the date change to the 'full_text' field.
        LMB_Form_Handler::generate_and_save_formatted_text($post_id);
        
        // Return the new content, which should include the new date.
        wp_send_json_success(['new_content' => get_post($post_id)->post_content]);
    }

    private static function lmb_admin_generate_pdf() {
        if (!current_user_can('edit_posts')) wp_send_json_error(['message' => 'Permission refusée.']);
        
        $post_id = intval($_POST['post_id']);
        $pdf_url = LMB_PDF_Generator::create_ad_pdf_from_fulltext($post_id);
        
        if ($pdf_url) {
            update_post_meta($post_id, 'ad_pdf_url', $pdf_url);
            wp_send_json_success(['message' => 'PDF généré avec succès !', 'pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Échec de la génération du PDF.']);
        }
    }

    // Enhanced ad fetching with multiple filters
    private static function lmb_fetch_ads_v2() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Accès refusé.'], 403);
        }

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        parse_str($_POST['filters'] ?? '', $filters);

        // --- (The WP_Query arguments are unchanged) ---
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
                
                // =========================================================================
                // --- START: FINAL CORRECTED CLIENT NAME LOGIC ---
                // =========================================================================
                $client_author_id = get_post_field('post_author', $post_id);
                $client = get_userdata($client_author_id); // Get the user object
                
                // Check if the client exists before getting the name
                if ($client) {
                    $client_name_to_display = LMB_User::get_client_display_name($client);
                } else {
                    $client_name_to_display = 'N/A'; // Fallback for ads with no author
                }
                // =========================================================================
                // --- END: FINAL CORRECTED CLIENT NAME LOGIC ---
                // =========================================================================
                
                $approved_by_id = get_post_meta($post_id, 'approved_by', true);
                $approved_by = $approved_by_id ? get_userdata($approved_by_id) : null;

                if (!$approved_by && $status === 'published') {
                    if ($client && in_array('administrator', (array)$client->roles)) {
                        $approved_by = $client;
                    }
                }
                
                $accuse_url = get_post_meta($post_id, 'lmb_accuse_pdf_url', true);
                $journal_display = '<span class="lamv2-cell-placeholder">-</span>';
                $final_journal_id = get_post_meta($post_id, 'lmb_final_journal_id', true);
                $temp_journal_id = get_post_meta($post_id, 'lmb_temporary_journal_id', true);

                if ($final_journal_id) {
                    $journal_title = get_the_title($final_journal_id);
                    $journal_url = wp_get_attachment_url(get_post_meta($final_journal_id, 'newspaper_pdf', true));
                    if ($journal_url) {
                       $journal_display = '<a href="' . esc_url($journal_url) . '" target="_blank" class="lamv2-btn lamv2-btn-sm lamv2-btn-text-link">' . esc_html($journal_title) . '</a>';
                    }
                } elseif ($temp_journal_id) {
                    $journal_title = get_the_title($temp_journal_id);
                    $journal_url = wp_get_attachment_url($temp_journal_id);
                     if ($journal_url) {
                        $journal_display = '<a href="' . esc_url($journal_url) . '" target="_blank" class="lamv2-btn lamv2-btn-sm lamv2-btn-text-link">' . esc_html($journal_title) . '</a>';
                    }
                }

                echo '<tr class="lamv2-clickable-row" data-href="' . esc_url(get_edit_post_link($post_id)) . '">';
                
                // --- Checkbox Column ---
                echo '<td class="lamv2-checkbox-col"><input type="checkbox" class="lamv2-ad-checkbox" data-id="' . esc_attr($post_id) . '"></td>';
                // --- End Checkbox Column ---

                echo '<td>' . esc_html($post_id) . '</td>';
                echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                echo '<td>' . get_the_date('d-m-Y') . '</td>';
                echo '<td>' . $client_name_to_display . '</td>'; // This is now fixed
                echo '<td><span class="lamv2-status-badge lamv2-status-' . esc_attr($status) . '">' . esc_html(ucwords(str_replace('_', ' ', $status))) . '</span></td>';
                echo '<td>' . ($approved_by ? esc_html($approved_by->display_name) : '<span class="lamv2-cell-placeholder">N/A</span>') . '</td>';
                echo '<td>';
                if ($accuse_url) {
                    echo '<a href="' . esc_url($accuse_url) . '" target="_blank" class="lamv2-btn lamv2-btn-sm lamv2-btn-text-link">Voir</a>';
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
                        echo '<button class="lamv2-btn lamv2-btn-icon lamv2-btn-success lamv2-ad-action" data-action="approve" data-id="' . $post_id . '" title="Approuver"><i class="fas fa-check-circle"></i></button>';
                        echo '<button class="lamv2-btn lamv2-btn-icon lamv2-btn-danger lamv2-ad-action" data-action="deny" data-id="' . $post_id . '" title="Refuser"><i class="fas fa-times-circle"></i></button>';
                    } elseif ($status === 'published') {
                        echo '<button class="lamv2-btn lamv2-btn-sm lamv2-btn-secondary lmb-upload-journal-btn" data-id="' . $post_id . '" title="Télécharger Journal Temporaire"><i class="fas fa-newspaper"></i></button>';
                    } else {
                        echo '<a href="' . esc_url(get_edit_post_link($post_id)) . '" class="lamv2-btn lamv2-btn-sm lamv2-btn-view">Voir</a>';
                    }
                }
                echo '<button class="lamv2-btn lamv2-btn-icon lamv2-btn-danger lmb-clean-ad-btn" data-action="clean" data-id="' . $post_id . '" title="Nettoyer l\'association Journal"><i class="fas fa-broom"></i></button>';
                echo '</td>';
                echo '</tr>';
            }
        } else {
            // FIX: Colspan changed to 11 to match the new table structure.
            echo '<tr><td colspan="11" style="text-align:center;">Aucune annonce trouvée correspondant à vos critères.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();

        $pagination_html = paginate_links([
            'base' => add_query_arg('paged', '%#%'), 'format' => '',
            'current' => $paged, 'total' => $query->max_num_pages,
            'prev_text' => '&laquo;', 'next_text' => '&raquo;',
            'add_args' => false
        ]);
        
        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }
    
    // --- NEW FUNCTION: v2 submit draft ad for review ---
    private static function lmb_submit_draft_ad_v2() {
        if (!isset($_POST['ad_id']) || !is_numeric($_POST['ad_id'])) {
            wp_send_json_error(['message' => 'ID d\'annonce non valide.'], 400);
            return;
        }

        $ad_id = intval($_POST['ad_id']);
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => 'Annonce non trouvée.'], 404);
            return;
        }
        
        if ($ad->post_author != get_current_user_id() && !current_user_can('edit_others_posts')) {
             wp_send_json_error(['message' => 'Vous n\'avez pas la permission de modifier cette annonce.'], 403);
            return;
        }
        
        // --- NEW BALANCE CHECK ---
        $user_id = $ad->post_author;
        $current_balance = (int) LMB_Points::get_balance($user_id);
        $ad_cost = (int) LMB_Points::get_cost_per_ad($user_id); 

        // CRITICAL CHECK: Ensure cost is greater than zero to prevent 0 < 0 from failing the check
        if ($ad_cost <= 0) {
            // This suggests a missing package setting for the user/system. Block until fixed.
            $error_message = "Vous ne disposez d’aucun point disponible. Merci de recharger votre solde afin de pouvoir publier votre annonce. Contactez-nous pour plus d'informations via email ste.lmbgroup@gmail.com ou whatsapp 0674406197";
            wp_send_json_error(['message' => $error_message], 500);
            return;
        }

        if ($current_balance < $ad_cost) {
            $error_message = 'Vous n\'avez pas assez de solde, veuillez contacter l\'administrateur pour plus d\'instructions. ste.lmbgroupe@gmail.com ou 0674406197';
            // Correctly block submission and return the error message.
            wp_send_json_error(['message' => $error_message], 402);
            return;
        }
        
        // If the balance is sufficient, proceed with submission.
        update_post_meta($ad_id, 'lmb_status', 'pending_review');
        
        // FIX: Replaced outdated/incorrect object-oriented call with the established static method
        if (class_exists('LMB_Notification_Manager')) {
            LMB_Notification_Manager::notify_admins_ad_pending($ad_id);
        }

        wp_send_json_success(['message' => 'Annonce soumise pour révision avec succès.']);
    }

    // --- NEW FUNCTION: v2 delete draft ad ---
    private static function lmb_delete_draft_ad_v2() {
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $ad = get_post($ad_id);

        if (!$ad || $ad->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => 'Permission refusée.'], 403);
            return;
        }

        if (wp_delete_post($ad_id, true)) {
            wp_send_json_success(['message' => 'Brouillon supprimé avec succès.']);
        } else {
            wp_send_json_error(['message' => 'Échec de la suppression du brouillon.']);
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
                echo '<div style="text-align: center; padding: 20px;">Aucune activité globale pour le moment.</div>';
            } else {
                foreach ($log_paged as $entry) {
                    $user = $entry['user'] ? get_userdata($entry['user']) : null;
                    $user_name = $user ? $user->display_name : 'Système';
                    $time_ago = human_time_diff(strtotime($entry['time'])) . ' il y a';
                    
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
                            <p class="feed-time"><?php echo esc_html($time_ago); ?> par <strong><?php echo esc_html($user_name); ?></strong></p>
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
                echo '<div style="text-align: center; padding: 20px;">Vous n\'avez aucune activité récente.</div>';
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
            wp_send_json_error(['message' => 'Nom d\'utilisateur ou mot de passe non valide.'], 401);
            return;
        }
        
        // Check if user is active
        $status = get_user_meta($user->ID, 'lmb_user_status', true);
        if ($status === 'inactive') {
            wp_logout();
            wp_send_json_error(['message' => 'Votre compte est en attente d\'approbation par l\'administrateur.'], 403);
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
        if (!is_email($email)) wp_send_json_error(['message' => 'Adresse e-mail non valide.']);
        if (email_exists($email)) wp_send_json_error(['message' => 'Cet e-mail est déjà enregistré.']);
        if (strlen($password) < 6) wp_send_json_error(['message' => 'Le mot de passe doit contenir au moins 6 caractères.']);

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
        
        // --- START: NEW DISPLAY NAME LOGIC ---
        $update_args = ['ID' => $user_id];
        if ($type === 'regular') {
            $first_name = sanitize_text_field($data['first_name']);
            $last_name = sanitize_text_field($data['last_name']);
            $update_args['first_name'] = $first_name;
            $update_args['last_name'] = $last_name;
            $update_args['display_name'] = trim($first_name . ' ' . $last_name);
            
            update_user_meta($user_id, 'phone_number', sanitize_text_field($data['phone_regular']));
            update_user_meta($user_id, 'city', sanitize_text_field($data['city_regular']));
        } else { // Professional
            $company_name = sanitize_text_field($data['company_name']);
            $update_args['display_name'] = $company_name;
            
            update_user_meta($user_id, 'company_name', $company_name);
            update_user_meta($user_id, 'company_hq', sanitize_text_field($data['company_hq']));
            update_user_meta($user_id, 'city', sanitize_text_field($data['city_professional']));
            update_user_meta($user_id, 'company_rc', sanitize_text_field($data['company_rc']));
            update_user_meta($user_id, 'phone_number', sanitize_text_field($data['phone_professional']));
        }
        
        // Set the core display name
        wp_update_user($update_args);
        // --- END: NEW DISPLAY NAME LOGIC ---

        // Notify admins of new registration
        LMB_Notification_Manager::add(1, 'new_user', 'Nouvelle Inscription Utilisateur', "Un nouvel utilisateur ($email) s'est inscrit et nécessite une approbation.");
        
        wp_send_json_success();
    }

    // --- REVISED FUNCTION: v2 fetch inactive clients with search, pagination, and approve/deny actions ---
    private static function lmb_fetch_inactive_clients_v2() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Accès refusé.'], 403);
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
                            echo '<div><i class="fas fa-calendar-alt"></i> Inscrit:<strong>' . human_time_diff(strtotime($user->user_registered)) . ' il y a</strong></div>';
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
            echo '<div style="text-align:center; padding: 20px;">Aucun client inactif trouvé.</div>';
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
            wp_send_json_error(['message' => 'Paramètres manquants.'], 400);
        }

        if ($approval_action === 'approve') {
            update_user_meta($user_id, 'lmb_user_status', 'active');
            // Optionally, send a welcome email
            wp_new_user_notification($user_id, null, 'user');
            LMB_Ad_Manager::log_activity(sprintf('Client #%d approuvé.', $user_id));
            wp_send_json_success(['message' => 'Client approuvé.']);
        } elseif ($approval_action === 'deny') {
            require_once(ABSPATH.'wp-admin/includes/user.php');
            if (wp_delete_user($user_id)) {
                LMB_Ad_Manager::log_activity(sprintf('Client #%d refusé et supprimé.', $user_id));
                wp_send_json_success(['message' => 'Client refusé et supprimé.']);
            } else {
                wp_send_json_error(['message' => 'Impossible de supprimer l\'utilisateur.']);
            }
        } else {
            wp_send_json_error(['message' => 'Action non valide.'], 400);
        }
    }

    // --- NEW FUNCTION: v2 fetch active clients with search, filters, pagination, and lock action ---
    private static function lmb_fetch_active_clients_v2() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Accès refusé.'], 403);
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
                echo '<a href="' . esc_url($edit_url) . '" class="lmb-btn lmb-btn-icon lmb-btn-primary" title="Modifier l\'utilisateur"><i class="fas fa-user-edit"></i></a>';
                
                if (!$is_admin) {
                    echo '<button class="lmb-btn lmb-btn-icon lmb-btn-warning lmb-lock-user-btn" data-user-id="' . $user_id . '" title="Verrouiller l\'utilisateur (définir comme inactif)"><i class="fas fa-user-lock"></i></button>';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;">Aucun client actif trouvé.</td></tr>';
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
        if (!$user_id) wp_send_json_error(['message' => 'ID utilisateur manquant.'], 400);
        
        // Prevent locking an admin
        if (user_can($user_id, 'manage_options')) {
            wp_send_json_error(['message' => 'Les administrateurs ne peuvent pas être verrouillés.'], 403);
        }

        update_user_meta($user_id, 'lmb_user_status', 'inactive');
        LMB_Ad_Manager::log_activity(sprintf('Compte client #%d verrouillé.', $user_id));
        
        wp_send_json_success(['message' => 'Le compte client a été verrouillé.']);
    }

    // --- UPDATED FUNCTION: v2 update user profile with role-based field restrictions ---
    private static function lmb_update_profile_v2() {
        $user_id_to_update = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        parse_str($_POST['form_data'], $data);

        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');

        if (!$is_admin && $current_user_id !== $user_id_to_update) {
            wp_send_json_error(['message' => 'Vous n\'avez pas la permission de modifier ce profil.'], 403);
            return;
        }
        
        $user_data = ['ID' => $user_id_to_update];

        if ($is_admin) {
            // --- START: MODIFIED ADMIN LOGIC ---
            if (isset($data['first_name'])) $user_data['first_name'] = sanitize_text_field($data['first_name']);
            if (isset($data['last_name'])) $user_data['last_name'] = sanitize_text_field($data['last_name']);
            
            // Handle display name based on client type
            $client_type = get_user_meta($user_id_to_update, 'lmb_client_type', true);
            if ($client_type === 'professional' && isset($data['company_name'])) {
                $company_name = sanitize_text_field($data['company_name']);
                update_user_meta($user_id_to_update, 'company_name', $company_name);
                $user_data['display_name'] = $company_name; // Set display name to company name
            } else {
                 $user_data['display_name'] = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
            }
            
            if (isset($data['company_rc'])) update_user_meta($user_id_to_update, 'company_rc', sanitize_text_field($data['company_rc']));
            if (isset($data['lmb_user_role'])) {
                $user = new WP_User($user_id_to_update);
                $user->set_role(sanitize_key($data['lmb_user_role']));
            }
            if (isset($data['lmb_client_type'])) {
                update_user_meta($user_id_to_update, 'lmb_client_type', sanitize_key($data['lmb_client_type']));
            }
            // --- END: MODIFIED ADMIN LOGIC ---
        }
        
        if (isset($data['company_hq'])) update_user_meta($user_id_to_update, 'company_hq', sanitize_text_field($data['company_hq']));
        if (isset($data['city'])) update_user_meta($user_id_to_update, 'city', sanitize_text_field($data['city']));
        if (isset($data['phone_number'])) update_user_meta($user_id_to_update, 'phone_number', sanitize_text_field($data['phone_number']));

        wp_update_user($user_data);

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
            wp_send_json_error(['message' => 'Vous n\'avez pas la permission de changer ce mot de passe.'], 403);
        }
        
        $new_pass = $data['new_password'];
        $confirm_pass = $data['confirm_password'];

        if (empty($new_pass) || empty($confirm_pass)) {
            wp_send_json_error(['message' => 'Veuillez remplir les deux champs du nouveau mot de passe.']);
        }
        if ($new_pass !== $confirm_pass) {
            wp_send_json_error(['message' => 'Les nouveaux mots de passe ne correspondent pas.']);
        }
        
        // If a non-admin is changing their own password, we must verify their current password
        if (!$is_admin || $current_user_id === $user_id_to_update) {
            $current_pass = $data['current_password'];
            $user = get_user_by('ID', $user_id_to_update);
            if (!wp_check_password($current_pass, $user->user_pass, $user->ID)) {
                wp_send_json_error(['message' => 'Votre mot de passe actuel n\'est pas correct.']);
            }
        }
        
        // All checks passed, update the password
        wp_set_password($new_pass, $user_id_to_update);
        
        wp_send_json_success();
    }

    // --- NEW FUNCTION: v2 manipulate client balance with admin-only access and validation ---
    private static function lmb_manipulate_balance() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Action non autorisée.'], 403);
            return;
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (empty($user_id)) {
            wp_send_json_error(['message' => 'ID utilisateur cible manquant.'], 400);
            return;
        }

        // --- START CORRECTION: Parse form data and use appropriate LMB_Points function ---
        parse_str($_POST['form_data'], $data);
        $amount = isset($data['amount']) ? floatval($data['amount']) : 0;
        $reason = isset($data['reason']) ? sanitize_textarea_field($data['reason']) : '';

        if (empty($amount)) {
            wp_send_json_error(['message' => 'Le montant ne peut pas être zéro.'], 400);
            return;
        }
        if (empty($reason)) {
            wp_send_json_error(['message' => 'La raison est obligatoire.'], 400);
            return;
        }

        $new_balance = null;
        if ($amount > 0) {
            // Credit (Add)
            $new_balance = LMB_Points::add($user_id, abs($amount), $reason);
        } elseif ($amount < 0) {
            // Debit (Deduct)
            $new_balance = LMB_Points::deduct($user_id, abs($amount), $reason);
            if ($new_balance === false) {
                 wp_send_json_error(['message' => 'Solde insuffisant pour débiter ce montant.'], 402);
                 return;
            }
        }
        // --- END CORRECTION ---

        // The remaining part of the original function is kept to generate the necessary HTML response
        $balance_history = LMB_Points::get_transactions($user_id, 5);
        ob_start();
        if (!empty($balance_history)) {
            foreach ($balance_history as $item) {
                $is_credit = $item->amount >= 0;
                ?>
                <div class="history-item">
                    <div class="history-icon <?php echo $is_credit ? 'credit' : 'debit'; ?>"><i class="fas <?php echo $is_credit ? 'fa-plus' : 'fa-minus'; ?>"></i></div>
                    <div class="history-details">
                        <span class="history-reason"><?php echo esc_html($item->reason); ?></span>
                        <span class="history-time"><?php echo esc_html(human_time_diff(strtotime($item->created_at))) . ' il y a'; ?></span>
                    </div>
                    <div class="history-amount <?php echo $is_credit ? 'credit' : 'debit'; ?>"><?php echo ($is_credit ? '+' : '') . esc_html($item->amount); ?></div>
                </div>
                <?php
            }
        } else {
            echo '<p class="no-history">Aucune transaction récente.</p>';
        }
        $history_html = ob_get_clean();

        wp_send_json_success(['message' => 'Le solde du client a été mis à jour avec succès.',
            'new_balance' => $new_balance,
            'history_html' => $history_html
        ]);
    }

    // --- NEW FUNCTION: Generate Accuse on-demand ---
    private static function lmb_admin_generate_accuse() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.'], 403);
        }
        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        if (!$ad_id) {
            wp_send_json_error(['message' => 'ID d\'annonce non valide.'], 400);
        }

        $accuse_url = LMB_Invoice_Handler::generate_accuse_pdf($ad_id);
        if ($accuse_url) {
            update_post_meta($ad_id, 'lmb_accuse_pdf_url', $accuse_url);
            wp_send_json_success(['message' => 'PDF d\'Accusé généré avec succès.']);
        } else {
            wp_send_json_error(['message' => 'Échec de la génération du PDF d\'Accusé.']);
        }
    }

    // --- REVISED FUNCTION: Upload Temporary Journal (Includes resource management) ---
    private static function lmb_admin_upload_temporary_journal() {
        // FIX: Temporarily increase resource limits for this resource-intensive task (PDF generation)
        @ini_set('memory_limit', '256M'); 
        @ini_set('max_execution_time', '180'); // Set max execution time to 3 minutes (180 seconds)

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
        
        // --- Input Processing (unchanged) ---
        $ad_ids_raw = isset($_POST['ad_ids']) ? explode(',', sanitize_text_field($_POST['ad_ids'])) : [];
        $ad_ids = array_filter(array_map('intval', $ad_ids_raw));
        $journal_no = isset($_POST['journal_no']) ? sanitize_text_field($_POST['journal_no']) : '';

        if (empty($ad_ids) || empty($_FILES['journal_file']) || empty($journal_no)) {
            wp_send_json_error(['message' => 'Missing required fields: Ad IDs, file, or journal number.'], 400);
        }
        
        // --- CRITICAL SAFETY INCLUDES (Ensure all dependencies are available) ---
        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }
        if (!function_exists('wp_delete_attachment')) {
            require_once(ABSPATH . 'wp-admin/includes/post.php');
        }
        if (!class_exists('LMB_PDF_Generator')) {
             require_once(LMB_CORE_PATH . 'includes/class-lmb-pdf-generator.php');
        }
        if (!class_exists('LMB_Invoice_Handler')) {
             require_once(LMB_CORE_PATH . 'includes/class-lmb-invoice-handler.php');
        }
        // --- END CRITICAL SAFETY INCLUDES ---

        // --- 1. Upload the file once ---
        $attachment_id = media_handle_upload('journal_file', 0);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()]);
        }
        
        update_post_meta($attachment_id, 'journal_no', $journal_no);

        $updated_count = 0;
        $accuse_generated_count = 0;
        $error_count = 0; // Tracks PDF generation failures
        
        // --- 2. Loop through all selected ads and update their meta ---
        foreach ($ad_ids as $ad_id) {
            $ad = get_post($ad_id);
            if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
                continue;
            }
            
            // a) Cleanup old journal associations 
            $old_temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
            if ($old_temp_journal_id && (int)$old_temp_journal_id !== (int)$attachment_id) {
                wp_delete_attachment($old_temp_journal_id, true);
            }
            delete_post_meta($ad_id, 'lmb_final_journal_id');

            // b) Set new temporary association (This ensures meta is updated even if PDF fails)
            update_post_meta($ad_id, 'lmb_temporary_journal_id', $attachment_id);
            update_post_meta($ad_id, 'lmb_journal_no', $journal_no);
            
            // c) Automatically generate accuse (Resource intensive part)
            if (class_exists('LMB_Invoice_Handler')) {
                // FIX: Use try/catch to ensure memory exhaustion in PDF generation doesn't crash the loop.
                try {
                     $accuse_url = LMB_Invoice_Handler::generate_accuse_pdf($ad_id);
                     if ($accuse_url) {
                        update_post_meta($ad_id, 'lmb_accuse_pdf_url', $accuse_url);
                        $accuse_generated_count++;
                     } else {
                        $error_count++;
                     }
                } catch (\Exception $e) {
                    // Log the error and continue to the next ad
                    error_log('LMB Bulk Upload PDF Error for Ad #' . $ad_id . ': ' . $e->getMessage());
                    $error_count++;
                }
            }
            $updated_count++;
        }
        
        // If no ads were updated but a file was uploaded, delete the orphaned attachment
        if ($updated_count === 0) {
            wp_delete_attachment($attachment_id, true);
            wp_send_json_error(['message' => 'No valid ads were found to update. The file was ignored.'], 400);
        }

        $message = sprintf('Temporary journal uploaded and applied to %d ads.', $updated_count);
        
        // Provide feedback on PDF generation failure
        if ($error_count > 0) {
            $message .= sprintf(' (ATTENTION: PDF Accusé generation failed for %d ads due to a system error.)', $error_count);
        }

        wp_send_json_success(['message' => $message]);
    }

    /* // --- MODIFIED FUNCTION: Upload FINAL Newspaper ---
    private static function lmb_upload_newspaper() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission refusée.']);
        
        if (empty($_POST['journal_no']) || empty($_FILES['newspaper_pdf']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            wp_send_json_error(['message' => 'Champs requis manquants. Le N° du Journal, le PDF et la plage de dates sont obligatoires.']);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $pdf_id = media_handle_upload('newspaper_pdf', 0);
        if (is_wp_error($pdf_id)) wp_send_json_error(['message' => 'Erreur de téléchargement du PDF : ' . $pdf_id->get_error_message()]);

        $journal_no = sanitize_text_field($_POST['journal_no']);
        $post_title = 'Journal N° ' . $journal_no;
        $post_id = wp_insert_post(['post_type' => 'lmb_newspaper', 'post_title' => $post_title, 'post_status' => 'publish']);
        
        if (is_wp_error($post_id)) { 
            wp_delete_attachment($pdf_id, true); 
            wp_send_json_error(['message' => 'Erreur lors de la création du post Journal Final : ' . $post_id->get_error_message()]); 
        }
        
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        update_post_meta($post_id, 'newspaper_pdf', $pdf_id);
        update_post_meta($post_id, 'journal_no', $journal_no);
        update_post_meta($post_id, 'start_date', $start_date);
        update_post_meta($post_id, 'end_date', $end_date);

        // --- START: CORRECTED QUERY ---
        $args = [
            'post_type'      => 'lmb_legal_ad',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'suppress_filters' => true, // Prevents interference from other plugins
            'meta_query'     => [
                'relation' => 'AND',
                // Condition 1: Must be within the date range
                [
                    'key'     => 'approved_date',
                    'value'   => [$start_date, $end_date],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE',
                ],
                // Condition 2: Must match the journal number
                [
                    'key'     => 'lmb_journal_no',
                    'value'   => $journal_no,
                    'compare' => '=',
                ],
                // Condition 3: Must not already have a final journal
                [
                    'key'     => 'lmb_final_journal_id',
                    'compare' => 'NOT EXISTS'
                ]
            ],
        ];
        // --- END: CORRECTED QUERY ---

        $ads_query = new WP_Query($args);
        
        $temp_journals_to_delete = [];
        if ($ads_query->have_posts()) {
            foreach ($ads_query->posts as $ad_id) {
                $temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
                if (!empty($temp_journal_id)) { $temp_journals_to_delete[] = $temp_journal_id; }
                
                update_post_meta($ad_id, 'lmb_final_journal_id', $post_id);
                delete_post_meta($ad_id, 'lmb_temporary_journal_id');
                // Optional: delete the temporary journal number meta as well
                delete_post_meta($ad_id, 'lmb_journal_no');
            }
        }
        if (!empty($temp_journals_to_delete)) {
            $unique_ids_to_delete = array_unique($temp_journals_to_delete);
            foreach ($unique_ids_to_delete as $attachment_id) { wp_delete_attachment($attachment_id, true); }
        }
        
        $updated_count = $ads_query->post_count;
        wp_reset_postdata();
        
        wp_send_json_success(['message' => 'Journal téléchargé. Associé à ' . $updated_count . ' annonces. Les anciens fichiers temporaires ont été supprimés.']);
    } */

    
    // --- REPLACEMENT FUNCTION: Fetch eligible ads for newspaper with replace mode support ---
    private static function lmb_fetch_eligible_ads_for_newspaper() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission refusée.'], 403);
        
        parse_str($_POST['filters'] ?? '', $filters);
        $start_date = sanitize_text_field($filters['start_date'] ?? '');
        $end_date = sanitize_text_field($filters['end_date'] ?? '');
        $journal_no = sanitize_text_field($filters['journal_no'] ?? '');
        $replace_mode = isset($filters['replace_journal']) && $filters['replace_journal'] === '1';

        if (empty($start_date) || empty($end_date) || empty($journal_no)) {
            wp_send_json_error(['message' => 'Plage de dates et numéro de journal requis.']);
        }

        $args = [
            'post_type'      => 'lmb_legal_ad',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'suppress_filters' => true,
            'meta_query'     => [
                'relation' => 'AND',
                ['key' => 'approved_date', 'value' => [$start_date, $end_date], 'compare' => 'BETWEEN', 'type' => 'DATE'],
                ['key' => 'lmb_journal_no', 'value' => $journal_no, 'compare' => '='],
            ],
        ];

        // --- START: NEW CONDITIONAL LOGIC ---
        // Only exclude already-associated ads if we are NOT in replace mode.
        if (!$replace_mode) {
            $args['meta_query'][] = [
                'key' => 'lmb_final_journal_id',
                'compare' => 'NOT EXISTS'
            ];
        }
        // --- END: NEW CONDITIONAL LOGIC ---

        $query = new WP_Query($args);
        $html = '';
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $final_journal_id = get_post_meta($post_id, 'lmb_final_journal_id', true);
                
                $status_html = '';
                if ($final_journal_id) {
                    $journal_title = get_the_title($final_journal_id);
                    $status_html = '<span class="lmb-status-badge lmb-status-published" style="background-color: #e74c3c;">Remplacement (Actuel: ' . esc_html($journal_title) . ')</span>';
                } else {
                     $status_html = '<span class="lmb-status-badge lmb-status-pending_review" style="background-color: #2ecc71;">Nouveau</span>';
                }

                $html .= '<tr data-status="' . ($final_journal_id ? 'replacement' : 'new') . '">'; // Add data attribute for JS
                $html .= '<td><input type="checkbox" class="lmb-ad-checkbox" value="' . esc_attr($post_id) . '" checked></td>';
                $html .= '<td>' . esc_html($post_id) . '</td>';
                $html .= '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                $html .= '<td>' . esc_html(get_post_meta($post_id, 'approved_date', true)) . '</td>';
                $html .= '<td>' . $status_html . '</td>'; // Add new status column
                $html .= '</tr>';
            }
        } else {
            wp_send_json_error(['message' => 'Aucune annonce trouvée pour ces critères.']);
        }
        wp_reset_postdata();

        wp_send_json_success(['html' => $html, 'count' => $query->post_count]);
    }

    /**
     * NEW: Replaces lmb_upload_newspaper. Associates the uploaded final PDF with selected ads.
     */
    private static function lmb_associate_final_newspaper() {
        check_ajax_referer('lmb_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Permission refusée.'], 403);
        
        $ad_ids = isset($_POST['ad_ids']) ? array_map('intval', $_POST['ad_ids']) : [];
        if (empty($ad_ids) || empty($_POST['journal_no']) || empty($_FILES['newspaper_pdf']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
            wp_send_json_error(['message' => 'Données manquantes. Annonces, N° du journal, PDF et dates sont requis.']);
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $pdf_id = media_handle_upload('newspaper_pdf', 0);
        if (is_wp_error($pdf_id)) wp_send_json_error(['message' => 'Erreur de téléchargement du PDF : ' . $pdf_id->get_error_message()]);

        $journal_no = sanitize_text_field($_POST['journal_no']);
        $post_title = 'Journal N° ' . $journal_no;
        $post_id = wp_insert_post(['post_type' => 'lmb_newspaper', 'post_title' => $post_title, 'post_status' => 'publish']);
        
        if (is_wp_error($post_id)) { 
            wp_delete_attachment($pdf_id, true); 
            wp_send_json_error(['message' => 'Erreur lors de la création du post Journal Final : ' . $post_id->get_error_message()]); 
        }
        
        update_post_meta($post_id, 'newspaper_pdf', $pdf_id);
        update_post_meta($post_id, 'journal_no', $journal_no);
        update_post_meta($post_id, 'start_date', sanitize_text_field($_POST['start_date']));
        update_post_meta($post_id, 'end_date', sanitize_text_field($_POST['end_date']));

        $temp_journals_to_delete = [];
        foreach ($ad_ids as $ad_id) {
            $temp_journal_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
            if (!empty($temp_journal_id)) { $temp_journals_to_delete[] = $temp_journal_id; }
            
            update_post_meta($ad_id, 'lmb_final_journal_id', $post_id);
            delete_post_meta($ad_id, 'lmb_temporary_journal_id');
            delete_post_meta($ad_id, 'lmb_journal_no');
        }
        
        if (!empty($temp_journals_to_delete)) {
            $unique_ids_to_delete = array_unique($temp_journals_to_delete);
            foreach ($unique_ids_to_delete as $attachment_id) { wp_delete_attachment($attachment_id, true); }
        }
        
        wp_send_json_success(['message' => 'Journal téléchargé. Associé à ' . count($ad_ids) . ' annonces sélectionnées.']);
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
        if (!empty($filters['filter_ref']) && is_numeric($filters['filter_ref'])) {
            $args['p'] = intval($filters['filter_ref']);
        }
        if (!empty($filters['filter_company'])) {
            $args['meta_query'][] = [
                'key' => 'company_name',
                'value' => sanitize_text_field($filters['filter_company']),
                'compare' => 'LIKE'
            ];
        }
        if (!empty($filters['filter_type'])) {
            $args['meta_query'][] = [   
                'key' => 'ad_type',
                'value' => sanitize_text_field($filters['filter_type']),
                'compare' => 'LIKE'
            ];
        }
        if (!empty($filters['filter_date'])) {
            $args['date_query'] = [[
                'year' => date('Y', strtotime($filters['filter_date'])),
                'month' => date('m', strtotime($filters['filter_date'])),
                'day' => date('d', strtotime($filters['filter_date'])),
            ]];
        }

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
                        //$approved_by = get_user_by('id', get_post_meta($post_id, 'approved_by', true));
                        $accuse_url = get_post_meta($post_id, 'lmb_accuse_pdf_url', true);
                        
                        $journal_display = '<span class="cell-placeholder">-</span>';
                        $final_journal_id = get_post_meta($post_id, 'lmb_final_journal_id', true);
                        $temp_journal_id = get_post_meta($post_id, 'lmb_temporary_journal_id', true);

                        if ($final_journal_id && ($pdf_id = get_post_meta($final_journal_id, 'newspaper_pdf', true)) && ($pdf_url = wp_get_attachment_url($pdf_id))) {
                            $journal_display = '<a href="' . esc_url($pdf_url) . '" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-text-link">' . esc_html(get_post_meta($final_journal_id, 'journal_no', true)) . '</a>';
                        } elseif ($temp_journal_id && ($pdf_url = wp_get_attachment_url($temp_journal_id))) {
                            $journal_display = '<a href="' . esc_url($pdf_url) . '" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-text-link">' . esc_html(get_post_meta($temp_journal_id, 'journal_no', true)) . '</a>';
                        }
                        
                        echo '<td>' . $post_id . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_the_date()) . '</td>';
                        //echo '<td>' . ($approved_by ? esc_html($approved_by->display_name) : 'N/A') . '</td>';
                        echo '<td>' . ($accuse_url ? '<a href="'.esc_url($accuse_url).'" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-text-link">Accusé</a>' : '<span class="cell-placeholder">-</span>') . '</td>';
                        echo '<td>' . $journal_display . '</td>';
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
                        echo '<td class="lmb-actions-cell no-hover"><button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-submit-ad-btn" data-ad-id="'.$post_id.'"><i class="fas fa-paper-plane"></i> Soumettre</button><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-delete-ad-btn" data-ad-id="'.$post_id.'"><i class="fas fa-trash"></i> Supprimer</button></td>';
                        break;
                    case 'denied':
                        echo '<td>' . $post_id . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'company_name', true)) . '</td>';
                        echo '<td>' . esc_html(get_post_meta($post_id, 'ad_type', true)) . '</td>';
                        echo '<td>' . esc_html(get_the_modified_date()) . '</td>';
                        echo '<td class="denial-reason">' . esc_html(get_post_meta($post_id, 'denial_reason', true)) . '</td>';
                        echo '<td class="lmb-actions-cell no-hover"><button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-delete-ad-btn" data-ad-id="'.$post_id.'"><i class="fas fa-trash"></i> Supprimer</button></td>';
                        break;
                }
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;">Aucune annonce trouvée pour ce statut.</td></tr>';
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
            $html = '<tr><td colspan="3" style="text-align:center;">Aucun journal trouvé correspondant à votre recherche.</td></tr>';
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
            wp_send_json_error(['message' => 'Accès refusé.'], 403);
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
                        echo '<a href="' . esc_url(wp_get_attachment_url($proof_id)) . '" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-paperclip"></i> Voir Justificatif</a>';
                    }
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action-btn" data-action="approve" data-id="' . $payment_id . '"><i class="fas fa-check"></i> Approuver</button>';
                    echo '<button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action-btn" data-action="deny" data-id="' . $payment_id . '"><i class="fas fa-times"></i> Rejeter</button>';
                } else {
                    echo '—';
                }
                echo '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="7" style="text-align:center;">Aucun paiement trouvé pour ce statut.</td></tr>';
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

    // --- REVISED FUNCTION: fetch package data for admin ---
    private static function lmb_get_package_data() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Accès refusé']);
        }
        $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
        if (!$package_id) {
            wp_send_json_error(['message' => 'ID de forfait non valide']);
        }
        $package_post = get_post($package_id);
        if (!$package_post || $package_post->post_type !== 'lmb_package') {
            wp_send_json_error(['message' => 'Forfait non trouvé']);
        }

        // <-- MODIFIED RESPONSE TO INCLUDE NEW DATA
        $package_data = [
            'id'             => $package_post->ID,
            'name'           => $package_post->post_title,
            'description'    => $package_post->post_content,
            'price'          => get_post_meta($package_id, 'price', true),
            'points'         => get_post_meta($package_id, 'points', true),
            'cost_per_ad'    => get_post_meta($package_id, 'cost_per_ad', true),
            'client_visible' => (bool) get_post_meta($package_id, 'client_visible', true), // <-- GET NEW VALUE
        ];

        wp_send_json_success(['package' => $package_data]);
    }

    // --- NEW FUNCTION: Admin subscribes user to package directly ---
    private static function lmb_admin_subscribe_user_to_package() {
        // Security is already checked in handle_request()
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;

        if (!$user_id || !$package_id) {
            wp_send_json_error(['message' => 'ID utilisateur ou ID de package manquant.'], 400);
        }

        $package = get_post($package_id);
        $user = get_user_by('ID', $user_id);

        if (!$package || $package->post_type !== 'lmb_package' || !$user) {
            wp_send_json_error(['message' => 'Utilisateur ou package non valide.'], 404);
        }

        $price = get_post_meta($package_id, 'price', true);
        $points = get_post_meta($package_id, 'points', true);

        // Create a payment post to track revenue
        $payment_id = wp_insert_post([
            'post_title'   => 'Admin Grant: ' . $package->post_title . ' for ' . $user->user_login,
            'post_type'    => 'lmb_payment',
            'post_status'  => 'publish',
            'post_author'  => $user_id,
        ]);

        if (is_wp_error($payment_id)) {
            wp_send_json_error(['message' => 'Erreur lors de la création de l\'enregistrement de paiement.']);
        }

        // Add metadata to the payment post for record-keeping
        update_post_meta($payment_id, 'package_id', $package_id);
        update_post_meta($payment_id, 'payment_status', 'completed'); // Marked as complete since it's an admin action
        update_post_meta($payment_id, 'package_price', $price);
        update_post_meta($payment_id, 'payment_method', 'admin_grant');

        // Add the points to the user's account
        $reason = sprintf(
            'Package "%s" accordé par l\'administrateur',
            $package->post_title,
        );
        LMB_Points::add($user_id, $points, $reason);

        wp_send_json_success(['message' => 'L\'utilisateur a été souscrit avec succès et les points ont été ajoutés.']);
    }

    // --- NEW FUNCTION: Quick Edit Ad Date ---
    private static function lmb_update_ad_date() {
        // 1. Sanitize and retrieve POST data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $new_date_str = isset($_POST['new_date']) ? sanitize_text_field($_POST['new_date']) : '';
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';

        // 2. Security: Verify the nonce and user permissions
        if (!wp_verify_nonce($nonce, 'lmb_update_ad_date_' . $post_id)) {
            wp_send_json_error(['message' => 'Security check failed.'], 403);
        }
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(['message' => 'Permission denied.'], 403);
        }
        if (!$post_id || empty($new_date_str)) {
            wp_send_json_error(['message' => 'Missing required data.'], 400);
        }

        // 3. Date Processing: Preserve original time and update the post
        $original_post = get_post($post_id);
        if (!$original_post) {
            wp_send_json_error(['message' => 'Post not found.'], 404);
        }
        $original_time = date('H:i:s', strtotime($original_post->post_date));
        $full_new_date = $new_date_str . ' ' . $original_time;

        $result = wp_update_post([
            'ID'            => $post_id,
            'post_date'     => $full_new_date,
            'post_date_gmt' => get_gmt_from_date($full_new_date),
            'edit_date'     => true,
        ], true);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => 'Failed to update post date: ' . $result->get_error_message()]);
        }

        // 4. CRITICAL FIX: Regenerate the ad's display text.
        // This updates the 'full_text' meta field that the public widget displays.
        if (class_exists('LMB_Form_Handler')) {
            LMB_Form_Handler::generate_and_save_formatted_text($post_id);
        }

        // 5. Update the 'approved_date' meta field for consistency with directory sorting.
        update_post_meta($post_id, 'approved_date', date('Y-m-d', strtotime($full_new_date)));

        // 6. Success: Send back confirmation.
        wp_send_json_success(['message' => 'Date updated and ad content regenerated successfully.']);
    }

    // --- REVISED FUNCTION: fetch eligible ads for newspaper creation ---
    private static function lmb_fetch_eligible_ads() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé.'], 403);
        
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

    // --- NEW FUNCTION: Clean Ad Association (Supports Bulk) ---
    private static function lmb_clean_ad_association() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.'], 403);
        }
        
        // --- INPUT NORMALIZATION FIX ---
        $ad_ids_input = isset($_POST['ad_ids']) ? $_POST['ad_ids'] : [];
        
        // Handle both comma-separated string (bulk form) and array (single button) inputs
        if (is_string($ad_ids_input)) {
             $ad_ids_raw = explode(',', sanitize_text_field($ad_ids_input));
        } elseif (is_array($ad_ids_input)) {
             $ad_ids_raw = $ad_ids_input;
        } else {
             $ad_ids_raw = [];
        }
        
        $ad_ids = array_filter(array_map('intval', $ad_ids_raw));
        // --- END INPUT NORMALIZATION FIX ---
        
        if (empty($ad_ids)) {
            wp_send_json_error(['message' => 'Aucun ID d\'annonce valide fourni.'], 400);
        }
        
        // --- SAFETY INCLUDES ---
        if (!function_exists('wp_delete_attachment')) {
            require_once(ABSPATH . 'wp-admin/includes/post.php');
        }
        if (!class_exists('LMB_Ad_Manager')) {
            require_once(LMB_CORE_PATH . 'includes/class-lmb-ad-manager.php');
        }
        if (!class_exists('LMB_Invoice_Handler')) {
             require_once(LMB_CORE_PATH . 'includes/class-lmb-invoice-handler.php');
        }
        // --- END SAFETY INCLUDES ---

        $cleaned_count = 0;
        foreach ($ad_ids as $ad_id) {
            $ad = get_post($ad_id);

            if (!$ad || $ad->post_type !== 'lmb_legal_ad') {
                continue; // Skip invalid or non-legal ad posts
            }

            // 1. Delete associated temporary journal file if it exists
            $temp_id = get_post_meta($ad_id, 'lmb_temporary_journal_id', true);
            if ($temp_id) {
                wp_delete_attachment($temp_id, true);
            }
            
            // 2. Delete the accuse file itself (via LMB_Invoice_Handler::delete_accuse_pdf_by_url)
            $accuse_url = get_post_meta($ad_id, 'lmb_accuse_pdf_url', true);
            if ($accuse_url && class_exists('LMB_Invoice_Handler')) {
                LMB_Invoice_Handler::delete_accuse_pdf_by_url($accuse_url);
            }

            // 3. Delete all meta keys related to association and resulting PDFs
            delete_post_meta($ad_id, 'lmb_temporary_journal_id');
            delete_post_meta($ad_id, 'lmb_final_journal_id');
            delete_post_meta($ad_id, 'lmb_final_journal_no');
            delete_post_meta($ad_id, 'lmb_accuse_pdf_url');

            $cleaned_count++;
            LMB_Ad_Manager::log_activity(sprintf('Association Journal-Annonce nettoyée pour l\'annonce #%d par %s.', $ad_id, wp_get_current_user()->display_name));
        }

        if ($cleaned_count > 0) {
            wp_send_json_success(['message' => sprintf('Nettoyage terminé. %d annonces mises à jour.', $cleaned_count)]);
        } else {
             wp_send_json_error(['message' => 'Nettoyage échoué. Aucune annonce valide trouvée.']);
        }
    }

    // --- NEW METHOD: AJAX Trigger for Cleanup ---
    private static function lmb_trigger_manual_cleanup() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission refusée.'], 403);
        }
        
        if (!class_exists('LMB_Maintenance_Utilities')) {
            // Include file if not loaded (safety fallback)
            require_once LMB_CORE_PATH . 'includes/class-lmb-maintenance-utilities.php';
        }

        // --- EXECUTE THE CLEANUP ---
        LMB_Maintenance_Utilities::run_cleanup();
        
        // Fetch log for immediate feedback (LMB_Ad_Manager::log_activity is used in run_cleanup)
        $log = get_option('lmb_activity_log', []);
        $latest_log = array_slice($log, 0, 1);
        
        // The cleanup utility writes its own detailed message to the log, which we retrieve here.
        $message = !empty($latest_log) 
            ? 'Nettoyage terminé. Résultat : ' . $latest_log[0]['msg'] 
            : 'Nettoyage déclenché avec succès. Vérifiez le journal d\'activité pour les détails.';

        wp_send_json_success(['message' => $message]);
    }

    // --- FUNCTION FOR GENERATING NEWSPAPER PREVIEW ---
    private static function lmb_generate_newspaper_preview() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé.'], 403);
        
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
            wp_send_json_error(['message' => 'Erreur lors de la création du brouillon de journal : ' . $temp_post_id->get_error_message()]);
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
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé.'], 403);
        
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
             wp_send_json_error(['message' => 'Erreur : Le brouillon de prévisualisation n\'a pas pu être retrouvé. Veuillez visualiser à nouveau.']);
        }
        
        $draft_post_id = $draft_posts[0]->ID;
        // In a real flow, this is where you would call the PDF generation library with the raw HTML
        $final_html_content = get_post_meta($draft_post_id, 'lmb_temp_newspaper_html', true);

        if (empty($final_html_content)) {
            wp_send_json_error(['message' => 'Erreur : Le contenu HTML du brouillon est vide.']);
        }

        // --- 2. CREATE THE FINAL lmb_newspaper post ---
        $post_title = 'Journal N° ' . $journal_no . ' (' . $date_start . ' au ' . $date_end . ') - FINAL';
        $newspaper_id = wp_insert_post([
            'post_type' => 'lmb_newspaper',
            'post_title' => $post_title,
            'post_status' => 'publish',
        ]);
        
        if (is_wp_error($newspaper_id)) { 
            wp_send_json_error(['message' => 'Erreur lors de la création du post Journal Final : ' . $newspaper_id->get_error_message()]);
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
        if (!current_user_can('manage_options')) wp_send_json_error(['message' => 'Accès refusé.'], 403);
        
        $temp_post_id = isset($_POST['temp_post_id']) ? intval($_POST['temp_post_id']) : 0;
        $draft_post = get_post($temp_post_id);

        if (!$draft_post || $draft_post->post_type !== 'lmb_newspaper' || $draft_post->post_status !== 'draft') {
            wp_send_json_error(['message' => 'ID de brouillon non valide ou déjà supprimé.'], 400);
        }

        if (wp_delete_post($temp_post_id, true)) {
            wp_send_json_success(['message' => 'Brouillon supprimé avec succès.']);
        } else {
            wp_send_json_error(['message' => 'Échec de la suppression du brouillon.']);
        }
    }

}