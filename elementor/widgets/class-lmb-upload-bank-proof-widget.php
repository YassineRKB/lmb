<?php
// FILE: elementor/widgets/class-lmb-upload-bank-proof-widget.php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_bank_proof'; }
    public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-user-widgets']; }

    public function get_script_depends() {
        return ['lmb-upload-bank-proof'];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to upload payment proof.', 'lmb-core').'</p></div>';
            return;
        }
        ?>
        <div class="lmb-upload-bank-proof-widget lmb-user-widget">
            <div class="lmb-widget-header"><h3><i class="fas fa-file-invoice-dollar"></i> <?php esc_html_e('Upload Payment Proof','lmb-core'); ?></h3></div>
            <div class="lmb-widget-content" id="lmb-upload-proof-container">
                <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_html_e('Loading pending invoices...', 'lmb-core'); ?></div>
            </div>
        </div>
        <?php
    }
}