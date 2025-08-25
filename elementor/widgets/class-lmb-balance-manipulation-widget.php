<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Balance_Manipulation_Widget extends Widget_Base {
    public function get_name() { return 'lmb_balance_manipulation'; }
    public function get_title() { return __('LMB Balance Manipulation', 'lmb-core'); }
    public function get_icon() { return 'eicon-coins'; }
    public function get_categories() { return ['lmb-cw-admin']; }

    public function get_script_depends() {
        return ['lmb-balance-manipulation'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied. Administrator privileges required.', 'lmb-core') . '</p></div>';
            return;
        }
        ?>
        <div class="lmb-balance-manipulation-widget lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-coins"></i> <?php esc_html_e('Balance Manipulation', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <div class="lmb-search-section">
                    <h4><?php esc_html_e('Search User', 'lmb-core'); ?></h4>
                    <div class="lmb-search-form">
                        <input type="text" id="lmb-user-search" placeholder="<?php esc_attr_e('Enter user email or ID...', 'lmb-core'); ?>" class="lmb-input">
                        <button type="button" id="lmb-search-btn" class="lmb-btn lmb-btn-primary">
                            <i class="fas fa-search"></i> <?php esc_html_e('Search', 'lmb-core'); ?>
                        </button>
                    </div>
                    <div id="lmb-search-results" class="lmb-search-results"></div>
                </div>

                <div id="lmb-balance-section" class="lmb-balance-section" style="display: none;">
                    <h4><?php esc_html_e('Balance Management', 'lmb-core'); ?></h4>
                    
                    <div class="lmb-user-info">
                        <div id="lmb-user-details"></div>
                        <div class="lmb-current-balance">
                            <span class="lmb-balance-label"><?php esc_html_e('Current Balance:', 'lmb-core'); ?></span>
                            <span id="lmb-current-balance" class="lmb-balance-value">0</span>
                            <span class="lmb-balance-unit"><?php esc_html_e('points', 'lmb-core'); ?></span>
                        </div>
                    </div>

                    <div class="lmb-balance-form">
                        <div class="lmb-form-row">
                            <div class="lmb-form-group">
                                <label for="lmb-balance-action"><?php esc_html_e('Action', 'lmb-core'); ?></label>
                                <select id="lmb-balance-action" class="lmb-select">
                                    <option value="add"><?php esc_html_e('Add Points', 'lmb-core'); ?></option>
                                    <option value="subtract"><?php esc_html_e('Subtract Points', 'lmb-core'); ?></option>
                                    <option value="set"><?php esc_html_e('Set Balance', 'lmb-core'); ?></option>
                                </select>
                            </div>
                            
                            <div class="lmb-form-group">
                                <label for="lmb-balance-amount"><?php esc_html_e('Amount', 'lmb-core'); ?></label>
                                <input type="number" id="lmb-balance-amount" min="0" step="1" class="lmb-input" placeholder="0">
                            </div>
                        </div>

                        <div class="lmb-form-group">
                            <label for="lmb-balance-reason"><?php esc_html_e('Reason (optional)', 'lmb-core'); ?></label>
                            <textarea id="lmb-balance-reason" class="lmb-textarea" rows="3" placeholder="<?php esc_attr_e('Enter reason for balance change...', 'lmb-core'); ?>"></textarea>
                        </div>

                        <div class="lmb-form-actions">
                            <button type="button" id="lmb-update-balance-btn" class="lmb-btn lmb-btn-success">
                                <i class="fas fa-save"></i> <?php esc_html_e('Update Balance', 'lmb-core'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="lmb-history-section" class="lmb-history-section" style="display: none;">
                    <h4><?php esc_html_e('Recent Balance Changes', 'lmb-core'); ?></h4>
                    <div id="lmb-balance-history" class="lmb-balance-history"></div>
                </div>
            </div>
        </div>
        <?php
    }
}