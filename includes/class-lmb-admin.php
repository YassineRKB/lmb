<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'settings']);
    }

    public static function menu() {
        add_menu_page('LMB Core', 'LMB Core', 'manage_options', 'lmb-core', [__CLASS__, 'dashboard'], 'dashicons-hammer');
        
        add_submenu_page('lmb-core', __('Dashboard','lmb-core'), __('Dashboard','lmb-core'), 'manage_options', 'lmb-core', [__CLASS__, 'dashboard']);
        add_submenu_page('lmb-core', __('Packages','lmb-core'), __('Packages','lmb-core'), 'manage_options', 'lmb-packages', [__CLASS__, 'packages_page']);
        add_submenu_page('lmb-core', __('Settings','lmb-core'), __('Settings','lmb-core'), 'manage_options', 'lmb-core-settings', [__CLASS__, 'settings_page']);
        
        // CPT menus are already attached via 'show_in_menu' => 'lmb-core'
    }

    public static function settings() {
        register_setting('lmb_core', 'lmb_invoice_template_html');
        register_setting('lmb_core', 'lmb_bank_name');
        register_setting('lmb_core', 'lmb_bank_iban');
        register_setting('lmb_core', 'lmb_default_cost_per_ad', ['type'=>'integer', 'default'=>1]);
        register_setting('lmb_core', 'lmb_protected_slugs');
        register_setting('lmb_core', 'lmb_staff_roles');
    }
    
    public static function packages_page() {
        if (isset($_POST['create_package'])) {
            self::create_package();
        }
        
        $packages = get_posts([
            'post_type' => 'lmb_package',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Packages Management', 'lmb-core'); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e('Create New Package', 'lmb-core'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('create_package'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="package_name"><?php esc_html_e('Package Name', 'lmb-core'); ?></label></th>
                            <td><input type="text" name="package_name" id="package_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="package_price"><?php esc_html_e('Price (MAD)', 'lmb-core'); ?></label></th>
                            <td><input type="number" name="package_price" id="package_price" step="0.01" required></td>
                        </tr>
                        <tr>
                            <th><label for="package_points"><?php esc_html_e('Points', 'lmb-core'); ?></label></th>
                            <td><input type="number" name="package_points" id="package_points" required></td>
                        </tr>
                        <tr>
                            <th><label for="cost_per_ad"><?php esc_html_e('Cost per Ad', 'lmb-core'); ?></label></th>
                            <td><input type="number" name="cost_per_ad" id="cost_per_ad" required></td>
                        </tr>
                        <tr>
                            <th><label for="package_description"><?php esc_html_e('Description', 'lmb-core'); ?></label></th>
                            <td><textarea name="package_description" id="package_description" rows="4" class="large-text"></textarea></td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="create_package" class="button button-primary"><?php esc_html_e('Create Package', 'lmb-core'); ?></button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2><?php esc_html_e('Existing Packages', 'lmb-core'); ?></h2>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Price', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Points', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Cost per Ad', 'lmb-core'); ?></th>
                            <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($packages as $package): ?>
                        <tr>
                            <td><?php echo esc_html($package->post_title); ?></td>
                            <td><?php echo esc_html(get_post_meta($package->ID, 'price', true)); ?> MAD</td>
                            <td><?php echo esc_html(get_post_meta($package->ID, 'points', true)); ?></td>
                            <td><?php echo esc_html(get_post_meta($package->ID, 'cost_per_ad', true)); ?></td>
                            <td>
                                <a href="<?php echo get_edit_post_link($package->ID); ?>" class="button button-small"><?php esc_html_e('Edit', 'lmb-core'); ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private static function create_package() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'create_package')) {
            wp_die('Security check failed');
        }
        
        $package_id = wp_insert_post([
            'post_type' => 'lmb_package',
            'post_title' => sanitize_text_field($_POST['package_name']),
            'post_content' => wp_kses_post($_POST['package_description']),
            'post_status' => 'publish'
        ]);
        
        if (!is_wp_error($package_id)) {
            update_post_meta($package_id, 'price', floatval($_POST['package_price']));
            update_post_meta($package_id, 'points', intval($_POST['package_points']));
            update_post_meta($package_id, 'cost_per_ad', intval($_POST['cost_per_ad']));
            
            echo '<div class="notice notice-success"><p>Package created successfully!</p></div>';
        }
    }

    public static function settings_page() { ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Settings','lmb-core'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('lmb_core'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Invoice HTML Template','lmb-core'); ?></th>
                        <td>
                            <p><?php esc_html_e('Use variables like','lmb-core'); ?> <code>{{invoice_number}} {{invoice_date}} {{user_id}} {{user_name}} {{user_email}} {{package_name}} {{package_price}} {{package_details}} {{payment_reference}} {{our_bank_name}} {{our_iban}} {{ad_id}} {{ad_cost_points}} {{points_after}}</code></p>
                            <textarea name="lmb_invoice_template_html" rows="12" class="large-text"><?php echo esc_textarea(get_option('lmb_invoice_template_html','')); ?></textarea>
                        </td>
                    </tr>
                    <tr><th><?php esc_html_e('Bank Name','lmb-core'); ?></th>
                        <td><input type="text" name="lmb_bank_name" value="<?php echo esc_attr(get_option('lmb_bank_name','Your Bank')); ?>" class="regular-text"></td>
                    </tr>
                    <tr><th><?php esc_html_e('IBAN/RIB','lmb-core'); ?></th>
                        <td><input type="text" name="lmb_bank_iban" value="<?php echo esc_attr(get_option('lmb_bank_iban','YOUR-IBAN-RIB')); ?>" class="regular-text"></td>
                    </tr>
                    <tr><th><?php esc_html_e('Default Cost per Ad (points)','lmb-core'); ?></th>
                        <td><input type="number" name="lmb_default_cost_per_ad" value="<?php echo (int) get_option('lmb_default_cost_per_ad',1); ?>"></td>
                    </tr>
                   <tr><th><?php esc_html_e('Protected Slugs','lmb-core'); ?></th>
                       <td>
                           <textarea name="lmb_protected_slugs" rows="4" class="large-text"><?php echo esc_textarea(get_option('lmb_protected_slugs', "/dashboard\n/administration")); ?></textarea>
                           <p class="description"><?php esc_html_e('One slug per line. These pages will require authentication.', 'lmb-core'); ?></p>
                       </td>
                   </tr>
                   <tr><th><?php esc_html_e('Staff Roles','lmb-core'); ?></th>
                       <td>
                           <input type="text" name="lmb_staff_roles" value="<?php echo esc_attr(get_option('lmb_staff_roles', 'administrator,editor')); ?>" class="regular-text">
                           <p class="description"><?php esc_html_e('Comma-separated list of roles that can bypass payment requirements.', 'lmb-core'); ?></p>
                       </td>
                   </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

    /** wp-admin dashboard page with stats + recent log */
    public static function dashboard() {
        $stats = self::collect_stats();
        $log   = get_option('lmb_activity_log', []);
        
        // Enqueue admin assets
        wp_enqueue_style('lmb-admin', LMB_CORE_URL . 'assets/css/admin.css', [], LMB_CORE_VERSION);
        wp_enqueue_script('lmb-admin', LMB_CORE_URL . 'assets/js/admin.js', ['jquery'], LMB_CORE_VERSION, true);
        wp_localize_script('lmb-admin', 'lmbAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_admin_nonce'),
            'strings' => [
                'confirm_status_change' => __('Are you sure you want to change the status?', 'lmb-core'),
                'status_changed' => __('Status changed successfully.', 'lmb-core'),
                'error_occurred' => __('An error occurred.', 'lmb-core'),
                'confirm_bulk_action' => __('Are you sure you want to perform this bulk action?', 'lmb-core')
            ]
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Dashboard', 'lmb-core'); ?></h1>
            
            <!-- Metrics Grid -->
            <div class="lmb-metrics-grid">
                <div class="lmb-metric-card info">
                    <div class="lmb-metric-icon">👥</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['users_total']); ?></h3>
                        <p><?php esc_html_e('Total Users', 'lmb-core'); ?></p>
                        <a href="<?php echo admin_url('users.php'); ?>" class="lmb-action-link"><?php esc_html_e('Manage Users', 'lmb-core'); ?></a>
                    </div>
                </div>
                
                <div class="lmb-metric-card pending">
                    <div class="lmb-metric-icon">📄</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['ads_pending']); ?></h3>
                        <p><?php esc_html_e('Pending Ads', 'lmb-core'); ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad&lmb_status=pending_review'); ?>" class="lmb-action-link"><?php esc_html_e('Review Ads', 'lmb-core'); ?></a>
                    </div>
                </div>
                
                <div class="lmb-metric-card success">
                    <div class="lmb-metric-icon">📰</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['news_total']); ?></h3>
                        <p><?php esc_html_e('Newspapers', 'lmb-core'); ?></p>
                        <a href="<?php echo admin_url('edit.php?post_type=lmb_newspaper'); ?>" class="lmb-action-link"><?php esc_html_e('Manage', 'lmb-core'); ?></a>
                    </div>
                </div>
                
                <div class="lmb-metric-card info">
                    <div class="lmb-metric-icon">💰</div>
                    <div class="lmb-metric-content">
                        <h3><?php echo number_format($stats['rev_month']); ?></h3>
                        <p><?php esc_html_e('Monthly Revenue (Points)', 'lmb-core'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="lmb-dashboard-row">
                <div class="lmb-dashboard-col-8">
                    <!-- Recent Activity -->
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('Recent Activity', 'lmb-core'); ?></h2>
                        <div class="inside">
                            <div class="lmb-activity-feed">
                                <?php foreach (array_slice($log, 0, 10) as $row): 
                                    $user = get_userdata($row['user']); ?>
                                    <div class="lmb-activity-item">
                                        <div class="lmb-activity-time"><?php echo esc_html(human_time_diff(strtotime($row['time']), current_time('timestamp')) . ' ago'); ?></div>
                                        <div class="lmb-activity-user"><?php echo $user ? esc_html($user->display_name) : 'System'; ?></div>
                                        <div class="lmb-activity-message"><?php echo esc_html($row['msg']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lmb-dashboard-col-4">
                    <!-- Quick Actions -->
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('Quick Actions', 'lmb-core'); ?></h2>
                        <div class="inside">
                            <div class="lmb-quick-actions">
                                <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad&lmb_status=pending_review'); ?>" class="button button-primary">
                                    <?php esc_html_e('Review Pending Ads', 'lmb-core'); ?>
                                    <?php if ($stats['ads_pending'] > 0): ?>
                                        <span class="lmb-badge"><?php echo $stats['ads_pending']; ?></span>
                                    <?php endif; ?>
                                </a>
                                
                                <a href="<?php echo admin_url('edit.php?post_type=lmb_payment'); ?>" class="button">
                                    <?php esc_html_e('Verify Payments', 'lmb-core'); ?>
                                    <?php if ($stats['payments_pending'] > 0): ?>
                                        <span class="lmb-badge"><?php echo $stats['payments_pending']; ?></span>
                                    <?php endif; ?>
                                </a>
                                
                                <a href="<?php echo admin_url('admin.php?page=lmb-packages'); ?>" class="button">
                                    <?php esc_html_e('Manage Packages', 'lmb-core'); ?>
                                </a>
                                
                                <a href="<?php echo admin_url('post-new.php?post_type=lmb_newspaper'); ?>" class="button">
                                    <?php esc_html_e('Add Newspaper', 'lmb-core'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Health -->
                    <div class="lmb-dashboard-widget">
                        <h2><?php esc_html_e('System Health', 'lmb-core'); ?></h2>
                        <div class="inside">
                            <?php $health = self::get_system_health(); ?>
                            <?php foreach ($health as $check): ?>
                                <div class="lmb-health-check">
                                    <span class="lmb-health-icon <?php echo $check['status'] === 'good' ? 'lmb-status-good' : 'lmb-status-error'; ?>">
                                        <?php echo $check['status'] === 'good' ? '✓' : '✗'; ?>
                                    </span>
                                    <span class="lmb-health-label"><?php echo esc_html($check['label']); ?></span>
                                    <span class="lmb-health-message"><?php echo esc_html($check['message']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php }

    /** Count revenue by scanning accepted/published ads and summing cost points */
    public static function collect_stats() {
        global $wpdb;
        
        $users_total = count_users()['total_users'] ?? 0;

        // Get ads by status using ACF meta
        $ads_total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p 
             WHERE p.post_type = 'lmb_legal_ad'"
        );
        
        $ads_pending = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = 'lmb_status' 
             AND pm.meta_value = 'pending_review' 
             AND p.post_type = 'lmb_legal_ad'"
        );

        $news_total = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_type = 'lmb_newspaper'"
        );
        
        $payments_pending = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE pm.meta_key = 'payment_status' 
             AND pm.meta_value = 'pending' 
             AND p.post_type = 'lmb_payment'"
        );

        // Calculate revenue from points transactions
        $today = date('Y-m-d');
        $month = date('Y-m');
        $year = date('Y');
        
        $transactions_table = $wpdb->prefix . 'lmb_points_transactions';
        
        $rev_today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(ABS(amount)) FROM {$transactions_table} 
             WHERE transaction_type = 'debit' 
             AND DATE(created_at) = %s",
            $today
        ));
        
        $rev_month = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(ABS(amount)) FROM {$transactions_table} 
             WHERE transaction_type = 'debit' 
             AND DATE_FORMAT(created_at, '%%Y-%%m') = %s",
            $month
        ));
        
        $rev_year = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(ABS(amount)) FROM {$transactions_table} 
             WHERE transaction_type = 'debit' 
             AND YEAR(created_at) = %s",
            $year
        ));

        return [
            'users_total' => (int) $users_total,
            'ads_total'   => (int) $ads_total,
            'ads_pending' => (int) $ads_pending,
            'news_total'  => (int) $news_total,
            'payments_pending' => (int) $payments_pending,
            'rev_today'   => (int) $rev_today,
            'rev_month'   => (int) $rev_month,
            'rev_year'    => (int) $rev_year,
        ];
    }
    
    /**
     * Get system health checks
     */
    public static function get_system_health() {
        $checks = [];
        
        // Check ACF
        $checks[] = [
            'label' => 'ACF Plugin',
            'status' => function_exists('acf_add_local_field_group') ? 'good' : 'error',
            'message' => function_exists('acf_add_local_field_group') ? 'Active' : 'Not installed'
        ];
        
        // Check Elementor
        $checks[] = [
            'label' => 'Elementor',
            'status' => defined('ELEMENTOR_VERSION') ? 'good' : 'error',
            'message' => defined('ELEMENTOR_VERSION') ? 'Active' : 'Not installed'
        ];
        
        // Check upload directory
        $upload_dir = wp_upload_dir();
        $checks[] = [
            'label' => 'Upload Directory',
            'status' => wp_is_writable($upload_dir['basedir']) ? 'good' : 'error',
            'message' => wp_is_writable($upload_dir['basedir']) ? 'Writable' : 'Not writable'
        ];
        
        // Check database tables
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}lmb_points_transactions'");
        $checks[] = [
            'label' => 'Database Tables',
            'status' => $table_exists ? 'good' : 'error',
            'message' => $table_exists ? 'All tables exist' : 'Missing tables'
        ];
        
        return $checks;
    }
}
