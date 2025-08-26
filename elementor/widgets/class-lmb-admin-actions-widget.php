<?php
// FILE: elementor/widgets/class-lmb-admin-actions-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Actions_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_actions'; }
    public function get_title(){ return __('LMB Admin Actions & Feeds','lmb-core'); }
    public function get_icon() { return 'eicon-tabs'; }
    public function get_categories(){ return ['lmb-admin-widgets']; }

    public function get_script_depends() { 
        return ['lmb-admin-actions']; 
    }
    public function get_style_depends() { 
        return ['lmb-admin-widgets', 'lmb-core']; 
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied.', 'lmb-core') . '</p></div>';
            return;
        }
        ?>
        <div class="lmb-admin-actions-widget lmb-admin-widget">
            <div class="lmb-tabs-nav">
                <button class="lmb-tab-btn active" data-tab="feed">
                    <i class="fas fa-stream"></i> <?php _e('Activity Feed', 'lmb-core'); ?>
                </button>
                <button class="lmb-tab-btn" data-tab="pending_ads">
                    <i class="fas fa-clock"></i> <?php _e('Pending Ads', 'lmb-core'); ?>
                    <span class="lmb-tab-badge" id="pending-ads-count">0</span>
                </button>
                <button class="lmb-tab-btn" data-tab="pending_payments">
                    <i class="fas fa-money-check-alt"></i> <?php _e('Pending Payments', 'lmb-core'); ?>
                    <span class="lmb-tab-badge" id="pending-payments-count">0</span>
                </button>
            </div>
            <div class="lmb-tab-content">
                <div id="lmb-tab-content-area">
                    <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
                <div class="lmb-pagination" id="lmb-tab-pagination-area"></div>
            </div>
        </div>
        <?php
    }
}