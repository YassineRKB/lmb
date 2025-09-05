<?php
// FILE: elementor/widgets/class-lmb-notifications-widget.php

use Elementor\Widget_Base;
if (!defined('ABSPATH')) exit;

class LMB_Notifications_Widget extends Widget_Base {
    public function get_name() { return 'lmb_notifications'; }
    public function get_title(){ return __('LMB Notifications','lmb-core'); }
    public function get_title(){ return __('Notifications LMB','lmb-core'); }
    public function get_icon() { return 'eicon-bell'; }
    public function get_categories(){ return ['lmb-user-widgets', 'lmb-admin-widgets']; }

    public function get_script_depends() {
        return ['lmb-notifications'];
    }
    
    // --- FIX: Add the correct style dependency ---
    public function get_style_depends() {
        return ['lmb-notifications'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            return;
        }
        $widget_id = esc_attr($this->get_id());
        ?>
        <div class="lmb-notifications" id="lmb-notifications-<?php echo $widget_id; ?>">
            <button type="button" class="lmb-bell" aria-haspopup="true" aria-expanded="false" aria-controls="lmb-dropdown-<?php echo $widget_id; ?>">
                <i class="fas fa-bell" aria-hidden="true"></i>
                <span class="lmb-badge" data-count="0">0</span>
                <span class="screen-reader-text"><?php esc_html_e('Toggle notifications', 'lmb-core'); ?></span>
            </button>
            
            <div class="lmb-dropdown" id="lmb-dropdown-<?php echo $widget_id; ?>" role="menu" aria-label="<?php esc_attr_e('Notifications', 'lmb-core'); ?>">
                <div class="lmb-dropdown-header">
                    <strong>Notifications</strong>
                    <button type="button" class="lmb-mark-all" disabled>Marquer tout comme lu</button>
                </div>
                <div class="lmb-list" aria-live="polite">
                    <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
                <div class="lmb-empty" style="display:none;"><em>Aucune notification pour le moment.</em></div>
            </div>
        </div>
        <?php
    }
}