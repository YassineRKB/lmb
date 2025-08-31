<?php
// FILE: elementor/widgets/class-lmb-upload-bank-proof-widget.php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    // ... (get_name, get_title, etc. remain the same) ...
    public function get_name() { return 'lmb_upload_bank_proof'; }
    public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-user-widgets-v2']; }

    public function get_script_depends() {
        return ['lmb-upload-bank-proof'];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets-v2'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to upload payment proof.', 'lmb-core').'</p></div>';
            return;
        }
        ?>
        <div class="lmb-upload-bank-proof-widget lmb-user-widget-v2">
            <div class="lmb-widget-header"><h3><i class="fas fa-file-invoice-dollar"></i> <?php esc_html_e('Upload Payment Proof','lmb-core'); ?></h3></div>
            <div class="lmb-widget-content" id="lmb-upload-proof-container">
                <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> <?php esc_html_e('Loading pending invoices...', 'lmb-core'); ?></div>
            </div>

            <div class="lmb-bank-details" style="padding: 25px; border-top: 1px solid #e9ecef; background-color: #f8f9fa;">
                <h4 style="margin-top: 0;"><?php esc_html_e('Bank Details for Payment', 'lmb-core'); ?></h4>
                <p><strong><?php esc_html_e('Bank Name:', 'lmb-core'); ?></strong> <?php echo esc_html(get_option('lmb_bank_name', 'Not Specified')); ?></p>
                <p><strong><?php esc_html_e('IBAN:', 'lmb-core'); ?></strong> <?php echo esc_html(get_option('lmb_bank_iban', 'Not Specified')); ?></p>
                <p><strong><?php esc_html_e('Account Holder:', 'lmb-core'); ?></strong> <?php echo esc_html(get_option('lmb_bank_account_holder', 'Not Specified')); ?></p>
            </div>
        </div>
        <?php
    }
}