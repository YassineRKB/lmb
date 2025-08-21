<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    private static $settings_tabs = [];

    public static function init() {
        self::$settings_tabs = [
            'general'        => __('General', 'lmb-core'),
            'templates'      => __('Templates', 'lmb-core'),
            'notifications'  => __('Notifications', 'lmb-core'),
            'security'       => __('Security', 'lmb-core'),
            'roles'          => __('Roles & Users', 'lmb-core'),
        ];

        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    /**
     * Top-level: LMB Core
     * Submenus: Dashboard, Settings, Error Logs (and CPTs via show_in_menu)
     */
    public static function add_admin_menu() {
        // Top-level
        add_menu_page(
            __('LMB Core', 'lmb-core'),
            __('LMB Core', 'lmb-core'),
            apply_filters('lmb_admin_capability', 'manage_options'),
            'lmb-core',
            [__CLASS__, 'render_dashboard_page'],
            'dashicons-analytics',
            25
        );

        // Dashboard
        add_submenu_page(
            'lmb-core',
            __('Dashboard', 'lmb-core'),
            __('Dashboard', 'lmb-core'),
            apply_filters('lmb_admin_capability', 'manage_options'),
            'lmb-core',
            [__CLASS__, 'render_dashboard_page'],
            0
        );

        // Settings
        add_submenu_page(
            'lmb-core',
            __('Settings', 'lmb-core'),
            __('Settings', 'lmb-core'),
            apply_filters('lmb_admin_capability', 'manage_options'),
            'lmb-settings',
            [__CLASS__, 'render_settings_page'],
            90
        );

        // Error Logs
        add_submenu_page(
            'lmb-core',
            __('Error Logs', 'lmb-core'),
            __('Error Logs', 'lmb-core'),
            apply_filters('lmb_admin_capability', 'manage_options'),
            'lmb-error-logs',
            ['LMB_Error_Handler', 'render_logs_page'],
            95
        );
    }

    /**
     * Register settings used in the tabs.
     */
    public static function register_settings() {
        // General
        register_setting('lmb_general_settings', 'lmb_bank_name');
        register_setting('lmb_general_settings', 'lmb_bank_iban');
        register_setting('lmb_general_settings', 'lmb_default_cost_per_ad');

        // Templates
        register_setting('lmb_templates_settings', 'lmb_invoice_template_html');
        register_setting('lmb_templates_settings', 'lmb_newspaper_template_html');
        register_setting('lmb_templates_settings', 'lmb_receipt_template_html');

        // Notifications
        register_setting('lmb_notifications_settings', 'lmb_enable_email_notifications');
        
        // Security
        register_setting('lmb_security_settings', 'lmb_protected_pages');
    }

    /**
     * Dashboard page
     */
    public static function render_dashboard_page() {
        if (!current_user_can(apply_filters('lmb_admin_capability', 'manage_options'))) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }

        $stats = self::collect_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Dashboard', 'lmb-core'); ?></h1>

            <div class="lmb-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:16px;">
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Total Users', 'lmb-core'); ?></h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['users_total']); ?></p>
                </div>
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Legal Ads (published)', 'lmb-core'); ?></h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['ads_published']); ?></p>
                </div>
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Legal Ads (draft/pending)', 'lmb-core'); ?></h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['ads_unpublished']); ?></p>
                </div>
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;"><?php esc_html_e('Newspapers', 'lmb-core'); ?></h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['news_total']); ?></p>
                </div>
            </div>

            <div style="margin-top:24px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                <h2 style="margin-top:0;"><?php esc_html_e('Quick Links', 'lmb-core'); ?></h2>
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=lmb_legal_ad')); ?>"><?php esc_html_e('Manage Legal Ads', 'lmb-core'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=lmb_newspaper')); ?>"><?php esc_html_e('Manage Newspapers', 'lmb-core'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=lmb-error-logs')); ?>"><?php esc_html_e('View Error Logs', 'lmb-core'); ?></a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=lmb-settings')); ?>"><?php esc_html_e('Settings', 'lmb-core'); ?></a></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page with tabs
     */
    public static function render_settings_page() {
        if (!current_user_can(apply_filters('lmb_admin_capability', 'manage_options'))) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }

        $current_tab = isset($_GET['tab']) && isset(self::$settings_tabs[$_GET['tab']])
            ? sanitize_key($_GET['tab'])
            : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Settings', 'lmb-core'); ?></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach (self::$settings_tabs as $tab_id => $tab_name): ?>
                    <?php
                        $tab_url = add_query_arg(['page' => 'lmb-settings', 'tab' => $tab_id], admin_url('admin.php'));
                        $active  = $current_tab === $tab_id ? ' nav-tab-active' : '';
                    ?>
                    <a href="<?php echo esc_url($tab_url); ?>" class="nav-tab<?php echo esc_attr($active); ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content" style="background:#fff;border:1px solid #e5e7eb;border-top:0;border-radius:0 0 12px 12px;padding:16px;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('lmb_' . $current_tab . '_settings');
                    $method = 'render_' . $current_tab . '_tab';
                    if (method_exists(__CLASS__, $method)) {
                        call_user_func([__CLASS__, $method]);
                    }
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    /*** Tabs ***/

    private static function render_general_tab() { ?>
        <table class="form-table" role="presentation">
            <tbody>
            <tr>
                <th scope="row"><label for="lmb_bank_name"><?php esc_html_e('Bank Name', 'lmb-core'); ?></label></th>
                <td><input name="lmb_bank_name" id="lmb_bank_name" type="text" class="regular-text" value="<?php echo esc_attr(get_option('lmb_bank_name', '')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="lmb_bank_iban"><?php esc_html_e('IBAN / RIB', 'lmb-core'); ?></label></th>
                <td><input name="lmb_bank_iban" id="lmb_bank_iban" type="text" class="regular-text" value="<?php echo esc_attr(get_option('lmb_bank_iban', '')); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="lmb_default_cost_per_ad"><?php esc_html_e('Default Cost / Ad (points)', 'lmb-core'); ?></label></th>
                <td><input name="lmb_default_cost_per_ad" id="lmb_default_cost_per_ad" type="number" min="0" step="1" class="small-text" value="<?php echo esc_attr(get_option('lmb_default_cost_per_ad', 0)); ?>"></td>
            </tr>
            </tbody>
        </table>
    <?php }

    private static function render_templates_tab() { ?>
        <h3><?php esc_html_e('Invoice Template', 'lmb-core'); ?></h3>
        <p class="description"><?php esc_html_e('Invoice template (HTML). You can use placeholders like {{invoice_number}}, {{user_name}}, {{package_name}}, {{package_price}}, {{payment_reference}}, {{our_bank_name}}, {{our_iban}}, etc.', 'lmb-core'); ?></p>
        <textarea name="lmb_invoice_template_html" rows="12" style="width:100%;"><?php echo esc_textarea(get_option('lmb_invoice_template_html', self::get_default_invoice_template())); ?></textarea>
        
        <h3><?php esc_html_e('Newspaper Template', 'lmb-core'); ?></h3>
        <p class="description"><?php esc_html_e('Newspaper template (HTML). You can use placeholders like {{newspaper_title}}, {{publication_date}}, {{ads_content}}, etc.', 'lmb-core'); ?></p>
        <textarea name="lmb_newspaper_template_html" rows="12" style="width:100%;"><?php echo esc_textarea(get_option('lmb_newspaper_template_html', self::get_default_newspaper_template())); ?></textarea>
        
        <h3><?php esc_html_e('Receipt Template', 'lmb-core'); ?></h3>
        <p class="description"><?php esc_html_e('Receipt template (HTML). You can use placeholders like {{ad_id}}, {{company_name}}, {{ad_type}}, {{publication_date}}, etc.', 'lmb-core'); ?></p>
        <textarea name="lmb_receipt_template_html" rows="12" style="width:100%;"><?php echo esc_textarea(get_option('lmb_receipt_template_html', self::get_default_receipt_template())); ?></textarea>
    <?php }

    private static function render_notifications_tab() { ?>
        <label>
            <input type="checkbox" name="lmb_enable_email_notifications" value="1" <?php checked(get_option('lmb_enable_email_notifications', 0), 1); ?>>
            <?php esc_html_e('Enable email notifications', 'lmb-core'); ?>
        </label>
    <?php }

    private static function render_security_tab() { 
        $protected_pages = get_option('lmb_protected_pages', []);
        $pages = get_pages();
        ?>
        <h3><?php esc_html_e('Page Access Control', 'lmb-core'); ?></h3>
        <p class="description"><?php esc_html_e('Configure access control for specific pages based on user roles.', 'lmb-core'); ?></p>
        
        <table class="form-table" role="presentation">
            <thead>
                <tr>
                    <th><?php esc_html_e('Page', 'lmb-core'); ?></th>
                    <th><?php esc_html_e('Access Level', 'lmb-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <?php $page_protection = isset($protected_pages[$page->ID]) ? $protected_pages[$page->ID] : 'public'; ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($page->post_title); ?></strong>
                            <br><small><?php echo esc_html($page->post_name); ?></small>
                        </td>
                        <td>
                            <select name="lmb_protected_pages[<?php echo $page->ID; ?>]">
                                <option value="public" <?php selected($page_protection, 'public'); ?>><?php esc_html_e('Public Access', 'lmb-core'); ?></option>
                                <option value="logged_in" <?php selected($page_protection, 'logged_in'); ?>><?php esc_html_e('Logged-in Users Only', 'lmb-core'); ?></option>
                                <option value="admin_only" <?php selected($page_protection, 'admin_only'); ?>><?php esc_html_e('Administrators Only', 'lmb-core'); ?></option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php }

    private static function render_roles_tab() { ?>
        <p><?php esc_html_e('Roles management is handled elsewhere in the plugin.', 'lmb-core'); ?></p>
    <?php }

    /**
     * Stats for the dashboard cards.
     */
    public static function collect_stats() {
        $ad_counts = (array) wp_count_posts('lmb_legal_ad');
        $news_counts = (array) wp_count_posts('lmb_newspaper');
        $user_counts = count_users();

        $ads_published = isset($ad_counts['publish']) ? (int) $ad_counts['publish'] : 0;
        $ads_draft = isset($ad_counts['draft']) ? (int) $ad_counts['draft'] : 0;
        // Note: The status is 'pending_review', not 'pending'
        $ads_pending = isset($ad_counts['pending_review']) ? (int) $ad_counts['pending_review'] : 0;
        
        return [
            'users_total'     => isset($user_counts['total_users']) ? (int) $user_counts['total_users'] : 0,
            'ads_published'   => $ads_published,
            'ads_unpublished' => $ads_draft + $ads_pending,
            'ads_total'       => $ads_published + $ads_draft + $ads_pending,
            'news_total'      => isset($news_counts['publish']) ? (int) $news_counts['publish'] : 0,
            'rev_year'        => 1250, // Placeholder value
        ];
    }
    
    private static function get_default_invoice_template() {
        return '<h1>Invoice {{invoice_number}}</h1>
<p>Date: {{invoice_date}}</p>
<hr>
<h3>Client Details</h3>
<p>Name: {{user_name}}<br>Email: {{user_email}}</p>
<hr>
<h3>Item Details</h3>
<p><strong>Package:</strong> {{package_name}}<br><strong>Price:</strong> {{package_price}} MAD</p>
<p><strong>Payment Reference:</strong> {{payment_reference}}</p>
<hr>
<h3>Payment Instructions</h3>
<p>Please make a bank transfer to:<br><strong>Bank:</strong> {{our_bank_name}}<br><strong>IBAN/RIB:</strong> {{our_iban}}</p>';
    }
    
    private static function get_default_newspaper_template() {
        return '<div style="text-align: center; margin-bottom: 30px;">
    <h1>{{newspaper_title}}</h1>
    <p>Publication Date: {{publication_date}}</p>
</div>
<hr>
<div>
    {{ads_content}}
</div>';
    }
    
    private static function get_default_receipt_template() {
        return '<div style="text-align: center; margin-bottom: 30px;">
    <h1>ACCUSE DE PUBLICATION</h1>
    <p>Legal Ad Receipt #{{ad_id}}</p>
</div>
<hr>
<h3>{{company_name}}</h3>
<p><strong>Ad Type:</strong> {{ad_type}}</p>
<p><strong>Publication Date:</strong> {{publication_date}}</p>
<hr>
<p>This document serves as proof of publication for the above legal advertisement.</p>';
    }
}
