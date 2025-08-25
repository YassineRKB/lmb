<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_List_Widget extends Widget_Base {
    public function get_name() { return 'lmb_legal_ads_list'; }
    public function get_title() { return __('LMB Legal Ads List', 'lmb-core'); }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return ['lmb-admin-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied. Administrator privileges required.', 'lmb-core') . '</p></div>';
            return;
        }

        // Get filter parameters
        $filter_ref = isset($_GET['filter_ref']) ? sanitize_text_field($_GET['filter_ref']) : '';
        $filter_user = isset($_GET['filter_user']) ? sanitize_text_field($_GET['filter_user']) : '';
        $filter_company = isset($_GET['filter_company']) ? sanitize_text_field($_GET['filter_company']) : '';
        $filter_ad_type = isset($_GET['filter_ad_type']) ? sanitize_text_field($_GET['filter_ad_type']) : '';
        $filter_approved_by = isset($_GET['filter_approved_by']) ? sanitize_text_field($_GET['filter_approved_by']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        // Build query args
        $args = [
            'post_type' => 'lmb_legal_ad',
            'post_status' => ['draft', 'pending_review', 'publish'],
            'posts_per_page' => 5,
            'paged' => $paged,
            'orderby' => $orderby === 'ref' ? 'ID' : $orderby,
            'order' => $order,
            'meta_query' => ['relation' => 'AND']
        ];

        if ($filter_ref) {
            if (is_numeric($filter_ref)) {
                $args['p'] = intval($filter_ref);
            } else {
                $args['s'] = $filter_ref;
            }
        }

        if ($filter_company) {
            $args['meta_query'][] = [
                'key' => 'company_name',
                'value' => $filter_company,
                'compare' => 'LIKE'
            ];
        }

        if ($filter_ad_type) {
            $args['meta_query'][] = [
                'key' => 'ad_type',
                'value' => $filter_ad_type,
                'compare' => 'LIKE'
            ];
        }

        if ($filter_user) {
            if (is_numeric($filter_user)) {
                $args['author'] = intval($filter_user);
            } else {
                $user = get_user_by('login', $filter_user);
                if (!$user) {
                    $user = get_user_by('email', $filter_user);
                }
                if ($user) {
                    $args['author'] = $user->ID;
                }
            }
        }

        $query = new WP_Query($args);
        $ads = $query->posts;

        // Get unique values for filters
        $ad_types = $this->get_unique_ad_types();
        ?>
        <div class="lmb-legal-ads-list-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-gavel"></i> <?php esc_html_e('Legal Ads Management', 'lmb-core'); ?></h3>
            </div>

            <!-- Filters -->
            <div class="lmb-filters-container">
                <form method="get" class="lmb-ads-filters">
                    <div class="lmb-filter-row">
                        <input type="text" name="filter_ref" placeholder="<?php esc_attr_e('Ref/ID', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($filter_ref); ?>" class="lmb-filter-input">
                        
                        <input type="text" name="filter_user" placeholder="<?php esc_attr_e('User (ID/login/email)', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($filter_user); ?>" class="lmb-filter-input">
                        
                        <input type="text" name="filter_company" placeholder="<?php esc_attr_e('Company', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($filter_company); ?>" class="lmb-filter-input">
                        
                        <select name="filter_ad_type" class="lmb-filter-select">
                            <option value=""><?php esc_html_e('All Ad Types', 'lmb-core'); ?></option>
                            <?php foreach ($ad_types as $type): ?>
                                <option value="<?php echo esc_attr($type); ?>" <?php selected($filter_ad_type, $type); ?>>
                                    <?php echo esc_html($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <input type="text" name="filter_approved_by" placeholder="<?php esc_attr_e('Approved By', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($filter_approved_by); ?>" class="lmb-filter-input">
                        
                        <button type="submit" class="lmb-btn lmb-btn-primary">
                            <i class="fas fa-search"></i> <?php esc_html_e('Filter', 'lmb-core'); ?>
                        </button>
                        
                        <a href="<?php echo esc_url(remove_query_arg(['filter_ref', 'filter_user', 'filter_company', 'filter_ad_type', 'filter_approved_by', 'orderby', 'order'])); ?>" 
                           class="lmb-btn lmb-btn-secondary">
                            <i class="fas fa-times"></i> <?php esc_html_e('Clear', 'lmb-core'); ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="lmb-results-summary">
                <p><?php printf(esc_html__('Showing %d of %d legal ads', 'lmb-core'), count($ads), $query->found_posts); ?></p>
            </div>

            <!-- Ads Table -->
            <div class="lmb-table-container">
                <table class="lmb-ads-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="<?php echo esc_url(add_query_arg(['orderby' => 'ID', 'order' => $orderby === 'ID' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                    <?php esc_html_e('Ref/ID', 'lmb-core'); ?>
                                    <?php if ($orderby === 'ID'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?php echo esc_url(add_query_arg(['orderby' => 'date', 'order' => $orderby === 'date' && $order === 'ASC' ? 'DESC' : 'ASC'])); ?>">
                                    <?php esc_html_e('Publication Date', 'lmb-core'); ?>
                                    <?php if ($orderby === 'date'): ?>
                                        <i class="fas fa-sort-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th><?php esc_html_e('User', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Company', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Ad Type', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Status', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Approved By', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($ads)): ?>
                            <?php foreach ($ads as $ad): ?>
                                <?php
                                $user = get_userdata($ad->post_author);
                                $company = get_post_meta($ad->ID, 'company_name', true);
                                $ad_type = get_post_meta($ad->ID, 'ad_type', true);
                                $status = get_post_meta($ad->ID, 'lmb_status', true);
                                $approved_by_id = get_post_meta($ad->ID, 'approved_by', true);
                                $approved_by = $approved_by_id ? get_userdata($approved_by_id) : null;
                                ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($ad->ID); ?></strong></td>
                                    <td><?php echo esc_html(get_the_date('Y-m-d H:i', $ad->ID)); ?></td>
                                    <td>
                                        <?php if ($user): ?>
                                            <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" target="_blank">
                                                <?php echo esc_html($user->display_name); ?>
                                            </a>
                                            <br><small><?php echo esc_html($user->user_email); ?></small>
                                        <?php else: ?>
                                            <em><?php esc_html_e('Unknown User', 'lmb-core'); ?></em>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html($company ?: '-'); ?></td>
                                    <td><?php echo esc_html($ad_type ?: '-'); ?></td>
                                    <td>
                                        <span class="lmb-status-badge lmb-status-<?php echo esc_attr($status); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $status))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($approved_by): ?>
                                            <?php echo esc_html($approved_by->display_name); ?>
                                        <?php else: ?>
                                            <em>-</em>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($ad->ID)); ?>" 
                                           class="lmb-btn lmb-btn-sm lmb-btn-primary" target="_blank">
                                            <i class="fas fa-edit"></i> <?php esc_html_e('Edit', 'lmb-core'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="lmb-no-results">
                                    <?php esc_html_e('No legal ads found matching your criteria.', 'lmb-core'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($query->max_num_pages > 1): ?>
                <div class="lmb-pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $paged,
                        'total' => $query->max_num_pages,
                        'prev_text' => '&laquo; ' . esc_html__('Previous', 'lmb-core'),
                        'next_text' => esc_html__('Next', 'lmb-core') . ' &raquo;'
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .lmb-legal-ads-list-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lmb-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .lmb-widget-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lmb-filters-container {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-filter-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr) auto auto;
            gap: 10px;
            align-items: center;
        }
        .lmb-filter-input, .lmb-filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .lmb-results-summary {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-table-container {
            overflow-x: auto;
        }
        .lmb-ads-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        .lmb-ads-table th,
        .lmb-ads-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-ads-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .lmb-ads-table th a {
            color: #495057;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .lmb-ads-table th a:hover {
            color: #667eea;
        }
        .lmb-ads-table tbody tr:hover {
            background: #f8f9fa;
        }
        .lmb-no-results {
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .lmb-filter-row {
                grid-template-columns: 1fr;
            }
        }
        </style>
        <?php
        wp_reset_postdata();
    }

    private function get_unique_ad_types() {
        global $wpdb;
        $types = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = 'ad_type' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ");
        return array_filter($types);
    }
}