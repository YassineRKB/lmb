<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * System maintenance and utility functions
 */
class LMB_Maintenance {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_maintenance_page']);
        add_action('wp_ajax_lmb_run_maintenance', [__CLASS__, 'ajax_run_maintenance']);
        add_action('admin_init', [__CLASS__, 'handle_maintenance_actions']);
    }

    /**
     * Add maintenance page to admin menu
     */
    public static function add_maintenance_page() {
        add_submenu_page(
            'lmb-core',
            __('System Maintenance', 'lmb-core'),
            __('Maintenance', 'lmb-core'),
            'manage_options',
            'lmb-maintenance',
            [__CLASS__, 'render_maintenance_page']
        );
    }

    /**
     * Render maintenance page
     */
    public static function render_maintenance_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }

        $stats = LMB_Database_Manager::get_database_stats();
        $system_info = self::get_system_info();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core System Maintenance', 'lmb-core'); ?></h1>

            <?php if (isset($_GET['action']) && $_GET['action'] === 'completed'): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e('Maintenance task completed successfully.', 'lmb-core'); ?></p>
                </div>
            <?php endif; ?>

            <div class="postbox-container" style="width: 70%;">
                <!-- System Statistics -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('System Statistics', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e('Total Legal Ads', 'lmb-core'); ?></strong></td>
                                    <td><?php echo number_format($stats['posts']['lmb_legal_ad']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Total Newspapers', 'lmb-core'); ?></strong></td>
                                    <td><?php echo number_format($stats['posts']['lmb_newspaper']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Points Ledger Entries', 'lmb-core'); ?></strong></td>
                                    <td><?php echo number_format($stats['posts']['lmb_points_ledger']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Users with Points', 'lmb-core'); ?></strong></td>
                                    <td><?php echo number_format($stats['users_with_points']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Total Points in System', 'lmb-core'); ?></strong></td>
                                    <td><?php echo number_format($stats['total_points']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Database Size', 'lmb-core'); ?></strong></td>
                                    <td><?php echo $stats['database_size']; ?> MB</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Maintenance Actions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('Maintenance Actions', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <form method="post" action="">
                            <?php wp_nonce_field('lmb_maintenance_action', 'lmb_maintenance_nonce'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php esc_html_e('Database Cleanup', 'lmb-core'); ?></th>
                                    <td>
                                        <button type="submit" name="action" value="cleanup_database" class="button">
                                            <?php esc_html_e('Clean Database', 'lmb-core'); ?>
                                        </button>
                                        <p class="description">
                                            <?php esc_html_e('Remove orphaned records and optimize database tables.', 'lmb-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Repair Database', 'lmb-core'); ?></th>
                                    <td>
                                        <button type="submit" name="action" value="repair_database" class="button button-secondary">
                                            <?php esc_html_e('Repair Database', 'lmb-core'); ?>
                                        </button>
                                        <p class="description">
                                            <?php esc_html_e('Fix corrupted data and missing fields.', 'lmb-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Recalculate Points', 'lmb-core'); ?></th>
                                    <td>
                                        <button type="submit" name="action" value="recalculate_points" class="button">
                                            <?php esc_html_e('Recalculate All Points', 'lmb-core'); ?>
                                        </button>
                                        <p class="description">
                                            <?php esc_html_e('Recalculate user points based on ledger entries.', 'lmb-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Clear Cache', 'lmb-core'); ?></th>
                                    <td>
                                        <button type="submit" name="action" value="clear_cache" class="button">
                                            <?php esc_html_e('Clear All Caches', 'lmb-core'); ?>
                                        </button>
                                        <p class="description">
                                            <?php esc_html_e('Clear all LMB-related cached data.', 'lmb-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Export Data', 'lmb-core'); ?></th>
                                    <td>
                                        <button type="submit" name="action" value="export_data" class="button">
                                            <?php esc_html_e('Export System Data', 'lmb-core'); ?>
                                        </button>
                                        <p class="description">
                                            <?php esc_html_e('Export all LMB data as JSON for backup.', 'lmb-core'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>

            <div class="postbox-container" style="width: 25%; margin-left: 5%;">
                <!-- System Information -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('System Information', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <table class="widefat">
                            <tbody>
                                <tr>
                                    <td><strong><?php esc_html_e('Plugin Version', 'lmb-core'); ?></strong></td>
                                    <td><?php echo esc_html(LMB_CORE_VERSION); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('WordPress Version', 'lmb-core'); ?></strong></td>
                                    <td><?php echo esc_html($system_info['wp_version']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('PHP Version', 'lmb-core'); ?></strong></td>
                                    <td><?php echo esc_html($system_info['php_version']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('MySQL Version', 'lmb-core'); ?></strong></td>
                                    <td><?php echo esc_html($system_info['mysql_version']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Memory Limit', 'lmb-core'); ?></strong></td>
                                    <td><?php echo esc_html($system_info['memory_limit']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Max Upload Size', 'lmb-core'); ?></strong></td>
                                    <td><?php echo esc_html($system_info['upload_max_size']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('ACF Active', 'lmb-core'); ?></strong></td>
                                    <td><?php echo $system_info['acf_active'] ? '✓' : '✗'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Elementor Active', 'lmb-core'); ?></strong></td>
                                    <td><?php echo $system_info['elementor_active'] ? '✓' : '✗'; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('Quick Actions', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=lmb-core'); ?>" class="button">
                                <?php esc_html_e('Settings', 'lmb-core'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=lmb-core-points'); ?>" class="button">
                                <?php esc_html_e('Manage Points', 'lmb-core'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('edit.php?post_type=lmb_legal_ad'); ?>" class="button">
                                <?php esc_html_e('Legal Ads', 'lmb-core'); ?>
                            </a>
                        </p>
                        <p>
                            <a href="<?php echo admin_url('edit.php?post_type=lmb_newspaper'); ?>" class="button">
                                <?php esc_html_e('Newspapers', 'lmb-core'); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Handle maintenance actions
     */
    public static function handle_maintenance_actions() {
        if (!isset($_POST['action']) || !isset($_POST['lmb_maintenance_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['lmb_maintenance_nonce'], 'lmb_maintenance_action')) {
            wp_die(__('Security check failed.', 'lmb-core'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'lmb-core'));
        }

        $action = sanitize_text_field($_POST['action']);
        $redirect_url = admin_url('admin.php?page=lmb-maintenance&action=completed');

        switch ($action) {
            case 'cleanup_database':
                self::cleanup_database();
                break;

            case 'repair_database':
                self::repair_database();
                break;

            case 'recalculate_points':
                self::recalculate_all_points();
                break;

            case 'clear_cache':
                self::clear_all_caches();
                break;

            case 'export_data':
                self::export_system_data();
                return; // Don't redirect, we're downloading a file

            default:
                wp_die(__('Invalid action.', 'lmb-core'));
        }

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Clean up database
     */
    private static function cleanup_database() {
        LMB_Database_Manager::daily_maintenance();
        
        // Additional cleanup
        delete_expired_transients();
        wp_cache_flush();
        
        LMB_Error_Handler::log_error('Database cleanup completed via maintenance page');
    }

    /**
     * Repair database
     */
    private static function repair_database() {
        $results = LMB_Database_Manager::repair_database();
        
        LMB_Error_Handler::log_error(
            'Database repair completed',
            ['results' => $results]
        );
    }

    /**
     * Recalculate all user points
     */
    private static function recalculate_all_points() {
        global $wpdb;
        
        // Get all users with points
        $users = get_users([
            'meta_key' => LMB_Points::META_KEY,
            'fields' => 'ID'
        ]);

        $recalculated = 0;
        
        foreach ($users as $user_id) {
            // Get all ledger entries for this user
            $ledger_entries = get_posts([
                'post_type' => 'lmb_points_ledger',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'lmb_user_id',
                        'value' => $user_id,
                        'compare' => '='
                    ]
                ],
                'orderby' => 'date',
                'order' => 'ASC'
            ]);

            $calculated_balance = 0;
            
            foreach ($ledger_entries as $entry) {
                $delta = get_post_meta($entry->ID, 'lmb_delta', true);
                $calculated_balance += (int) $delta;
            }

            // Update user's balance
            LMB_Points::set($user_id, max(0, $calculated_balance), 'Balance recalculated');
            $recalculated++;
        }

        LMB_Error_Handler::log_error(
            "Points recalculation completed for {$recalculated} users"
        );
    }

    /**
     * Clear all caches
     */
    private static function clear_all_caches() {
        // WordPress object cache
        wp_cache_flush();
        
        // Delete LMB-specific transients
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient%lmb%' 
             OR option_name LIKE '_transient%LMB%'"
        );

        // Clear cached stats
        delete_option('lmb_system_stats');
        
        // Clear any third-party caches if available
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }
        
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }
        
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
        }

        LMB_Error_Handler::log_error('All caches cleared');
    }

    /**
     * Export system data
     */
    private static function export_system_data() {
        $data = [
            'export_info' => [
                'version' => LMB_CORE_VERSION,
                'date' => current_time('mysql'),
                'site_url' => get_site_url()
            ],
            'legal_ads' => self::export_legal_ads(),
            'newspapers' => self::export_newspapers(),
            'users_points' => self::export_user_points(),
            'points_ledger' => self::export_points_ledger(),
            'settings' => self::export_settings()
        ];

        $json = wp_json_encode($data, JSON_PRETTY_PRINT);
        $filename = 'lmb-core-export-' . date('Y-m-d-H-i-s') . '.json';

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));

        echo $json;
        exit;
    }

    /**
     * Export legal ads
     */
    private static function export_legal_ads() {
        $ads = get_posts([
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $exported = [];
        foreach ($ads as $ad) {
            $exported[] = [
                'ID' => $ad->ID,
                'title' => $ad->post_title,
                'status' => $ad->post_status,
                'author' => $ad->post_author,
                'date' => $ad->post_date,
                'meta' => get_post_meta($ad->ID),
                'acf_fields' => [
                    'ad_type' => get_field('ad_type', $ad->ID),
                    'full_text' => get_field('full_text', $ad->ID),
                    'lmb_status' => get_field('lmb_status', $ad->ID),
                    'lmb_client_id' => get_field('lmb_client_id', $ad->ID),
                    'ad_pdf_url' => get_field('ad_pdf_url', $ad->ID)
                ]
            ];
        }

        return $exported;
    }

    /**
     * Export newspapers
     */
    private static function export_newspapers() {
        $newspapers = get_posts([
            'post_type' => 'lmb_newspaper',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $exported = [];
        foreach ($newspapers as $newspaper) {
            $exported[] = [
                'ID' => $newspaper->ID,
                'title' => $newspaper->post_title,
                'status' => $newspaper->post_status,
                'date' => $newspaper->post_date,
                'meta' => get_post_meta($newspaper->ID),
                'acf_fields' => [
                    'newspaper_pdf' => get_field('newspaper_pdf', $newspaper->ID)
                ]
            ];
        }

        return $exported;
    }

    /**
     * Export user points
     */
    private static function export_user_points() {
        $users = get_users([
            'meta_key' => LMB_Points::META_KEY,
            'fields' => ['ID', 'display_name', 'user_email']
        ]);

        $exported = [];
        foreach ($users as $user) {
            $exported[] = [
                'user_id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'points' => LMB_Points::get($user->ID)
            ];
        }

        return $exported;
    }

    /**
     * Export points ledger
     */
    private static function export_points_ledger() {
        $ledger = get_posts([
            'post_type' => 'lmb_points_ledger',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $exported = [];
        foreach ($ledger as $entry) {
            $exported[] = [
                'ID' => $entry->ID,
                'title' => $entry->post_title,
                'date' => $entry->post_date,
                'user_id' => get_post_meta($entry->ID, 'lmb_user_id', true),
                'delta' => get_post_meta($entry->ID, 'lmb_delta', true),
                'reason' => get_post_meta($entry->ID, 'lmb_reason', true),
                'balance' => get_post_meta($entry->ID, 'lmb_balance', true)
            ];
        }

        return $exported;
    }

    /**
     * Export settings
     */
    private static function export_settings() {
        return [
            'lmb_points_per_ad' => get_option('lmb_points_per_ad'),
            'lmb_protected_slugs' => get_option('lmb_protected_slugs'),
            'lmb_staff_roles' => get_option('lmb_staff_roles')
        ];
    }

    /**
     * Get system information
     */
    private static function get_system_info() {
        global $wpdb;

        return [
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'mysql_version' => $wpdb->get_var('SELECT VERSION()'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_size' => size_format(wp_max_upload_size()),
            'acf_active' => function_exists('acf_add_local_field_group'),
            'elementor_active' => defined('ELEMENTOR_VERSION')
        ];
    }

    /**
     * AJAX handler for maintenance tasks
     */
    public static function ajax_run_maintenance() {
        if (!wp_verify_nonce($_POST['nonce'], 'lmb_maintenance') || 
            !current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        $task = sanitize_text_field($_POST['task']);
        
        switch ($task) {
            case 'check_integrity':
                $result = self::check_system_integrity();
                break;
            case 'optimize_database':
                $result = self::optimize_database();
                break;
            default:
                wp_send_json_error('Invalid task');
        }

        wp_send_json_success($result);
    }

    /**
     * Check system integrity
     */
    private static function check_system_integrity() {
        $issues = [];

        // Check required plugins
        if (!function_exists('acf_add_local_field_group')) {
            $issues[] = __('Advanced Custom Fields is not active.', 'lmb-core');
        }

        if (!defined('ELEMENTOR_VERSION')) {
            $issues[] = __('Elementor is not active.', 'lmb-core');
        }

        // Check file permissions
        $upload_dir = wp_upload_dir();
        if (!wp_is_writable($upload_dir['basedir'])) {
            $issues[] = __('Upload directory is not writable.', 'lmb-core');
        }

        // Check database integrity
        global $wpdb;
        $orphaned = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm 
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
             WHERE p.ID IS NULL AND pm.meta_key LIKE 'lmb_%'"
        );

        if ($orphaned > 0) {
            $issues[] = sprintf(__('%d orphaned meta records found.', 'lmb-core'), $orphaned);
        }

        return [
            'status' => empty($issues) ? 'good' : 'issues',
            'issues' => $issues
        ];
    }

    /**
     * Optimize database
     */
    private static function optimize_database() {
        global $wpdb;

        $tables = [
            $wpdb->posts,
            $wpdb->postmeta,
            $wpdb->users,
            $wpdb->usermeta,
            $wpdb->options
        ];

        $optimized = 0;
        foreach ($tables as $table) {
            $result = $wpdb->query("OPTIMIZE TABLE {$table}");
            if ($result !== false) {
                $optimized++;
            }
        }

        return [
            'optimized_tables' => $optimized,
            'total_tables' => count($tables)
        ];
    }
}

/**
 * Import/Export utilities
 */
class LMB_Import_Export {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_import_export_page']);
        add_action('admin_post_lmb_import_data', [__CLASS__, 'handle_import']);
    }

    /**
     * Add import/export page
     */
    public static function add_import_export_page() {
        add_submenu_page(
            'lmb-core',
            __('Import/Export', 'lmb-core'),
            __('Import/Export', 'lmb-core'),
            'manage_options',
            'lmb-import-export',
            [__CLASS__, 'render_import_export_page']
        );
    }

    /**
     * Render import/export page
     */
    public static function render_import_export_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Import/Export LMB Data', 'lmb-core'); ?></h1>

            <div class="card">
                <h2><?php esc_html_e('Export Data', 'lmb-core'); ?></h2>
                <p><?php esc_html_e('Export all LMB Core data including ads, newspapers, user points, and settings.', 'lmb-core'); ?></p>
                <form method="post" action="<?php echo admin_url('admin.php?page=lmb-maintenance'); ?>">
                    <?php wp_nonce_field('lmb_maintenance_action', 'lmb_maintenance_nonce'); ?>
                    <input type="hidden" name="action" value="export_data">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Export All Data', 'lmb-core'); ?>
                    </button>
                </form>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Import Data', 'lmb-core'); ?></h2>
                <p><?php esc_html_e('Import LMB Core data from a previously exported JSON file.', 'lmb-core'); ?></p>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('lmb_import_data', 'lmb_import_nonce'); ?>
                    <input type="hidden" name="action" value="lmb_import_data">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php esc_html_e('Import File', 'lmb-core'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".json" required>
                                <p class="description">
                                    <?php esc_html_e('Select a JSON file exported from LMB Core.', 'lmb-core'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Import Options', 'lmb-core'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_ads" value="1" checked>
                                    <?php esc_html_e('Import Legal Ads', 'lmb-core'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="import_newspapers" value="1" checked>
                                    <?php esc_html_e('Import Newspapers', 'lmb-core'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="import_points" value="1" checked>
                                    <?php esc_html_e('Import User Points', 'lmb-core'); ?>
                                </label><br>
                                
                                <label>
                                    <input type="checkbox" name="import_settings" value="1">
                                    <?php esc_html_e('Import Settings (will overwrite current)', 'lmb-core'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Import Data', 'lmb-core'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <div class="card">
                <h2><?php esc_html_e('CSV Export', 'lmb-core'); ?></h2>
                <p><?php esc_html_e('Export specific data as CSV files for spreadsheet analysis.', 'lmb-core'); ?></p>
                
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lmb-import-export&export_csv=ads'), 'export_csv'); ?>" class="button">
                        <?php esc_html_e('Export Ads as CSV', 'lmb-core'); ?>
                    </a>
                </p>
                
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=lmb-import-export&export_csv=points'), 'export_csv'); ?>" class="button">
                        <?php esc_html_e('Export User Points as CSV', 'lmb-core'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php

        // Handle CSV export
        if (isset($_GET['export_csv']) && wp_verify_nonce($_GET['_wpnonce'], 'export_csv')) {
            $type = sanitize_text_field($_GET['export_csv']);
            self::export_csv($type);
        }
    }

    /**
     * Handle data import
     */
    public static function handle_import() {
        if (!wp_verify_nonce($_POST['lmb_import_nonce'], 'lmb_import_data') ||
            !current_user_can('manage_options')) {
            wp_die(__('Security check failed.', 'lmb-core'));
        }

        if (empty($_FILES['import_file'])) {
            wp_die(__('No file uploaded.', 'lmb-core'));
        }

        $file = $_FILES['import_file'];
        
        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('File upload error.', 'lmb-core'));
        }

        if (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            wp_die(__('Invalid file type. Please upload a JSON file.', 'lmb-core'));
        }

        // Read and parse JSON
        $json_content = file_get_contents($file['tmp_name']);
        $data = json_decode($json_content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_die(__('Invalid JSON file.', 'lmb-core'));
        }

        // Import data based on selections
        $results = [];

        if (!empty($_POST['import_ads']) && !empty($data['legal_ads'])) {
            $results['ads'] = self::import_legal_ads($data['legal_ads']);
        }

        if (!empty($_POST['import_newspapers']) && !empty($data['newspapers'])) {
            $results['newspapers'] = self::import_newspapers($data['newspapers']);
        }

        if (!empty($_POST['import_points']) && !empty($data['users_points'])) {
            $results['points'] = self::import_user_points($data['users_points']);
        }

        if (!empty($_POST['import_settings']) && !empty($data['settings'])) {
            $results['settings'] = self::import_settings($data['settings']);
        }

        // Redirect with success message
        $redirect_url = add_query_arg([
            'page' => 'lmb-import-export',
            'import' => 'success',
            'results' => urlencode(json_encode($results))
        ], admin_url('admin.php'));

        wp_redirect($redirect_url);
        exit;
    }

    /**
     * Import legal ads
     */
    private static function import_legal_ads($ads_data) {
        $imported = 0;
        $errors = 0;

        foreach ($ads_data as $ad_data) {
            $post_data = [
                'post_type' => 'lmb_legal_ad',
                'post_title' => $ad_data['title'],
                'post_status' => 'draft', // Always import as draft for safety
                'post_author' => $ad_data['author'],
            ];

            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                // Import ACF fields
                if (!empty($ad_data['acf_fields'])) {
                    foreach ($ad_data['acf_fields'] as $field_key => $field_value) {
                        if ($field_value) {
                            update_field($field_key, $field_value, $post_id);
                        }
                    }
                }
                $imported++;
            } else {
                $errors++;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import newspapers
     */
    private static function import_newspapers($newspapers_data) {
        $imported = 0;
        $errors = 0;

        foreach ($newspapers_data as $newspaper_data) {
            $post_data = [
                'post_type' => 'lmb_newspaper',
                'post_title' => $newspaper_data['title'],
                'post_status' => $newspaper_data['status'],
            ];

            $post_id = wp_insert_post($post_data);
            
            if (!is_wp_error($post_id)) {
                // Import ACF fields
                if (!empty($newspaper_data['acf_fields'])) {
                    foreach ($newspaper_data['acf_fields'] as $field_key => $field_value) {
                        if ($field_value) {
                            update_field($field_key, $field_value, $post_id);
                        }
                    }
                }
                $imported++;
            } else {
                $errors++;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import user points
     */
    private static function import_user_points($points_data) {
        $imported = 0;
        $errors = 0;

        foreach ($points_data as $point_data) {
            $user = get_user_by('email', $point_data['email']);
            
            if ($user) {
                LMB_Points::set($user->ID, $point_data['points'], 'Imported from backup');
                $imported++;
            } else {
                $errors++;
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /**
     * Import settings
     */
    private static function import_settings($settings_data) {
        $imported = 0;

        foreach ($settings_data as $option_name => $option_value) {
            update_option($option_name, $option_value);
            $imported++;
        }

        return ['imported' => $imported];
    }

    /**
     * Export data as CSV
     */
    private static function export_csv($type) {
        switch ($type) {
            case 'ads':
                self::export_ads_csv();
                break;
            case 'points':
                self::export_points_csv();
                break;
        }
    }

    /**
     * Export ads as CSV
     */
    private static function export_ads_csv() {
        $ads = get_posts([
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $filename = 'lmb-ads-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID',
            'Title',
            'Status',
            'Author ID',
            'Date Created',
            'Ad Type',
            'LMB Status',
            'Client ID'
        ]);

        foreach ($ads as $ad) {
            fputcsv($output, [
                $ad->ID,
                $ad->post_title,
                $ad->post_status,
                $ad->post_author,
                $ad->post_date,
                get_field('ad_type', $ad->ID),
                get_field('lmb_status', $ad->ID),
                get_field('lmb_client_id', $ad->ID)
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Export user points as CSV
     */
    private static function export_points_csv() {
        $users = get_users([
            'meta_key' => LMB_Points::META_KEY,
        ]);

        $filename = 'lmb-user-points-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'User ID',
            'Username',
            'Display Name',
            'Email',
            'Points Balance',
            'Last Updated'
        ]);

        foreach ($users as $user) {
            fputcsv($output, [
                $user->ID,
                $user->user_login,
                $user->display_name,
                $user->user_email,
                LMB_Points::get($user->ID),
                get_user_meta($user->ID, 'lmb_points_last_updated', true) ?: 'N/A'
            ]);
        }

        fclose($output);
        exit;
    }
}

// Initialize maintenance and import/export classes
LMB_Maintenance::init();
LMB_Import_Export::init();