<?php
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

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1, 'orderby' => 'title', 'order' => 'ASC']);
        ?>
        <div class="lmb-upload-bank-proof-widget lmb-user-widget">
            <div class="lmb-widget-header"><h3><i class="fas fa-file-invoice-dollar"></i> <?php esc_html_e('Upload Payment Proof','lmb-core'); ?></h3></div>
            <div class="lmb-widget-content">
                <form id="lmb-upload-proof-form" class="lmb-form" enctype="multipart/form-data">
                    <div class="lmb-form-group">
                        <label for="package_id"><i class="fas fa-box-open"></i> <?php esc_html_e('For Package','lmb-core'); ?></label>
                        <select name="package_id" id="package_id" class="lmb-select" required>
                            <option value=""><?php esc_html_e('Select the package you paid for...','lmb-core'); ?></option>
                            <?php foreach ($packages as $p) :
                                $price = get_post_meta($p->ID, 'price', true); ?>
                                <option value="<?php echo esc_attr($p->ID); ?>"><?php echo esc_html($p->post_title); ?> - <?php echo esc_html($price); ?> MAD</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lmb-form-group">
                        <label for="payment_reference"><i class="fas fa-receipt"></i> <?php esc_html_e('Payment Reference','lmb-core'); ?></label>
                        <input type="text" class="lmb-input" name="payment_reference" id="payment_reference" placeholder="<?php esc_attr_e('Enter the reference from your invoice','lmb-core'); ?>" required>
                    </div>
                    <div class="lmb-form-group">
                        <label for="proof_file"><i class="fas fa-paperclip"></i> <?php esc_html_e('Proof of Payment File','lmb-core'); ?></label>
                        <input type="file" name="proof_file" id="proof_file" class="lmb-input" accept="image/jpeg,image/png,application/pdf" required>
                        <small><?php esc_html_e('Accepted formats: JPG, PNG, PDF. Maximum size: 5MB.','lmb-core'); ?></small>
                    </div>
                    <div class="lmb-form-actions">
                        <button type="submit" class="lmb-btn lmb-btn-primary lmb-btn-large"><i class="fas fa-check-circle"></i> <?php esc_html_e('Submit for Verification','lmb-core'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}