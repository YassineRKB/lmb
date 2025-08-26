<?php
// FILE: elementor/widgets/class-lmb-upload-accuse-widget.php

if (!defined('ABSPATH')) {
    exit;
}

class LMB_Upload_Accuse_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'lmb-upload-accuse';
    }

    public function get_title() {
        return __('LMB Upload Accuse', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['lmb-admin-widgets'];
    }

    public function get_script_depends() {
        return ['lmb-upload-accuse'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied.', 'lmb-core') . '</p></div>';
            return;
        }

        // The widget now renders a container that will be populated by AJAX.
        ?>
        <div class="lmb-upload-accuse-widget lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-file-upload"></i> <?php esc_html_e('Upload Accuse Documents', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <p><?php esc_html_e('This is a list of all published ads that are waiting for an official accuse document.', 'lmb-core'); ?></p>
                <div id="lmb-pending-accuse-list-container">
                    <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_html_e('Loading ads...', 'lmb-core'); ?></div>
                </div>
            </div>
        </div>
        <?php
    }
}