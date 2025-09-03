<?php
// FILE: elementor/widgets/class-lmb-legal-ads-management-v2-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_Management_V2_Widget extends Widget_Base {
    public function get_name() { return 'lmb_legal_ads_management_v2'; }
    public function get_title() { return __('LMB Legal Ads Management V2', 'lmb-core'); }
    public function get_icon() { return 'eicon-table'; }
    public function get_categories() { return ['lmb-admin-widgets-v2']; }
    
    // Point to the dedicated JS and CSS files for this widget
    public function get_script_depends() { return ['lmb-legal-ads-management-v2']; }
    public function get_style_depends() { return ['lmb-legal-ads-management-v2']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lamv2-widget"><p style="padding: 20px;">'.esc_html__('You do not have permission to view this content.', 'lmb-core').'</p></div>';
            return;
        }
        ?>
        <div class="lamv2-widget">
            <div class="lamv2-widget-header">
                <h3><i class="fas fa-gavel"></i> <?php esc_html_e('Legal Ads Management V2', 'lmb-core'); ?></h3>
            </div>
            <div class="lamv2-widget-content">
                <!-- Filter Section -->
                <div class="lamv2-filters-box">
                    <form id="lamv2-ads-filters-form">
                        <div class="lamv2-filter-grid">
                            <input type="text" name="filter_ref" placeholder="<?php esc_attr_e('Ref (ID)', 'lmb-core'); ?>" class="lamv2-filter-input">
                            <input type="text" name="filter_company" placeholder="<?php esc_attr_e('Company', 'lmb-core'); ?>" class="lamv2-filter-input">
                            <input type="text" name="filter_type" placeholder="<?php esc_attr_e('Type', 'lmb-core'); ?>" class="lamv2-filter-input">
                            <input type="date" name="filter_date" class="lamv2-filter-input">
                            <input type="text" name="filter_client" placeholder="<?php esc_attr_e('Client', 'lmb-core'); ?>" class="lamv2-filter-input">
                            <select name="filter_status" class="lamv2-filter-select">
                                <option value=""><?php esc_html_e('All Statuses', 'lmb-core'); ?></option>
                                <option value="published"><?php esc_html_e('Published', 'lmb-core'); ?></option>
                                <option value="pending_review"><?php esc_html_e('Pending Review', 'lmb-core'); ?></option>
                                <option value="draft"><?php esc_html_e('Draft', 'lmb-core'); ?></option>
                                <option value="denied"><?php esc_html_e('Denied', 'lmb-core'); ?></option>
                            </select>
                            <input type="text" name="filter_approved_by" placeholder="<?php esc_attr_e('Approved By', 'lmb-core'); ?>" class="lamv2-filter-input">
                            <button type="reset" class="lamv2-btn lamv2-btn-view"><i class="fas fa-undo"></i> <?php esc_html_e('Reset', 'lmb-core'); ?></button>
                        </div>
                    </form>
                </div>

                <!-- Table Section -->
                <div class="lamv2-table-container">
                    <table class="lamv2-data-table">
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
                            <!-- Rows will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Section -->
                <div class="lamv2-pagination-container"></div>
            </div>

            <!-- Modal for Uploading Journal -->
            <div id="lamv2-upload-journal-modal" class="lamv2-modal-overlay hidden">
                <div class="lamv2-modal-content">
                    <div class="lamv2-modal-header">
                         <h4><?php esc_html_e('Upload Temporary Journal', 'lmb-core'); ?></h4>
                         <button class="lamv2-modal-close">&times;</button>
                    </div>
                     <form id="lamv2-upload-journal-form" class="lamv2-upload-journal-form">
                        <input type="hidden" name="ad_id" id="lamv2-journal-ad-id">
                         <div class="lamv2-form-grid">
                            <div class="lamv2-form-group">
                                <label for="lamv2-journal-no"><?php esc_html_e('Journal N°', 'lmb-core'); ?></label>
                                <input type="text" id="lamv2-journal-no" name="journal_no" class="lamv2-filter-input" placeholder="<?php esc_attr_e('Enter Journal Number', 'lmb-core'); ?>" required>
                            </div>
                            <div class="lamv2-form-group">
                                <label for="lamv2-journal-file"><?php esc_html_e('Journal PDF File', 'lmb-core'); ?></label>
                                <input type="file" id="lamv2-journal-file" name="journal_file" class="lamv2-filter-input" required accept="application/pdf">
                            </div>
                         </div>
                         <div class="lamv2-form-actions">
                             <button type="button" class="lamv2-btn lamv2-btn-view lamv2-modal-close"><?php esc_html_e('Cancel', 'lmb-core'); ?></button>
                             <button type="submit" class="lamv2-btn lamv2-btn-primary"><?php esc_html_e('Upload', 'lmb-core'); ?></button>
                         </div>
                     </form>
                </div>
            </div>
        </div>
        <?php
    }
}