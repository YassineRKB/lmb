<?php
if (!defined('ABSPATH')) {
    exit;
}

class LMB_Enhanced_Admin {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menus']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
        
        // Enhanced legal ads list functionality
        add_filter('manage_lmb_legal_ad_posts_columns', [__CLASS__, 'customize_ad_columns']);
        add_action('manage_lmb_legal_ad_posts_custom_column', [__CLASS__, 'populate_ad_columns'], 10, 2);
        add_filter('manage_edit-lmb_legal_ad_sortable_columns', [__CLASS__, 'make_ad_columns_sortable']);
        add_action('pre_get_posts', [__CLASS__, 'sort_ad_columns']);
        
        // Add bulk actions
        add_filter('bulk_actions-edit-lmb_legal_ad', [__CLASS__, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-lmb_legal_ad', [__CLASS__, 'handle_bulk_actions'], 10, 3);
        
        // Add admin notices
        add_action('admin_notices', [__CLASS__, 'show_admin_notices']);
        
        // Add meta boxes
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post', [__CLASS__, 'save_meta_box_data']);
        
        // AJAX handlers
        add_action('wp_ajax_lmb_quick_status_change', [__CLASS__, 'ajax_quick_status_change']);
        add_action('wp_ajax_lmb_get_ad_stats', [__CLASS__, 'ajax_get_ad_stats']);
        add_action('wp_ajax_lmb_export_ads', [__CLASS__, 'ajax_export_ads']);
    }

    /**
     * Add comprehensive admin menus
     */
    public static function add_admin_menus() {
        // Main LMB menu
        add_menu_page(
            __('LMB Core', 'lmb-core'),
            __('LMB Core', 'lmb-core'),
            'manage_options',
            'lmb-core',
            [__CLASS__, 'render_dashboard'],
            'dashicons-media-text',
            58
        );

        // Dashboard (same as main menu)
        add_submenu_page(
            'lmb-core',
            __('Dashboard', 'lmb-core'),
            __('Dashboard', 'lmb-core'),
            'manage_options',
            'lmb-core',
            [__CLASS__, 'render_dashboard']
        );

        // Settings
        add_submenu_page(
            'lmb-core',
            __('Settings', 'lmb-core'),
            __('Settings', 'lmb-core'),
            'manage_options',
            'lmb-core-settings',
            [__CLASS__, 'render_settings']
        );

        // User Points Management
        add_submenu_page(
            'lmb-core',
            __('User Points', 'lmb-core'),
            __('User Points', 'lmb-core'),
            'manage_options',
            'lmb-core-points',
            [__CLASS__, 'render_points_management']
        );

        // Reports & Analytics
        add_submenu_page(
            'lmb-core',
            __('Reports', 'lmb-core'),
            __('Reports', 'lmb-core'),
            'manage_options',
            'lmb-core-reports',
            [__CLASS__, 'render_reports']
        );

        // System Status
        add_submenu_page(
            'lmb-core',
            __('System Status', 'lmb-core'),
            __('System Status', 'lmb-core'),
            'manage_options',
            'lmb-core-status',
            [__CLASS__, 'render_system_status']
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public static function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'lmb-core') === false && $hook !== 'edit.php') {
            return;
        }

        wp_enqueue_script('lmb-admin-js', LMB_CORE_URL . 'assets/js/admin.js', ['jquery'], LMB_CORE_VERSION, true);
        wp_enqueue_style('lmb-admin-css', LMB_CORE_URL . 'assets/css/admin.css', [], LMB_CORE_VERSION);

        // Localize script for AJAX
        wp_localize_script('lmb-admin-js', 'lmbAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_admin_actions'),
            'strings' => [
                'confirm_status_change' => __('Are you sure you want to change the status?', 'lmb-core'),
                'confirm_bulk_action' => __('Are you sure you want to perform this bulk action?', 'lmb-core'),
                'status_changed' => __('Status changed successfully.', 'lmb-core'),
                'error_occurred' => __('An error occurred. Please try again.', 'lmb-core'),
            ]
        ]);
    }

    /**
     * Enhanced dashboard with comprehensive statistics
     */
    public static function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }

        $stats = self::get_comprehensive_stats();
        ?>
        <div class="wrap lmb-admin-dashboard">
            <h1><?php esc_html_e('LMB Core Dashboard', 'lmb-core'); ?></h1>

            <!-- Key Metrics Cards -->
            <div class="lmb-metrics-grid">
                <div class="lmb-metric-card">
                    <div class="lmb-metric-icon">📄</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['total_ads']); ?></h3>
                        <p><?php esc_html_e('Total Legal Ads', 'lmb-core'); ?></p>
                    </div>
                </div>

                <div class="lmb-metric-card pending">
                    <div class="lmb-metric-icon">⏳</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['pending_ads']); ?></h3>
                        <p><?php esc_html_e('Pending Review', 'lmb-core'); ?></p>
                        <?php if ($stats['pending_ads'] > 0): ?>
                            <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad&lmb_status=pending_review'); ?>" class="lmb-action-link">
                                <?php esc_html_e('Review Now', 'lmb-core'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="lmb-metric-card success">
                    <div class="lmb-metric-icon">✅</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['published_ads']); ?></h3>
                        <p><?php esc_html_e('Published Ads', 'lmb-core'); ?></p>
                    </div>
                </div>

                <div class="lmb-metric-card info">
                    <div class="lmb-metric-icon">👥</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p><?php esc_html_e('Registered Users', 'lmb-core'); ?></p>
                    </div>
                </div>
            </div>

            <div class="lmb-dashboard-row">
                <!-- Recent Activity -->
                <div class="lmb-dashboard-col-8">
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('Recent Ad Submissions', 'lmb-core'); ?></h2>
                        <?php self::render_recent_ads_table(); ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="lmb-dashboard-col-4">
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('Quick Actions', 'lmb-core'); ?></h2>
                        <div class="lmb-quick-actions">
                            <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad&lmb_status=pending_review'); ?>" class="button button-primary button-large">
                                <?php esc_html_e('Review Pending Ads', 'lmb-core'); ?>
                                <?php if ($stats['pending_ads'] > 0): ?>
                                    <span class="lmb-badge"><?php echo $stats['pending_ads']; ?></span>
                                <?php endif; ?>
                            </a>

                            <a href="<?php echo admin_url('admin.php?page=lmb-core-points'); ?>" class="button button-secondary button-large">
                                <?php esc_html_e('Manage User Points', 'lmb-core'); ?>
                            </a>

                            <a href="<?php echo admin_url('edit.php?post_type=lmb_newspaper'); ?>" class="button button-secondary button-large">
                                <?php esc_html_e('Upload Newspaper', 'lmb-core'); ?>
                            </a>

                            <a href="<?php echo admin_url('admin.php?page=lmb-core-reports'); ?>" class="button button-secondary button-large">
                                <?php esc_html_e('View Reports', 'lmb-core'); ?>
                            </a>
                        </div>
                    </div>

                    <!-- System Health -->
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('System Health', 'lmb-core'); ?></h2>
                        <?php self::render_system_health(); ?>
                    </div>
                </div>
            </div>

            <!-- Charts and Analytics -->
            <div class="lmb-dashboard-row">
                <div class="lmb-dashboard-col-6">
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('Submissions by Type', 'lmb-core'); ?></h2>
                        <canvas id="lmb-ad-types-chart" width="400" height="200"></canvas>
                    </div>
                </div>

                <div class="lmb-dashboard-col-6">
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('Monthly Submissions', 'lmb-core'); ?></h2>
                        <canvas id="lmb-monthly-chart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
            // Initialize charts with data
            jQuery(document).ready(function($) {
                // Ad types chart data
                const adTypesData = <?php echo wp_json_encode($stats['ad_types']); ?>;
                // Monthly submissions data
                const monthlyData = <?php echo wp_json_encode($stats['monthly_submissions']); ?>;
                
                // Initialize charts (assuming Chart.js is loaded)
                if (typeof Chart !== 'undefined') {
                    LMB_Admin.initCharts(adTypesData, monthlyData);
                }
            });
        </script>
        <?php
    }

    /**
     * Get comprehensive statistics for dashboard
     */
    private static function get_comprehensive_stats() {
        global $wpdb;

        // Basic counts
        $total_ads = wp_count_posts('lmb_legal_ad');
        $total_users = count(get_users());
        
        // Ads by status
        $pending_ads = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_lmb_ad_status' 
             AND pm.meta_value = 'pending_review' 
             AND p.post_type = 'lmb_legal_ad'"
        );
        
        $published_ads = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_lmb_ad_status' 
             AND pm.meta_value = 'published' 
             AND p.post_type = 'lmb_legal_ad'"
        );

        // Ad types distribution
        $ad_types_raw = $wpdb->get_results(
            "SELECT pm.meta_value as type, COUNT(*) as count 
             FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = '_lmb_ad_type' 
             AND p.post_type = 'lmb_legal_ad' 
             GROUP BY pm.meta_value 
             ORDER BY count DESC"
        );

        $ad_types = [];
        foreach ($ad_types_raw as $type) {
            $ad_types[$type->type] = (int) $type->count;
        }

        // Monthly submissions (last 6 months)
        $monthly_submissions = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} 
                 WHERE post_type = 'lmb_legal_ad' 
                 AND DATE_FORMAT(post_date, '%%Y-%%m') = %s",
                $month
            ));
            $monthly_submissions[date('M Y', strtotime($month . '-01'))] = $count;
        }

        return [
            'total_ads' => $total_ads->publish + $total_ads->draft + $total_ads->pending,
            'pending_ads' => $pending_ads,
            'published_ads' => $published_ads,
            'total_users' => $total_users,
            'ad_types' => $ad_types,
            'monthly_submissions' => $monthly_submissions
        ];
    }

    /**
     * Render recent ads table
     */
    private static function render_recent_ads_table() {
        $recent_ads = get_posts([
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_lmb_ad_status',
                    'value' => 'pending_review',
                    'compare' => '='
                ]
            ]
        ]);

        if (empty($recent_ads)) {
            echo '<p>' . esc_html__('No recent submissions to review.', 'lmb-core') . '</p>';
            return;
        }
        ?>
        <div class="lmb-recent-ads-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Type', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Client', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Submitted', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_ads as $ad): 
                        $ad_type = get_field('ad_type', $ad->ID);
                        $client_id = get_field('lmb_client_id', $ad->ID);
                        $client = get_userdata($client_id);
                    ?>
                        <tr>
                            <td><strong>#<?php echo $ad->ID; ?></strong></td>
                            <td><?php echo esc_html($ad_type); ?></td>
                            <td>
                                <?php if ($client): ?>
                                    <a href="<?php echo get_edit_user_link($client_id); ?>">
                                        <?php echo esc_html($client->display_name); ?>
                                    </a>
                                <?php else: ?>
                                    <em><?php esc_html_e('Unknown', 'lmb-core'); ?></em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo human_time_diff(strtotime($ad->post_date), current_time('timestamp')) . ' ' . __('ago', 'lmb-core'); ?></td>
                            <td>
                                <div class="lmb-quick-actions-inline">
                                    <button class="button button-small lmb-quick-approve" data-post-id="<?php echo $ad->ID; ?>">
                                        <?php esc_html_e('Approve', 'lmb-core'); ?>
                                    </button>
                                    <a href="<?php echo get_edit_post_link($ad->ID); ?>" class="button button-small">
                                        <?php esc_html_e('Review', 'lmb-core'); ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render system health indicators
     */
    private static function render_system_health() {
        $health_checks = [
            'acf_active' => [
                'label' => __('ACF Plugin', 'lmb-core'),
                'status' => function_exists('acf_add_local_field_group'),
                'message' => function_exists('acf_add_local_field_group') 
                    ? __('Active', 'lmb-core') 
                    : __('Not Active', 'lmb-core')
            ],
            'elementor_active' => [
                'label' => __('Elementor', 'lmb-core'),
                'status' => defined('ELEMENTOR_VERSION'),
                'message' => defined('ELEMENTOR_VERSION') 
                    ? __('Active', 'lmb-core') 
                    : __('Not Active', 'lmb-core')
            ],
            'upload_writable' => [
                'label' => __('Uploads Directory', 'lmb-core'),
                'status' => wp_is_writable(wp_upload_dir()['basedir']),
                'message' => wp_is_writable(wp_upload_dir()['basedir']) 
                    ? __('Writable', 'lmb-core') 
                    : __('Not Writable', 'lmb-core')
            ]
        ];

        foreach ($health_checks as $check) {
            $status_class = $check['status'] ? 'lmb-status-good' : 'lmb-status-error';
            $icon = $check['status'] ? '✅' : '❌';
            
            echo '<div class="lmb-health-check ' . $status_class . '">';
            echo '<span class="lmb-health-icon">' . $icon . '</span>';
            echo '<span class="lmb-health-label">' . esc_html($check['label']) . '</span>';
            echo '<span class="lmb-health-message">' . esc_html($check['message']) . '</span>';
            echo '</div>';
        }
    }

    /**
     * Customize legal ads list columns
     */
    public static function customize_ad_columns($columns) {
        // Remove date column, we'll add our own
        unset($columns['date']);

        // Add custom columns
        $new_columns = [
            'cb' => $columns['cb'],
            'title' => $columns['title'],
            'lmb_ad_type' => __('Ad Type', 'lmb-core'),
            'lmb_status' => __('Status', 'lmb-core'),
            'lmb_client' => __('Client', 'lmb-core'),
            'lmb_submission_date' => __('Submitted', 'lmb-core'),
            'lmb_actions' => __('Quick Actions', 'lmb-core')
        ];

        return $new_columns;
    }

    /**
     * Populate custom columns with data
     */
    public static function populate_ad_columns($column, $post_id) {
        switch ($column) {
            case 'lmb_ad_type':
                $ad_type = get_field('ad_type', $post_id);
                echo '<span class="lmb-ad-type-badge">' . esc_html($ad_type) . '</span>';
                break;

            case 'lmb_status':
                $status = get_field('lmb_status', $post_id);
                $status_labels = [
                    'draft' => __('Draft', 'lmb-core'),
                    'pending_review' => __('Pending Review', 'lmb-core'),
                    'published' => __('Published', 'lmb-core'),
                    'denied' => __('Denied', 'lmb-core')
                ];
                
                $status_classes = [
                    'draft' => 'lmb-status-draft',
                    'pending_review' => 'lmb-status-pending',
                    'published' => 'lmb-status-published',
                    'denied' => 'lmb-status-denied'
                ];
                
                $status_label = $status_labels[$status] ?? ucfirst($status);
                $status_class = $status_classes[$status] ?? 'lmb-status-unknown';
                
                echo '<span class="lmb-status-badge ' . $status_class . '">' . esc_html($status_label) . '</span>';
                break;

            case 'lmb_client':
                $client_id = get_field('lmb_client_id', $post_id);
                if ($client_id) {
                    $client = get_userdata($client_id);
                    if ($client) {
                        echo '<a href="' . esc_url(get_edit_user_link($client_id)) . '">';
                        echo esc_html($client->display_name);
                        echo '<br><small>' . esc_html($client->user_email) . '</small>';
                        echo '</a>';
                    } else {
                        echo '<em>' . __('User not found', 'lmb-core') . '</em>';
                    }
                } else {
                    echo '<em>' . __('No client assigned', 'lmb-core') . '</em>';
                }
                break;

            case 'lmb_submission_date':
                $post = get_post($post_id);
                echo '<strong>' . date('Y-m-d', strtotime($post->post_date)) . '</strong><br>';
                echo '<small>' . date('H:i', strtotime($post->post_date)) . '</small>';
                break;

            case 'lmb_actions':
                $current_status = get_field('lmb_status', $post_id);
                echo '<div class="lmb-quick-actions-column">';
                
                if ($current_status === 'pending_review') {
                    echo '<button class="button button-small button-primary lmb-quick-approve" data-post-id="' . $post_id . '">';
                    echo __('Approve', 'lmb-core') . '</button> ';
                    
                    echo '<button class="button button-small lmb-quick-deny" data-post-id="' . $post_id . '">';
                    echo __('Deny', 'lmb-core') . '</button>';
                } else {
                    echo '<select class="lmb-quick-status-change" data-post-id="' . $post_id . '">';
                    echo '<option value="">' . __('Change Status...', 'lmb-core') . '</option>';
                    
                    $statuses = [
                        'pending_review' => __('Pending Review', 'lmb-core'),
                        'published' => __('Published', 'lmb-core'),
                        'denied' => __('Denied', 'lmb-core'),
                        'draft' => __('Draft', 'lmb-core')
                    ];
                    
                    foreach ($statuses as $status_key => $status_label) {
                        if ($status_key !== $current_status) {
                            echo '<option value="' . esc_attr($status_key) . '">' . esc_html($status_label) . '</option>';
                        }
                    }
                    echo '</select>';
                }
                
                echo '</div>';
                break;
        }
    }

    /**
     * Make columns sortable
     */
    public static function make_ad_columns_sortable($columns) {
        $columns['lmb_ad_type'] = 'lmb_ad_type';
        $columns['lmb_status'] = 'lmb_status';
        $columns['lmb_client'] = 'lmb_client';
        $columns['lmb_submission_date'] = 'date';
        
        return $columns;
    }

    /**
     * Handle column sorting
     */
    public static function sort_ad_columns($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $orderby = $query->get('orderby');

        switch ($orderby) {
            case 'lmb_ad_type':
                $query->set('meta_key', '_lmb_ad_type');
                $query->set('orderby', 'meta_value');
                break;

            case 'lmb_status':
                $query->set('meta_key', '_lmb_ad_status');
                $query->set('orderby', 'meta_value');
                break;

            case 'lmb_client':
                $query->set('meta_key', '_lmb_client_id');
                $query->set('orderby', 'meta_value_num');
                break;
        }
    }

    /**
     * Add bulk actions for status changes
     */
    public static function add_bulk_actions($actions) {
        $actions['lmb_bulk_approve'] = __('Approve Selected', 'lmb-core');
        $actions['lmb_bulk_deny'] = __('Deny Selected', 'lmb-core');
        $actions['lmb_bulk_pending'] = __('Set Pending Review', 'lmb-core');
        $actions['lmb_bulk_export'] = __('Export Selected', 'lmb-core');
        
        return $actions;
    }

    /**
     * Handle bulk actions
     */
    public static function handle_bulk_actions($redirect_to, $action, $post_ids) {
        if (strpos($action, 'lmb_bulk_') !== 0) {
            return $redirect_to;
        }

        $status_map = [
            'lmb_bulk_approve' => 'published',
            'lmb_bulk_deny' => 'denied',
            'lmb_bulk_pending' => 'pending_review'
        ];

        if ($action === 'lmb_bulk_export') {
            // Handle export
            self::export_ads($post_ids);
            return $redirect_to;
        }

        if (!isset($status_map[$action])) {
            return $redirect_to;
        }

        $new_status = $status_map[$action];
        $updated_count = 0;

        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) === 'lmb_legal_ad') {
                update_field('lmb_status', $new_status, $post_id);
                update_post_meta($post_id, '_lmb_ad_status', $new_status);
                
                // Update post status if published
                if ($new_status === 'published') {
                    wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
                }

                // Send notification to client
                $client_id = get_field('lmb_client_id', $post_id);
                if ($client_id) {
                    self::send_status_change_notification($post_id, $client_id, $new_status);
                }

                $updated_count++;
            }
        }

        return add_query_arg([
            'lmb_bulk_updated' => $updated_count,
            'lmb_bulk_action' => $action
        ], $redirect_to);
    }

    /**
     * Show admin notices for bulk actions
     */
    public static function show_admin_notices() {
        if (isset($_GET['lmb_bulk_updated']) && isset($_GET['lmb_bulk_action'])) {
            $updated_count = intval($_GET['lmb_bulk_updated']);
            $action = sanitize_text_field($_GET['lmb_bulk_action']);

            $messages = [
                'lmb_bulk_approve' => __('approved', 'lmb-core'),
                'lmb_bulk_deny' => __('denied', 'lmb-core'),
                'lmb_bulk_pending' => __('set to pending review', 'lmb-core')
            ];

            if (isset($messages[$action]) && $updated_count > 0) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . sprintf(
                    _n(
                        '%d ad has been %s.',
                        '%d ads have been %s.',
                        $updated_count,
                        'lmb-core'
                    ),
                    $updated_count,
                    $messages[$action]
                ) . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * AJAX handler for quick status changes
     */
    public static function ajax_quick_status_change() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'lmb-core')]);
        }

        check_ajax_referer('lmb_admin_actions', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        if (!$post_id || !in_array($new_status, ['draft', 'pending_review', 'published', 'denied'])) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'lmb-core')]);
        }

        // Update status
        update_field('lmb_status', $new_status, $post_id);
        update_post_meta($post_id, '_lmb_ad_status', $new_status);

        if ($new_status === 'published') {
            wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
        }

        // Send notification
        $client_id = get_field('lmb_client_id', $post_id);
        if ($client_id) {
            self::send_status_change_notification($post_id, $client_id, $new_status);
        }

        wp_send_json_success([
            'message' => __('Status updated successfully.', 'lmb-core'),
            'new_status' => $new_status
        ]);
    }

    /**
     * Send status change notification to client
     */
    private static function send_status_change_notification($post_id, $client_id, $new_status) {
        $client = get_userdata($client_id);
        $ad_type = get_field('ad_type', $post_id);
        
        if (!$client) return;

        $status_messages = [
            'pending_review' => __('Your ad is now under review by our team.', 'lmb-core'),
            'published' => __('Great news! Your ad has been approved and published.', 'lmb-core'),
            'denied' => __('Unfortunately, your ad was not approved. Please contact our support team for more information.', 'lmb-core'),
            'draft' => __('Your ad has been moved back to draft status.', 'lmb-core')
        ];

        $subject = sprintf(__('[%s] Ad Status Update - %s', 'lmb-core'), get_bloginfo('name'), $ad_type);
        
        $message = sprintf(
            __("Hello %s,\n\nYour legal ad (ID: %d, Type: %s) status has been updated.\n\nNew Status: %s\n%s\n\nView your dashboard: %s\n\nBest regards,\nThe %s Team", 'lmb-core'),
            $client->display_name,
            $post_id,
            $ad_type,
            ucfirst(str_replace('_', ' ', $new_status)),
            $status_messages[$new_status] ?? '',
            home_url('/dashboard'),
            get_bloginfo('name')
        );

        wp_mail($client->user_email, $subject, $message);

        // Log the notification
        LMB_Error_Handler::log_error('Status change notification sent', [
            'post_id' => $post_id,
            'client_id' => $client_id,
            'new_status' => $new_status,
            'admin_user' => get_current_user_id()
        ]);
    }

    /**
     * Export selected ads
     */
    private static function export_ads($post_ids) {
        $ads_data = [];
        
        foreach ($post_ids as $post_id) {
            if (get_post_type($post_id) !== 'lmb_legal_ad') continue;
            
            $post = get_post($post_id);
            $client_id = get_field('lmb_client_id', $post_id);
            $client = get_userdata($client_id);
            
            $ads_data[] = [
                'ID' => $post_id,
                'Title' => $post->post_title,
                'Ad Type' => get_field('ad_type', $post_id),
                'Status' => get_field('lmb_status', $post_id),
                'Client Name' => $client ? $client->display_name : 'Unknown',
                'Client Email' => $client ? $client->user_email : 'Unknown',
                'Submitted Date' => $post->post_date,
                'Full Text' => get_field('full_text', $post_id)
            ];
        }

        // Generate CSV
        $filename = 'lmb-ads-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        if (!empty($ads_data)) {
            fputcsv($output, array_keys($ads_data[0]));
            
            // CSV data
            foreach ($ads_data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }

    /**
     * Register additional settings
     */
    public static function register_settings() {
        // Settings are already registered in the main admin class
        // Add any additional settings specific to enhanced features here
    }

    /**
     * Render enhanced settings page
     */
    public static function render_settings() {
        // Include the existing settings page content
        LMB_Admin::render_settings();
    }

    /**
     * Render points management page
     */
    public static function render_points_management() {
        // Include the existing points management page content
        LMB_Admin::render_points();
    }

    /**
     * Render reports page
     */
    public static function render_reports() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }

        $stats = LMB_Form_Handler::get_submission_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Reports', 'lmb-core'); ?></h1>
            
            <div class="lmb-reports-container">
                <!-- Add comprehensive reporting interface here -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('Submission Statistics', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <tr>
                                <td><strong><?php esc_html_e('Total Submissions', 'lmb-core'); ?></strong></td>
                                <td><?php echo number_format($stats['total_submissions']); ?></td>
                            </tr>
                            <?php foreach ($stats['by_status'] as $status => $count): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?></strong></td>
                                <td><?php echo number_format($count); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render system status page
     */
    public static function render_system_status() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('System Status', 'lmb-core'); ?></h1>
            
            <div class="lmb-system-status">
                <?php self::render_system_health(); ?>
                
                <!-- Add more system status information here -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('System Information', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat">
                            <tr>
                                <td><strong><?php esc_html_e('Plugin Version', 'lmb-core'); ?></strong></td>
                                <td><?php echo esc_html(LMB_CORE_VERSION); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('WordPress Version', 'lmb-core'); ?></strong></td>
                                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                            </tr>
                            <tr>
                                <td><strong><?php esc_html_e('PHP Version', 'lmb-core'); ?></strong></td>
                                <td><?php echo esc_html(PHP_VERSION); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the enhanced admin
LMB_Enhanced_Admin::init();