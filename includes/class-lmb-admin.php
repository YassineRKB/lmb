<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    private static $settings_tabs = [];
    private static $settings_sub_tabs = []; // New property for sub-tabs

    public static function init() {
        // --- MODIFICATION: Removed 'accuse_newspaper' from main tabs ---
        self::$settings_tabs = [
            'general'        => __('General', 'lmb-core'),
            'templates'      => __('Templates', 'lmb-core'),
            'notifications'  => __('Notifications', 'lmb-core'),
            'security'       => __('Security', 'lmb-core'),
            'roles'          => __('Roles & Users', 'lmb-core'),
        ];

        // --- NEW: Define the sub-tabs for the 'templates' main tab ---
        self::$settings_sub_tabs = [
            'templates' => [
                'legal_ads'        => __('Legal Ad Templates', 'lmb-core'),
                'accuse_newspaper' => __('Accuse & Newspaper', 'lmb-core'),
            ]
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

    // --- REPLACED aTH a NEW, COMPREHENSIVE FUNCTION ---
    public static function collect_stats() {
        global $wpdb;
        $stats = [];

        // Ad Counts by Status
        $ad_counts = (array) wp_count_posts('lmb_legal_ad');
        $stats['ads_draft'] = $ad_counts['draft'] ?? 0;
        $stats['ads_pending'] = $ad_counts['pending'] ?? 0; // WordPress uses 'pending' for pending review
        $stats['ads_published'] = $ad_counts['publish'] ?? 0;
        $stats['ads_total'] = array_sum($ad_counts);

        // Due Payments (pending invoices)
        $stats['due_payments_count'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'pending'");
        $stats['due_payments_value'] = (float) $wpdb->get_var("SELECT SUM(CAST(meta_value AS DECIMAL(10,2))) FROM {$wpdb->postmeta} WHERE meta_key = 'package_price' AND post_id IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'payment_status' AND meta_value = 'pending')");

        // Points & Earnings (based on transactions)
        $transactions_table = $wpdb->prefix . 'lmb_points_transactions';
        $stats['total_spent_points'] = abs((int) $wpdb->get_var("SELECT SUM(amount) FROM {$transactions_table} WHERE amount < 0"));

        // Time-based earnings (assuming 1 point = 1 MAD for simplicity, adjust if different)
        $stats['earnings_month'] = abs((float) $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$transactions_table} WHERE amount < 0 AND created_at >= %s", date('Y-m-01'))));
        $stats['earnings_quarter'] = abs((float) $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$transactions_table} WHERE amount < 0 AND created_at >= %s", date('Y-m-d', strtotime('-3 months')))));
        $stats['earnings_year'] = abs((float) $wpdb->get_var($wpdb->prepare("SELECT SUM(amount) FROM {$transactions_table} WHERE amount < 0 AND created_at >= %s", date('Y-01-01'))));

        // Total points in the system
        $stats['total_unspent_points'] = (int) $wpdb->get_var("SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->usermeta} WHERE meta_key = 'lmb_points_balance'");
        $stats['total_points_system'] = $stats['total_unspent_points'] + $stats['total_spent_points'];
        
        // Other Counts
        $stats['users_total'] = count_users()['total_users'];
        $stats['news_total'] = wp_count_posts('lmb_newspaper')->publish ?? 0;

        return $stats;
    }

    // --- REVISED FUNCTION ---
    public static function register_settings() {
        // General Settings
        register_setting('lmb_general_settings', 'lmb_bank_name');
        register_setting('lmb_general_settings', 'lmb_bank_iban');
        register_setting('lmb_general_settings', 'lmb_bank_account_holder');
        register_setting('lmb_general_settings', 'lmb_default_cost_per_ad');

        // Invoice & Receipt Templates
        register_setting('lmb_invoices_receipts_settings', 'lmb_invoice_template_html');
        register_setting('lmb_invoices_receipts_settings', 'lmb_receipt_template_html');

        // --- FIX: Register ONE option to hold an array of all ad templates ---
        register_setting('lmb_legal_ads_settings', 'lmb_legal_ad_templates');

        // --- MODIFICATION: Register new settings for the accuse template ---
        register_setting('lmb_accuse_newspaper_settings', 'lmb_accuse_template_html');
        register_setting('lmb_accuse_newspaper_settings', 'lmb_logo_url');
        register_setting('lmb_accuse_newspaper_settings', 'lmb_signature_url');
    
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

    // --- REVISED FUNCTION to include sub-tabs ---
    public static function render_settings_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h2><?php esc_html_e('LMB Core Settings', 'lmb-core'); ?></h2>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach (self::$settings_tabs as $tab_key => $tab_name) {
                    $active_class = ($current_tab === $tab_key) ? 'nav-tab-active' : '';
                    echo '<a href="?page=lmb-core-settings&tab=' . esc_attr($tab_key) . '" class="nav-tab ' . esc_attr($active_class) . '">' . esc_html($tab_name) . '</a>';
                }
                ?>
            </h2>

            <?php
            // --- NEW: Sub-tab navigation logic ---
            if (isset(self::$settings_sub_tabs[$current_tab])) {
                $current_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : key(self::$settings_sub_tabs[$current_tab]);
                echo '<ul class="subsubsub">';
                foreach (self::$settings_sub_tabs[$current_tab] as $sub_tab_key => $sub_tab_name) {
                    $active_class = ($current_sub_tab === $sub_tab_key) ? 'current' : '';
                    $separator = next(self::$settings_sub_tabs[$current_tab]) ? ' |' : '';
                    $url = '?page=lmb-core-settings&tab=' . esc_attr($current_tab) . '&sub_tab=' . esc_attr($sub_tab_key);
                    echo '<li><a href="' . esc_url($url) . '" class="' . esc_attr($active_class) . '">' . esc_html($sub_tab_name) . '</a>' . $separator . '</li>';
                }
                 echo '</ul><br class="clear">';
            }
            // --- END NEW ---

            // --- MODIFICATION: Updated switch to handle sub-tabs ---
            switch ($current_tab) {
                case 'general':
                    self::render_general_tab();
                    break;
                case 'templates':
                    $current_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : 'legal_ads';
                    if ($current_sub_tab === 'accuse_newspaper') {
                        self::render_accuse_newspaper_tab();
                    } else {
                        self::render_legal_ads_tab();
                    }
                    break;
                case 'notifications':
                    self::render_notifications_tab();
                    break;
                case 'security':
                    self::render_security_tab();
                    break;
                case 'roles':
                    self::render_roles_tab();
                    break;
                default:
                    self::render_general_tab();
                    break;
            }
            ?>
        </div>
        <?php
    }

    /*** Tabs ***/

    // --- REVISED FUNCTION to render the new sub-tabs ---
    private static function render_templates_tab() {
        $sub_tabs = [
            'invoices_receipts' => __('Invoices & Receipts', 'lmb-core'),
            'legal_ads'         => __('Legal Ads', 'lmb-core'),
        ];
        $current_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : 'invoices_receipts';
        ?>
        <h2 class="nav-tab-wrapper" style="margin-bottom: 20px;">
            <?php foreach ($sub_tabs as $tab_id => $tab_name): ?>
                <a href="?page=lmb-settings&tab=templates&sub_tab=<?php echo esc_attr($tab_id); ?>" class="nav-tab<?php echo $current_sub_tab === $tab_id ? ' nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab_name); ?>
                </a>
            <?php endforeach; ?>
        </h2>

        <form method="post" action="options.php">
            <?php
            // Call the correct render function for the sub-tab
            $sub_method = 'render_' . $current_sub_tab . '_sub_tab';
            if (method_exists(__CLASS__, $sub_method)) {
                call_user_func([__CLASS__, $sub_method]);
            }
            submit_button();
            ?>
        </form>
        <?php
    }

    // --- NEW FUNCTION for the Invoices & Receipts sub-tab ---
    private static function render_invoices_receipts_sub_tab() {
        settings_fields('lmb_invoices_receipts_settings');
        ?>
        <h3><?php esc_html_e('Invoice Template (for Pending Payments)', 'lmb-core'); ?></h3>
        <textarea name="lmb_invoice_template_html" rows="12" style="width:100%;"><?php echo esc_textarea(get_option('lmb_invoice_template_html')); ?></textarea>
        
        <hr style="margin: 20px 0;">

        <h3><?php esc_html_e('Receipt Template (for Approved Payments)', 'lmb-core'); ?></h3>
        <textarea name="lmb_receipt_template_html" rows="12" style="width:100%;"><?php echo esc_textarea(get_option('lmb_receipt_template_html')); ?></textarea>
        <?php
    }

    // --- REVISED AND FINAL FUNCTION ---
    private static function render_legal_ads_sub_tab() {
        settings_fields('lmb_legal_ads_settings');
        $all_ad_types = self::get_all_ad_types();
        $current_ad_type = isset($_GET['ad_type']) ? sanitize_text_field($_GET['ad_type']) : $all_ad_types[0];
        
        $all_templates = get_option('lmb_legal_ad_templates', []);
        
        ?>
        <p class="description">
            <?php esc_html_e('Select an ad type to edit its template. Use placeholders like {{field_id}} for form data.', 'lmb-core'); ?><br>
            - <?php esc_html_e('For repeaters, use:', 'lmb-core'); ?> <code>{{#each repeater_id}}...{{/each}}</code><br>
            - <?php esc_html_e('For calculations, use:', 'lmb-core'); ?> <code>{{sum:repeater_id:field_id}}</code><br>
            - <?php esc_html_e('For conditions, use:', 'lmb-core'); ?> <code>{{#ifcount repeater_id > 1}}...{{else}}...{{/ifcount}}</code>
        </p>
        
        <select id="lmb_ad_type_selector" onchange="window.location.href = this.value;">
            <?php foreach ($all_ad_types as $type): ?>
                <option value="?page=lmb-settings&tab=templates&sub_tab=legal_ads&ad_type=<?php echo urlencode($type); ?>" <?php selected($current_ad_type, $type); ?>>
                    <?php echo esc_html($type); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <hr style="margin-top: 20px;">
        
        <?php
        $current_ad_type_key = sanitize_key($current_ad_type);
        $field_name = 'lmb_legal_ad_templates[' . $current_ad_type_key . ']';
        $template_content = isset($all_templates[$current_ad_type_key]) ? $all_templates[$current_ad_type_key] : '';
        ?>
        <h3>Template for: <strong style="color: #667eea;"><?php echo esc_html($current_ad_type); ?></strong></h3>
        <textarea name="<?php echo esc_attr($field_name); ?>" rows="20" style="width:100%;"><?php echo esc_textarea($template_content); ?></textarea>
        
        <?php
        // --- THIS IS THE FIX ---
        // Add hidden fields for all other templates to ensure they are not erased on save.
        foreach ($all_templates as $key => $value) {
            if ($key === $current_ad_type_key) {
                continue; // Skip the one we are currently editing
            }
            echo '<input type="hidden" name="lmb_legal_ad_templates[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
        }
        ?>
        <?php
    }


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
        return '<h1 style="color: #4CAF50;">Payment Receipt</h1>
<h2>Reference: {{invoice_number}}</h2>
<hr>
<h3>Client Details</h3>
<p>Name: {{user_name}}</p>
<hr>
<h3>Transaction Details</h3>
<p><strong>Package Purchased:</strong> {{package_name}}</p>
<p><strong>Amount Paid:</strong> {{package_price}} MAD</p>
<p><strong>Points Awarded:</strong> {{points_awarded}}</p>
<p><strong>Approval Date:</strong> {{approval_date}}</p>
<hr>
<p style="text-align:center;">Thank you for your business. The points have been added to your account.</p>';
    }


    /**
     * Get unique ad types from post meta for filtering.
     */
    public static function get_unique_ad_types() {
        global $wpdb;
        $meta_key = 'ad_type';
        // The phpcs:ignore below is safe because we are controlling all parts of the query.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE p.post_type = 'lmb_legal_ad' AND pm.meta_key = %s
             ORDER BY pm.meta_value ASC",
            $meta_key
        ));
        return $results;
    }


    // --- NEW HELPER FUNCTION to get all ad types ---
    private static function get_all_ad_types() {
        // In a real-world scenario, you might get this from another source.
        // For now, we use the list you provided.
        return [
            'Constitution - SARL', 'Constitution - SARL AU', 'Liquidation - anticipee',
            'Liquidation - definitive', 'Modification - Capital', 'Modification - denomination',
            'Modification - gerant', 'Modification - objects', 'Modification - parts', 'Modification - seige'
        ];
    }

    // The other render_*_tab methods (like render_general_tab) should now be simple form content
    private static function render_general_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('lmb_general_settings'); ?>
            <h3><?php esc_html_e('Bank Details', 'lmb-core'); ?></h3>
            <p><?php esc_html_e('This information will be displayed to users when they need to make a payment.', 'lmb-core'); ?></p>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="lmb_bank_name"><?php esc_html_e('Bank Name', 'lmb-core'); ?></label></th>
                    <td><input type="text" id="lmb_bank_name" name="lmb_bank_name" value="<?php echo esc_attr(get_option('lmb_bank_name')); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="lmb_bank_iban"><?php esc_html_e('IBAN', 'lmb-core'); ?></label></th>
                    <td><input type="text" id="lmb_bank_iban" name="lmb_bank_iban" value="<?php echo esc_attr(get_option('lmb_bank_iban')); ?>" class="regular-text" /></td>
                </tr>
                 <tr valign="top">
                    <th scope="row"><label for="lmb_bank_account_holder"><?php esc_html_e('Account Holder Name', 'lmb-core'); ?></label></th>
                    <td><input type="text" id="lmb_bank_account_holder" name="lmb_bank_account_holder" value="<?php echo esc_attr(get_option('lmb_bank_account_holder')); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }


    // --- NEW FUNCTION for the Accuse & Newspaper tab ---
    private static function render_accuse_newspaper_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('lmb_accuse_newspaper_settings'); ?>
            <h3><?php esc_html_e('Accuse (Receipt) Template Settings', 'lmb-core'); ?></h3>
            <p class="description">
                <?php esc_html_e('Configure the template and assets for the automatically generated accuse/receipt PDF.', 'lmb-core'); ?>
            </p>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="lmb_logo_url"><?php esc_html_e('Logo Image URL', 'lmb-core'); ?></label></th>
                    <td>
                        <input type="text" id="lmb_logo_url" name="lmb_logo_url" value="<?php echo esc_attr(get_option('lmb_logo_url')); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Enter the full URL for the logo to display at the top of the accuse.', 'lmb-core'); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="lmb_signature_url"><?php esc_html_e('Signature Image URL', 'lmb-core'); ?></label></th>
                    <td>
                        <input type="text" id="lmb_signature_url" name="lmb_signature_url" value="<?php echo esc_attr(get_option('lmb_signature_url')); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Enter the full URL for the signature image.', 'lmb-core'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Accuse HTML Template', 'lmb-core'); ?></h3>
            <textarea name="lmb_accuse_template_html" rows="20" style="width:100%; font-family: monospace;"><?php echo esc_textarea(get_option('lmb_accuse_template_html', self::get_default_accuse_template())); ?></textarea>
            
            <h4><?php esc_html_e('Available Placeholders:', 'lmb-core'); ?></h4>
            <ul style="list-style: inside; margin-left: 20px;">
                <li><code>{{lmb_logo_url}}</code> - The Logo Image URL defined above.</li>
                <li><code>{{journal_no}}</code> - The number of the associated journal (temp or final).</li>
                <li><code>{{ad_object}}</code> - The "objet" or "ad_type" of the legal ad.</li>
                <li><code>{{legal_ad_link}}</code> - A public link to the legal ad.</li>
                <li><code>{{signature_url}}</code> - The Signature Image URL defined above.</li>
            </ul>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }
    // --- NEW DEFAULT TEMPLATE FUNCTION ---
    private static function get_default_accuse_template() {
        // Updated default template based on user's structure
        return '<div style="text-align: center;">
    <img src="{{lmb_logo_url}}" alt="Logo" width="150">
</div>
<p><strong>Journal N°:</strong> {{journal_no}}</p>
<h1 style="text-align: center;">ACCUSE DE PUBLICATION</h1>
<p><strong>Objet:</strong> avis de {{ad_object}}</p>
<p>Pour consulter votre annonce, veuillez cliquer sur le lien suivant :<br><a href="{{legal_ad_link}}">{{legal_ad_link}}</a></p>
<div style="margin-top: 40px;">
    <img src="{{signature_url}}" alt="Signature" width="200">
    <p>
        <strong>Directeur de publication : MOHAMED ELBACHIR LANSAR</strong><br>
        2022/23/01ص : License<br>
        RUE AHL LKHALIL OULD MHAMED N°08 ES-SEMARA<br>
        ICE :002924841000097-TP :77402556-IF :50611382-CNSS :4319969<br>
        RIB : 007260000899200000033587<br>
        lmbannonceslegales.com<br>
        ste.lmbgroup@gmail.com<br>
        06 61 83 82 11 / 06 74 40 61 97 / 06 05 28 98 04 / 08 08 61 04 87
    </p>
</div>';
    }

}
