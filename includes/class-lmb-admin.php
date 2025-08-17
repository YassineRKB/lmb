<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    private static $settings_tabs = [];

    public static function init() {
        self::$settings_tabs = [
            'general' => __('General', 'lmb-core'),
            'templates' => __('Templates', 'lmb-core'),
            'notifications' => __('Notifications', 'lmb-core'),
            'roles' => __('Roles & Users', 'lmb-core'),
        ];
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_admin_menu() {
        add_menu_page('LMB Core', 'LMB Core', 'manage_options', 'lmb-core', [__CLASS__, 'render_dashboard_page'], 'dashicons-analytics', 25);
        add_submenu_page('lmb-core', __('Dashboard', 'lmb-core'), __('Dashboard', 'lmb-core'), 'manage_options', 'lmb-core', [__CLASS__, 'render_dashboard_page']);
        add_submenu_page('lmb-core', __('Settings', 'lmb-core'), __('Settings', 'lmb-core'), 'manage_options', 'lmb-settings', [__CLASS__, 'render_settings_page']);
    }
    
    public static function register_settings() {
        register_setting('lmb_general_settings', 'lmb_bank_name');
        register_setting('lmb_general_settings', 'lmb_bank_iban');
        register_setting('lmb_general_settings', 'lmb_default_cost_per_ad');
        
        register_setting('lmb_templates_settings', 'lmb_invoice_template_html');
        
        register_setting('lmb_notifications_settings', 'lmb_enable_email_notifications');
    }

    public static function render_settings_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap lmb-settings-wrap">
            <h1><?php esc_html_e('LMB Core Settings', 'lmb-core'); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php
                foreach (self::$settings_tabs as $tab_id => $tab_name) {
                    $tab_url = add_query_arg(['page' => 'lmb-settings', 'tab' => $tab_id]);
                    $active = $current_tab == $tab_id ? ' nav-tab-active' : '';
                    echo '<a href="' . esc_url($tab_url) . '" class="nav-tab' . $active . '">' . esc_html($tab_name) . '</a>';
                }
                ?>
            </nav>
            <div class="tab-content">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('lmb_' . $current_tab . '_settings');
                    self::{'render_' . $current_tab . '_tab'}();
                    submit_button();
                    ?>
                </form>
            </div>
        </div>
        <?php
    }

    public static function render_general_tab() {
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Bank Name','lmb-core'); ?></th>
                <td><input type="text" name="lmb_bank_name" value="<?php echo esc_attr(get_option('lmb_bank_name')); ?>" class="regular-text">
                <p class="description"><?php _e('The bank name to display on invoices for bank transfers.', 'lmb-core'); ?></p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Bank IBAN/RIB','lmb-core'); ?></th>
                <td><input type="text" name="lmb_bank_iban" value="<?php echo esc_attr(get_option('lmb_bank_iban')); ?>" class="regular-text">
                 <p class="description"><?php _e('Your bank account number for receiving payments.', 'lmb-core'); ?></p></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Default Cost per Ad (Points)','lmb-core'); ?></th>
                <td><input type="number" name="lmb_default_cost_per_ad" value="<?php echo (int) get_option('lmb_default_cost_per_ad', 1); ?>" min="0">
                 <p class="description"><?php _e('The fallback cost for an ad if a user does not have a package-specific price.', 'lmb-core'); ?></p></td>
            </tr>
        </table>
        <?php
    }
    
    public static function render_templates_tab() {
        ?>
        <h3><?php _e('Invoice Template', 'lmb-core'); ?></h3>
        <p><?php _e('Customize the HTML template for all generated PDF invoices.', 'lmb-core'); ?></p>
        <textarea name="lmb_invoice_template_html" rows="20" class="large-text"><?php echo esc_textarea(get_option('lmb_invoice_template_html')); ?></textarea>
        <p class="description">
            <?php esc_html_e('Available variables:', 'lmb-core'); ?>
            <code>{{invoice_number}}, {{invoice_date}}, {{user_id}}, {{user_name}}, {{user_email}}, {{package_name}}, {{package_price}}, {{payment_reference}}, {{our_bank_name}}, {{our_iban}}, {{ad_id}}, {{ad_cost_points}}, {{points_after}}</code>
        </p>
        <?php
    }

    public static function render_notifications_tab() {
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Email Notifications','lmb-core'); ?></th>
                <td>
                    <label><input type="checkbox" name="lmb_enable_email_notifications" value="1" <?php checked(get_option('lmb_enable_email_notifications'), 1); ?>>
                    <?php _e('Enable all email notifications to users and admins', 'lmb-core'); ?></label>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public static function render_roles_tab() {
        // This is a placeholder for future functionality.
        echo '<h3>'.__('Manage Roles & Users', 'lmb-core').'</h3>';
        echo '<p>'.__('This section will allow you to assign packages and manage roles directly. This feature is under development.', 'lmb-core').'</p>';
    }
    
    // Unchanged dashboard functions
    public static function render_dashboard_page() { /* ... unchanged ... */ }
    public static function collect_stats() { /* ... unchanged ... */ }
}