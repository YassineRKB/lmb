<?php
// FILE: elementor/widgets/class-lmb-legal-ads-management-v2-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_Management_V2_Widget extends Widget_Base {
    public function get_name() {
        return 'lmb_legal_ads_management_v2';
    }

    public function get_title() {
        return __('LMB Legal Ads Management V2', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return ['lmb-admin-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-legal-ads-management-v2'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets-v2'];
    }

    protected function render() {
        ?>
        <div class="lmb-legal-ads-management-v2 lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-gavel"></i> <?php esc_html_e('Legal Ads Management V2', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <!-- Filters Section -->
                <div class="lmb-filters-box">
                    <h4 class="lmb-filters-box-title"><?php esc_html_e('Filter Ads', 'lmb-core'); ?></h4>
                    <form id="lmb-ads-filters-form-v2">
                        <div class="lmb-filter-grid">
                            <input type="text" name="filter_ref" placeholder="<?php esc_attr_e('Ref (ID)', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="text" name="filter_company" placeholder="<?php esc_attr_e('Company', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="text" name="filter_type" placeholder="<?php esc_attr_e('Type', 'lmb-core'); ?>" class="lmb-filter-input">
                            <input type="date" name="filter_date" class="lmb-filter-input">
                            <input type="text" name="filter_client" placeholder="<?php esc_attr_e('Client', 'lmb-core'); ?>" class="lmb-filter-input">
                             <select name="filter_status" class="lmb-filter-select">
                                <option value=""><?php esc_html_e('All Statuses', 'lmb-core'); ?></option>
                                <option value="published"><?php esc_html_e('Published', 'lmb-core'); ?></option>
                                <option value="pending_review"><?php esc_html_e('Pending Review', 'lmb-core'); ?></option>
                                <option value="draft"><?php esc_html_e('Draft', 'lmb-core'); ?></option>
                                <option value="denied"><?php esc_html_e('Denied', 'lmb-core'); ?></option>
                            </select>
                            <input type="text" name="filter_approved_by" placeholder="<?php esc_attr_e('Approved By', 'lmb-core'); ?>" class="lmb-filter-input">
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> <?php esc_html_e('Reset', 'lmb-core'); ?></button>
                        </div>
                    </form>
                </div>

                <!-- Table Section -->
                <div class="lmb-table-container">
                    <table class="lmb-data-table lmb-ads-table-v2">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ref', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Company', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Type', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Date', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Client', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Status', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Approved By', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Accuse', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Journal', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- This will be populated by AJAX -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Container for AJAX-loaded pagination -->
                <div class="lmb-pagination-container" style="margin-top: 20px;"></div>

            </div>
        </div>
        <?php
    }
}

