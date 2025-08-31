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
        register_setting('lmb_general_settings', 'lmb_default_cost_per_ad');

        // Invoice & Receipt Templates
        register_setting('lmb_invoices_receipts_settings', 'lmb_invoice_template_html');
        register_setting('lmb_invoices_receipts_settings', 'lmb_receipt_template_html');

        // --- FIX: Register ONE option to hold an array of all ad templates ---
        register_setting('lmb_legal_ads_settings', 'lmb_legal_ad_templates');
        // accuse and newspaper templates
        register_setting('lmb_accuse_newspaper_settings', 'lmb_accuse_template_html');
    
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
        if (!current_user_can(apply_filters('lmb_admin_capability', 'manage_options'))) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }

        $current_main_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('LMB Core Settings', 'lmb-core'); ?></h1>

            <nav class="nav-tab-wrapper">
                <?php foreach (self::$settings_tabs as $tab_id => $tab_name): ?>
                    <a href="?page=lmb-settings&tab=<?php echo esc_attr($tab_id); ?>" class="nav-tab<?php echo $current_main_tab === $tab_id ? ' nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content" style="background:#fff;padding:16px;border:1px solid #e5e7eb;border-top:0;">
                <?php
                // Call the correct render function based on the main tab
                $method = 'render_' . $current_main_tab . '_tab';
                if (method_exists(__CLASS__, $method)) {
                    call_user_func([__CLASS__, $method]);
                }
                ?>
            </div>
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
            <table class="form-table">
                </table>
            <?php submit_button(); ?>
        </form>
        <label>
            <input type="checkbox" name="lmb_enable_email_notifications" value="1" <?php checked(get_option('lmb_enable_email_notifications', 0), 1); ?>>
            <?php esc_html_e('Enable email notifications', 'lmb-core'); ?>
        </label>
        <?php
    }


// --- NEW FUNCTION for the Accuse & Newspaper tab ---
    private static function render_accuse_newspaper_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields('lmb_accuse_newspaper_settings'); ?>
            <h3><?php esc_html_e('Accuse (Receipt) Template', 'lmb-core'); ?></h3>
            <p class="description">
                <?php esc_html_e('This template is used to automatically generate the accuse/receipt PDF when an ad is approved. Use the placeholders below to insert dynamic data.', 'lmb-core'); ?>
            </p>
            <textarea name="lmb_accuse_template_html" rows="15" style="width:100%; font-family: monospace;"><?php echo esc_textarea(get_option('lmb_accuse_template_html', self::get_default_accuse_template())); ?></textarea>
            
            <h4><?php esc_html_e('Available Placeholders:', 'lmb-core'); ?></h4>
            <ul style="list-style: inside; margin-left: 20px;">
                <li><code>{{ad_id}}</code> - The ID of the legal ad.</li>
                <li><code>{{ad_title}}</code> - The title of the legal ad.</li>
                <li><code>{{publication_date}}</code> - The date the ad was approved/published.</li>
                <li><code>{{client_name}}</code> - The client's display name or company name.</li>
                <li><code>{{client_email}}</code> - The client's email address.</li>
                <li><code>{{ad_cost}}</code> - The cost of the ad in points.</li>
            </ul>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    // --- NEW HELPER FUNCTION for default template ---
    private static function get_default_accuse_template() {
        return '<h1>Accuse de Réception</h1>
<p><strong>Annonce Légale Réf:</strong> {{ad_id}}</p>
<p><strong>Titre:</strong> {{ad_title}}</p>
<hr>
<p>Ceci est pour confirmer que votre annonce légale a été reçue et publiée avec succès.</p>
<p><strong>Date de Publication:</strong> {{publication_date}}</p>
<p><strong>Client:</strong> {{client_name}} ({{client_email}})</p>
<p><strong>Coût:</strong> {{ad_cost}} points</p>
<br>
<p>Merci pour votre confiance.</p>';
    }

}
