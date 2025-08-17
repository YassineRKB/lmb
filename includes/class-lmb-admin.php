<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_init', [__CLASS__, 'settings']);
    }

    public static function menu() {
        add_menu_page('LMB Core', 'LMB Core', 'manage_options', 'lmb-core', [__CLASS__, 'dashboard'], 'dashicons-hammer');
        add_submenu_page('lmb-core', __('Settings','lmb-core'), __('Settings','lmb-core'), 'manage_options', 'lmb-core-settings', [__CLASS__, 'settings_page']);
        // CPT menus are already attached via 'show_in_menu' => 'lmb-core'
    }

    public static function settings() {
        register_setting('lmb_core', 'lmb_invoice_template_html');
        register_setting('lmb_core', 'lmb_bank_name');
        register_setting('lmb_core', 'lmb_bank_iban');
        register_setting('lmb_core', 'lmb_default_cost_per_ad', ['type'=>'integer', 'default'=>1]);
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
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    <?php }

    /** wp-admin dashboard page with stats + recent log */
    public static function dashboard() {
        $stats = self::collect_stats();
        $log   = get_option('lmb_activity_log', []);
        ?>
        <div class="wrap">
            <h1>LMB Admin Dashboard</h1>
            <h2><?php esc_html_e('Statistics','lmb-core'); ?></h2>
            <ul>
                <li><?php printf(__('Total Users: %d','lmb-core'), $stats['users_total']); ?></li>
                <li><?php printf(__('Total Legal Ads: %d','lmb-core'), $stats['ads_total']); ?></li>
                <li><?php printf(__('Total Newspapers: %d','lmb-core'), $stats['news_total']); ?></li>
                <li><?php printf(__('Revenue Today (points): %d','lmb-core'), $stats['rev_today']); ?></li>
                <li><?php printf(__('Revenue This Month (points): %d','lmb-core'), $stats['rev_month']); ?></li>
                <li><?php printf(__('Revenue This Year (points): %d','lmb-core'), $stats['rev_year']); ?></li>
            </ul>

            <h2><?php esc_html_e('Recent Activity','lmb-core'); ?></h2>
            <table class="widefat">
                <thead><tr><th><?php esc_html_e('Time','lmb-core'); ?></th><th><?php esc_html_e('User','lmb-core'); ?></th><th><?php esc_html_e('Event','lmb-core'); ?></th></tr></thead>
                <tbody>
                <?php foreach ($log as $row): $u = get_userdata($row['user']); ?>
                    <tr>
                        <td><?php echo esc_html($row['time']); ?></td>
                        <td><?php echo $u ? esc_html($u->user_login) : '-'; ?></td>
                        <td><?php echo esc_html($row['msg']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php }

    /** Count revenue by scanning accepted/published ads and summing cost points */
    public static function collect_stats() {
        $users_total = count_users()['total_users'] ?? 0;

        $ads = new WP_Query(['post_type'=>'lmb_legal_ad','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids']);
        $ads_total = $ads->post_count;

        $news = new WP_Query(['post_type'=>'lmb_newspaper','post_status'=>'any','posts_per_page'=>-1,'fields'=>'ids']);
        $news_total = $news->post_count;

        $rev_today=0; $rev_month=0; $rev_year=0;

        $pub_ads = new WP_Query(['post_type'=>'lmb_legal_ad','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids']);
        $today = date('Y-m-d'); $month = date('Y-m'); $year = date('Y');

        foreach($pub_ads->posts as $pid){
            $cost = (int) get_post_meta($pid, '_ad_cost_points', true);
            $date = get_the_date('Y-m-d', $pid);
            if (!$cost) continue;
            if (strpos($date, $today) === 0) $rev_today += $cost;
            if (strpos($date, $month) === 0) $rev_month += $cost;
            if (strpos($date, $year) === 0)  $rev_year  += $cost;
        }

        return [
            'users_total' => (int) $users_total,
            'ads_total'   => (int) $ads_total,
            'news_total'  => (int) $news_total,
            'rev_today'   => (int) $rev_today,
            'rev_month'   => (int) $rev_month,
            'rev_year'    => (int) $rev_year,
        ];
    }
}
