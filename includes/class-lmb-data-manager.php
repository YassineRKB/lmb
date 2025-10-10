<?php
// FILE: includes/class-lmb-data-manager.php
if (!defined('ABSPATH')) exit;

class LMB_Data_Manager {

    public static function init() {
        // Add the new admin page under "LMB Core"
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);

        // Register the new AJAX actions
        add_action('wp_ajax_lmb_fetch_manageable_ads', [__CLASS__, 'ajax_fetch_manageable_ads']);
        add_action('wp_ajax_lmb_dissociate_ads', [__CLASS__, 'ajax_dissociate_ads']);
        add_action('wp_ajax_lmb_update_ad_journal_no', [__CLASS__, 'ajax_update_ad_journal_no']);
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'lmb-core',
            __('LMB Data Management', 'lmb-core'),
            __('Data Management', 'lmb-core'),
            'manage_options',
            'lmb-data-manager',
            [__CLASS__, 'render_management_page']
        );
    }

    public static function render_management_page() {
        ?>
        <div class="wrap lmb-data-manager-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>Cet outil vous permet de rechercher des annonces légales et de gérer leurs associations de journaux et leurs numéros.</p>

            <div class="card">
                <form id="lmb-dm-filters">
                    <div class="lmb-dm-filter-grid">
                        <input type="text" name="filter_ref" placeholder="Réf Annonce...">
                        <input type="text" name="filter_company" placeholder="Société...">
                        <input type="text" name="filter_journal_no" placeholder="N° Journal...">
                        <div class="lmb-dm-date-range">
                            <input type="date" name="filter_date_from" title="Date de début">
                            <span>au</span>
                            <input type="date" name="filter_date_to" title="Date de fin">
                        </div>
                        <div class="lmb-dm-filter-actions">
                            <button type="submit" class="button button-primary">Rechercher</button>
                            <button type="reset" class="button">Réinitialiser</button>
                        </div>
                    </div>
                </form>
            </div>

            <form id="lmb-dm-results-form">
                <div class="lmb-dm-bulk-actions">
                    <label for="lmb-dm-bulk-action-select" class="screen-reader-text">Select bulk action</label>
                    <select name="bulk_action" id="lmb-dm-bulk-action-select">
                        <option value="-1">Actions groupées</option>
                        <option value="dissociate">Dissocier le Journal Final</option>
                    </select>
                    <button type="submit" class="button">Appliquer</button>
                    <div class="lmb-dm-pagination tablenav-pages"></div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="lmb-dm-select-all"></td>
                            <th scope="col" class="manage-column">Réf Annonce</th>
                            <th scope="col" class="manage-column">Société</th>
                            <th scope="col" class="manage-column">N° Journal (Temp)</th>
                            <th scope="col" class="manage-column">Journal Final</th>
                            <th scope="col" class="manage-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="lmb-dm-results-tbody">
                        </tbody>
                </table>
            </form>
        </div>
        <?php
    }

    /**
     * FINAL FIX: Handles flexible filtering and pagination for manageable ads.
     */
    public static function ajax_fetch_manageable_ads() {
        check_ajax_referer('lmb_dm_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $paged = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
        parse_str($_POST['filters'] ?? '', $filters);

        $args = [
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'paged' => $paged,
            'meta_query' => ['relation' => 'AND'],
        ];

        if (!empty($filters['filter_ref'])) $args['p'] = intval($filters['filter_ref']);
        if (!empty($filters['filter_company'])) $args['meta_query'][] = ['key' => 'company_name', 'value' => sanitize_text_field($filters['filter_company']), 'compare' => 'LIKE'];
        if (!empty($filters['filter_journal_no'])) $args['meta_query'][] = ['key' => 'lmb_journal_no', 'value' => sanitize_text_field($filters['filter_journal_no']), 'compare' => 'LIKE'];
        
        // --- START: MORE ROBUST date_query LOGIC ---
        // This structure prevents crashes by building the query correctly.
        $date_query = [];
        if (!empty($filters['filter_date_from'])) {
            $date_query['after'] = sanitize_text_field($filters['filter_date_from']) . ' 00:00:00';
        }
        if (!empty($filters['filter_date_to'])) {
            $date_query['before'] = sanitize_text_field($filters['filter_date_to']) . ' 23:59:59';
        }
        
        if (!empty($date_query)) {
            $date_query['inclusive'] = true;
            // The entire query must be wrapped in an outer array.
            $args['date_query'] = [$date_query];
        }
        // --- END: MORE ROBUST date_query LOGIC ---

        $query = new WP_Query($args);
        ob_start();

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $post_id = get_the_ID();
                $final_journal_id = get_post_meta($post_id, 'lmb_final_journal_id', true);
                $final_journal_title = $final_journal_id && get_post($final_journal_id) ? get_the_title($final_journal_id) : '—';
                $temp_journal_no = get_post_meta($post_id, 'lmb_journal_no', true) ?: '—';
                ?>
                <tr data-ad-id="<?php echo $post_id; ?>">
                    <th scope="row" class="check-column"><input type="checkbox" name="ad_ids[]" value="<?php echo $post_id; ?>"></th>
                    <td><?php echo $post_id; ?></td>
                    <td><?php echo esc_html(get_post_meta($post_id, 'company_name', true)); ?></td>
                    <td><?php echo esc_html($temp_journal_no); ?></td>
                    <td><?php echo esc_html($final_journal_title); ?></td>
                    <td class="lmb-dm-actions-cell">
                        <input type="text" class="lmb-dm-new-journal-no" placeholder="Nouveau N° Journal...">
                        <button type="button" class="button button-small lmb-dm-set-journal-no">Définir</button>
                    </td>
                </tr>
                <?php
            }
        } else {
            echo '<tr><td colspan="6">Aucune annonce trouvée correspondant à vos critères.</td></tr>';
        }
        $html = ob_get_clean();
        wp_reset_postdata();

        $pagination_html = paginate_links([
            'base' => '%_%',
            'format' => '?paged=%#%',
            'current' => $paged,
            'total' => $query->max_num_pages,
            'prev_text' => '‹',
            'next_text' => '›',
            'type' => 'plain'
        ]);

        wp_send_json_success(['html' => $html, 'pagination' => $pagination_html]);
    }
    
    public static function ajax_dissociate_ads() {
        check_ajax_referer('lmb_dm_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $ad_ids = isset($_POST['ad_ids']) ? array_map('intval', $_POST['ad_ids']) : [];
        if (empty($ad_ids)) wp_send_json_error(['message' => 'Aucune annonce sélectionnée.']);

        $count = 0;
        foreach ($ad_ids as $ad_id) {
            delete_post_meta($ad_id, 'lmb_final_journal_id');
            $count++;
        }
        wp_send_json_success(['message' => $count . ' annonce(s) ont été dissociées avec succès.']);
    }

    public static function ajax_update_ad_journal_no() {
        check_ajax_referer('lmb_dm_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error();

        $ad_id = isset($_POST['ad_id']) ? intval($_POST['ad_id']) : 0;
        $new_journal_no = isset($_POST['new_journal_no']) ? sanitize_text_field($_POST['new_journal_no']) : '';
        
        if (!$ad_id || $new_journal_no === '') wp_send_json_error(['message' => 'Données invalides.']);

        update_post_meta($ad_id, 'lmb_journal_no', $new_journal_no);
        wp_send_json_success(['message' => 'Le numéro de journal pour l\'annonce ' . $ad_id . ' a été mis à jour.']);
    }
}