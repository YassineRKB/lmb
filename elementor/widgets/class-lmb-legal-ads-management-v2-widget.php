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
                            <!-- Example Row 1: Published, missing Accuse, has Journal -->
                            <tr class="clickable-row" data-href="#">
                                <td>101</td>
                                <td>Tech Solutions SARL</td>
                                <td>Constitution - SARL</td>
                                <td>2025-08-26</td>
                                <td>John Doe</td>
                                <td><span class="lmb-status-badge lmb-status-published">Published</span></td>
                                <td>Admin User</td>
                                <td class="cell-placeholder">-</td>
                                <td><a href="#" class="lmb-btn lmb-btn-sm lmb-btn-text-link">Journal</a></td>
                                <td class="lmb-actions-cell">
                                    <button class="lmb-btn lmb-btn-sm lmb-btn-info" title="Upload Temporary Journal"><i class="fas fa-newspaper"></i></button>
                                    <button class="lmb-btn lmb-btn-sm lmb-btn-info" title="Generate Accuse"><i class="fas fa-receipt"></i></button>
                                </td>
                            </tr>
                            <!-- Example Row 2: Published, has Accuse, missing Journal -->
                            <tr class="clickable-row" data-href="#">
                                <td>102</td>
                                <td>Global Imports</td>
                                <td>Modification - Capital</td>
                                <td>2025-08-25</td>
                                <td>Jane Smith</td>
                                <td><span class="lmb-status-badge lmb-status-published">Published</span></td>
                                <td>Admin User</td>
                                <td><a href="#" class="lmb-btn lmb-btn-sm lmb-btn-text-link">Accuse</a></td>
                                <td class="cell-placeholder">-</td>
                                <td class="lmb-actions-cell">
                                    <button class="lmb-btn lmb-btn-sm lmb-btn-info" title="Upload Temporary Journal"><i class="fas fa-newspaper"></i></button>
                                    <button class="lmb-btn lmb-btn-sm lmb-btn-info" title="Generate Accuse"><i class="fas fa-receipt"></i></button>
                                </td>
                            </tr>
                            <!-- Example Row 3: Pending -->
                            <tr class="clickable-row" data-href="#">
                                <td>103</td>
                                <td>Creative Minds AU</td>
                                <td>SARL AU</td>
                                <td>2025-08-24</td>
                                <td>Peter Jones</td>
                                <td><span class="lmb-status-badge lmb-status-pending_review">Pending</span></td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="lmb-actions-cell">
                                    <button class="lmb-btn lmb-btn-icon lmb-btn-success" title="Approve"><i class="fas fa-check-circle"></i></button>
                                    <button class="lmb-btn lmb-btn-icon lmb-btn-danger" title="Deny"><i class="fas fa-times-circle"></i></button>
                                </td>
                            </tr>
                             <!-- Example Row 4: Denied -->
                            <tr class="clickable-row" data-href="#">
                                <td>104</td>
                                <td>My Biz Consulting</td>
                                <td>Cession de parts</td>
                                <td>2025-08-23</td>
                                <td>Sam Wilson</td>
                                <td><span class="lmb-status-badge lmb-status-denied">Denied</span></td>
                                <td>Admin User</td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="lmb-actions-cell">
                                    <button class="lmb-btn lmb-btn-sm lmb-btn-view">View</button>
                                </td>
                            </tr>
                             <!-- Example Row 5: Draft -->
                            <tr class="clickable-row" data-href="#">
                                <td>105</td>
                                <td>Future Ventures</td>
                                <td>Réduction de capital</td>
                                <td>2025-08-22</td>
                                <td>Emily Carter</td>
                                <td><span class="lmb-status-badge lmb-status-draft">Draft</span></td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="cell-placeholder">N/A</td>
                                <td class="lmb-actions-cell">
                                    <button class="lmb-btn lmb-btn-sm lmb-btn-view">View</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
