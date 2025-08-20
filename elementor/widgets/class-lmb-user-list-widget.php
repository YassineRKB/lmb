<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_User_List_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_list'; }
    public function get_title() { return __('LMB User List', 'lmb-core'); }
    public function get_icon() { return 'eicon-table'; }
    public function get_categories() { return ['lmb-2']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied. Administrator privileges required.', 'lmb-core') . '</p></div>';
            return;
        }

        // Get filter parameters
        $search_name = isset($_GET['search_name']) ? sanitize_text_field($_GET['search_name']) : '';
        $search_email = isset($_GET['search_email']) ? sanitize_text_field($_GET['search_email']) : '';
        $search_id = isset($_GET['search_id']) ? intval($_GET['search_id']) : '';
        $filter_city = isset($_GET['filter_city']) ? sanitize_text_field($_GET['filter_city']) : '';
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        // Build user query
        $args = [
            'number' => 20,
            'paged' => $paged,
            'orderby' => 'registered',
            'order' => 'DESC'
        ];

        if ($search_name) {
            $args['search'] = '*' . $search_name . '*';
            $args['search_columns'] = ['display_name', 'user_nicename'];
        }

        if ($search_email) {
            $args['search'] = '*' . $search_email . '*';
            $args['search_columns'] = ['user_email'];
        }

        if ($search_id) {
            $args['include'] = [$search_id];
        }

        if ($filter_city) {
            $args['meta_query'] = [
                [
                    'key' => 'billing_city',
                    'value' => $filter_city,
                    'compare' => 'LIKE'
                ]
            ];
        }

        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        $total_users = $user_query->get_total();

        // Get unique cities for filter dropdown
        $cities = $this->get_unique_cities();
        ?>
        <div class="lmb-user-list-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-users"></i> <?php esc_html_e('User Management', 'lmb-core'); ?></h3>
            </div>

            <!-- Filters -->
            <div class="lmb-filters-container">
                <form method="get" class="lmb-user-filters">
                    <div class="lmb-filter-row">
                        <input type="text" name="search_name" placeholder="<?php esc_attr_e('Search by name...', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($search_name); ?>" class="lmb-filter-input">
                        
                        <input type="email" name="search_email" placeholder="<?php esc_attr_e('Search by email...', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($search_email); ?>" class="lmb-filter-input">
                        
                        <input type="number" name="search_id" placeholder="<?php esc_attr_e('User ID', 'lmb-core'); ?>" 
                               value="<?php echo esc_attr($search_id); ?>" class="lmb-filter-input">
                        
                        <select name="filter_city" class="lmb-filter-select">
                            <option value=""><?php esc_html_e('All Cities', 'lmb-core'); ?></option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo esc_attr($city); ?>" <?php selected($filter_city, $city); ?>>
                                    <?php echo esc_html($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="lmb-btn lmb-btn-primary">
                            <i class="fas fa-search"></i> <?php esc_html_e('Filter', 'lmb-core'); ?>
                        </button>
                        
                        <a href="<?php echo esc_url(remove_query_arg(['search_name', 'search_email', 'search_id', 'filter_city'])); ?>" 
                           class="lmb-btn lmb-btn-secondary">
                            <i class="fas fa-times"></i> <?php esc_html_e('Clear', 'lmb-core'); ?>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="lmb-results-summary">
                <p><?php printf(esc_html__('Showing %d users', 'lmb-core'), count($users)); ?></p>
            </div>

            <!-- Users Table -->
            <div class="lmb-table-container">
                <table class="lmb-users-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Name', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Email', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('City', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Registered', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo esc_html($user->ID); ?></td>
                                    <td>
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                        <br><small>@<?php echo esc_html($user->user_login); ?></small>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo esc_html(get_user_meta($user->ID, 'billing_city', true)); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($user->user_registered))); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>" 
                                           class="lmb-btn lmb-btn-sm lmb-btn-primary" target="_blank">
                                            <i class="fas fa-user-edit"></i> <?php esc_html_e('View Profile', 'lmb-core'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="lmb-no-results">
                                    <?php esc_html_e('No users found matching your criteria.', 'lmb-core'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_users > 20): ?>
                <div class="lmb-pagination">
                    <?php
                    $total_pages = ceil($total_users / 20);
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $paged,
                        'total' => $total_pages,
                        'prev_text' => '&laquo; ' . esc_html__('Previous', 'lmb-core'),
                        'next_text' => esc_html__('Next', 'lmb-core') . ' &raquo;'
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .lmb-user-list-widget {
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
            grid-template-columns: 1fr 1fr 100px 150px auto auto;
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
        .lmb-users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .lmb-users-table th,
        .lmb-users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-users-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .lmb-users-table tbody tr:hover {
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
            .lmb-users-table {
                font-size: 14px;
            }
        }
        </style>
        <?php
    }

    private function get_unique_cities() {
        global $wpdb;
        $cities = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'billing_city' 
            AND meta_value != '' 
            ORDER BY meta_value ASC
        ");
        return array_filter($cities);
    }
}