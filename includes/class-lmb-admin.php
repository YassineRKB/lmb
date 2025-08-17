<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_admin_menu() {
        add_menu_page('LMB Core', 'LMB Core', 'manage_options', 'lmb-core', [__CLASS__, 'render_dashboard_page'], 'dashicons-analytics', 25);
        add_submenu_page('lmb-core', __('Dashboard', 'lmb-core'), __('Dashboard', 'lmb-core'), 'manage_options', 'lmb-core', [__CLASS__, 'render_dashboard_page']);
        add_submenu_page('lmb-core', __('Activity Log', 'lmb-core'), __('Activity Log', 'lmb-core'), 'manage_options', 'lmb-activity-log', [__CLASS__, 'render_activity_log_page']);
        add_submenu_page('lmb-core', __('Settings', 'lmb-core'), __('Settings', 'lmb-core'), 'manage_options', 'lmb-settings', [__CLASS__, 'render_settings_page']);
    }

    public static function register_settings() {
        register_setting('lmb_settings_group', 'lmb_invoice_template_html');
        register_setting('lmb_settings_group', 'lmb_bank_name');
        register_setting('lmb_settings_group', 'lmb_bank_iban');
        register_setting('lmb_settings_group', 'lmb_default_cost_per_ad', ['type' => 'integer', 'default' => 1]);
    }
    
    public static function render_dashboard_page() {
        $stats = self::collect_stats();
        ?>
        <div class="wrap lmb-admin-dashboard">
            <h1><?php esc_html_e('LMB Core Dashboard', 'lmb-core'); ?></h1>
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container">
                        <div class="meta-box-sortables">
                            <?php self::render_stats_widget($stats); ?>
                            <?php self::render_activity_widget(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public static function render_stats_widget($stats) {
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('System Overview', 'lmb-core'); ?></span></h2>
            <div class="inside">
                <div class="lmb-stats-grid">
                    <div class="lmb-stat-card"><h3><?php echo number_format($stats['users_total']); ?></h3><p>Total Users</p></div>
                    <div class="lmb-stat-card"><h3><?php echo number_format($stats['ads_total']); ?></h3><p>Total Legal Ads</p></div>
                    <div class="lmb-stat-card pending"><h3><?php echo number_format($stats['ads_pending']); ?></h3><p>Pending Ads</p></div>
                    <div class="lmb-stat-card"><h3><?php echo number_format($stats['news_total']); ?></h3><p>Total Newspapers</p></div>
                </div>
                <h4><?php esc_html_e('Revenue (Points Spent)', 'lmb-core'); ?></h4>
                <div class="lmb-revenue-grid">
                    <div><strong>Today:</strong> <?php echo number_format($stats['rev_today']); ?></div>
                    <div><strong>This Month:</strong> <?php echo number_format($stats['rev_month']); ?></div>
                    <div><strong>This Year:</strong> <?php echo number_format($stats['rev_year']); ?></div>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_activity_widget() {
        $log = get_option('lmb_activity_log', []);
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php esc_html_e('Recent Activity', 'lmb-core'); ?></span></h2>
            <div class="inside">
                <div class="lmb-activity-feed">
                    <?php if (empty($log)): ?>
                        <p>No activity recorded yet.</p>
                    <?php else: ?>
                        <ul>
                        <?php foreach (array_slice($log, 0, 10) as $item): 
                            $user = $item['user'] ? get_userdata($item['user']) : null;
                            ?>
                            <li>
                                <span class="activity-time"><?php echo esc_html(human_time_diff(strtotime($item['time']), current_time('timestamp')) . ' ago'); ?></span>
                                <span class="activity-user"><?php echo $user ? esc_html($user->display_name) : 'System'; ?></span>
                                <span class="activity-msg"><?php echo esc_html($item['msg']); ?></span>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <p style="text-align:right;"><a href="<?php echo admin_url('admin.php?page=lmb-activity-log'); ?>" class="button">View All Activity</a></p>
                </div>
            </div>
        </div>
        <?php
    }

    public static function render_activity_log_page() {
        $log = get_option('lmb_activity_log', []);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Full Activity Log', 'lmb-core'); ?></h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Timestamp', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('User', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Action', 'lmb-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($log)): ?>
                        <tr><td colspan="3"><?php esc_html_e('No activity recorded.', 'lmb-core'); ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($log as $item): 
                            $user = $item['user'] ? get_userdata($item['user']) : null;
                            ?>
                            <tr>
                                <td><?php echo esc_html($item['time']); ?></td>
                                <td><?php echo $user ? esc_html($user->display_name) : 'System'; ?></td>
                                <td><?php echo esc_html($item['msg']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function render_settings_page() { ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Settings','lmb-core'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('lmb_settings_group'); ?>
                <?php do_settings_sections('lmb_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Bank Name','lmb-core'); ?></th>
                        <td><input type="text" name="lmb_bank_name" value="<?php echo esc_attr(get_option('lmb_bank_name')); ?>" class="regular-text"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('IBAN/RIB','lmb-core'); ?></th>
                        <td><input type="text" name="lmb_bank_iban" value="<?php echo esc_attr(get_option('lmb_bank_iban')); ?>" class="regular-text"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Default Cost per Ad (Points)','lmb-core'); ?></th>
                        <td><input type="number" name="lmb_default_cost_per_ad" value="<?php echo (int) get_option('lmb_default_cost_per_ad', 1); ?>" min="0"></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('Invoice HTML Template','lmb-core'); ?></th>
                        <td>
                            <textarea name="lmb_invoice_template_html" rows="15" class="large-text"><?php echo esc_textarea(get_option('lmb_invoice_template_html')); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Available variables:', 'lmb-core'); ?>
                                <code>{{invoice_number}}, {{invoice_date}}, {{user_id}}, {{user_name}}, {{user_email}}, {{package_name}}, {{package_price}}, {{payment_reference}}, {{our_bank_name}}, {{our_iban}}, {{ad_id}}, {{ad_cost_points}}, {{points_after}}</code>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

    public static function collect_stats() {
        global $wpdb;
        $stats = [];
        $stats['users_total'] = count_users()['total_users'] ?? 0;
        $stats['ads_total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lmb_legal_ad'");
        $stats['ads_pending'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'lmb_status' AND meta_value = %s", 'pending_review'));
        $stats['news_total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lmb_newspaper'");

        $transactions_table = $wpdb->prefix . 'lmb_points_transactions';
        $stats['rev_today'] = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(ABS(amount)) FROM {$transactions_table} WHERE transaction_type = 'debit' AND DATE(created_at) = %s", current_time('Y-m-d')));
        $stats['rev_month'] = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(ABS(amount)) FROM {$transactions_table} WHERE transaction_type = 'debit' AND DATE_FORMAT(created_at, '%%Y-%%m') = %s", current_time('Y-m')));
        $stats['rev_year'] = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(ABS(amount)) FROM {$transactions_table} WHERE transaction_type = 'debit' AND YEAR(created_at) = %s", current_time('Y')));
        
        return $stats;
    }
}