<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_List_Widget extends Widget_Base {
    public function get_name() { return 'lmb_legal_ads_list'; }
    public function get_title() { return __('LMB Legal Ads Management', 'lmb-core'); }
    public function get_icon() { return 'eicon-post-list'; }
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

        // --- THIS CALL WILL NOW WORK ---
        $ad_types = class_exists('LMB_Admin') ? LMB_Admin::get_unique_ad_types() : [];
        
        ?>
        <div class="lmb-legal-ads-list-widget lmb-admin-widget" id="lmb-legal-ads-list-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-gavel"></i> <?php esc_html_e('Legal Ads Management', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-container">
                    <form id="lmb-ads-filters-form">
                        <div class="lmb-filter-grid">
                            <input type="text" name="filter_ref" placeholder="<?php esc_attr_e('Ref/ID or Title', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="text" name="filter_user" placeholder="<?php esc_attr_e('User (ID/login/email)', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="text" name="filter_company" placeholder="<?php esc_attr_e('Company', 'lmb-core'); ?>" class="lmb-filter-input">
                            <select name="filter_ad_type" class="lmb-filter-select">
                                <option value=""><?php esc_html_e('All Ad Types', 'lmb-core'); ?></option>
                                <?php foreach ($ad_types as $type): if(empty($type)) continue; ?>
                                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($type); ?></option>
                                <?php endforeach; ?>
                            </select>
                             <select name="filter_status" class="lmb-filter-select">
                                <option value=""><?php esc_html_e('All Statuses', 'lmb-core'); ?></option>
                                <option value="published"><?php esc_html_e('Published', 'lmb-core'); ?></option>
                                <option value="pending_review"><?php esc_html_e('Pending Review', 'lmb-core'); ?></option>
                                <option value="draft"><?php esc_html_e('Draft', 'lmb-core'); ?></option>
                                <option value="denied"><?php esc_html_e('Denied', 'lmb-core'); ?></option>
                            </select>
                            <button type="submit" class="lmb-btn lmb-btn-primary"><i class="fas fa-search"></i> <?php esc_html_e('Filter', 'lmb-core'); ?></button>
                            <button type="reset" class="lmb-btn lmb-btn-secondary"><i class="fas fa-times"></i> <?php esc_html_e('Clear', 'lmb-core'); ?></button>
                        </div>
                    </form>
                </div>
                <div id="lmb-ads-list-container">
                    <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_html_e('Loading ads...', 'lmb-core'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
}