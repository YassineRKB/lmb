<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_List_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_list'; }
    public function get_title() { return __('LMB User List', 'lmb-core'); }
    public function get_icon() { return 'eicon-table'; }
    public function get_categories() { return ['lmb-admin-widgets']; }

    public function get_script_depends() {
        return ['lmb-admin-lists'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied.', 'lmb-core') . '</p></div>';
            return;
        }
        ?>
        <div class="lmb-user-list-widget lmb-admin-widget" id="lmb-user-list-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-users"></i> <?php esc_html_e('User Management', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-container">
                    <form id="lmb-user-filters-form">
                        <div class="lmb-filter-row">
                            <input type="text" name="search_name" placeholder="<?php esc_attr_e('Search by name...', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="email" name="search_email" placeholder="<?php esc_attr_e('Search by email...', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="number" name="search_id" placeholder="<?php esc_attr_e('User ID', 'lmb-core'); ?>" class="lmb-filter-input">
                            <button type="submit" class="lmb-btn lmb-btn-primary"><i class="fas fa-search"></i> <?php esc_html_e('Filter', 'lmb-core'); ?></button>
                            <button type="reset" class="lmb-btn lmb-btn-secondary"><i class="fas fa-times"></i> <?php esc_html_e('Clear', 'lmb-core'); ?></button>
                        </div>
                    </form>
                </div>
                <div id="lmb-user-list-container">
                    <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_html_e('Loading users...', 'lmb-core'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
}