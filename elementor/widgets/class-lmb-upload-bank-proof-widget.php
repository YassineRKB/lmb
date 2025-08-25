<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_bank_proof'; }
    public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-user-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to upload payment proof.', 'lmb-core').'</p></div>';
            return;
        }

        if (isset($_POST['lmb_upload_proof']) && wp_verify_nonce($_POST['_wpnonce'], 'lmb_upload_bank_proof')) {
            $result = self::handle_upload();
            if ($result['success']) {
                echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Proof Uploaded Successfully','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            } else {
                echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Upload Failed','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            }
        }

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1, 'orderby' => 'title', 'order' => 'ASC']);
        ?>
        <div class="lmb-upload-form-container">
            <div class="lmb-upload-header">
                <h2><i class="fas fa-file-invoice-dollar"></i> <?php esc_html_e('Upload Payment Proof','lmb-core'); ?></h2>
                <p><?php esc_html_e('After making a bank transfer, upload your proof of payment for verification.','lmb-core'); ?></p>
            </div>
            
            <form method="post" enctype="multipart/form-data" class="lmb-form">
                <?php wp_nonce_field('lmb_upload_bank_proof'); ?>
                
                <div class="lmb-form-group">
                    <label for="package_id"><i class="fas fa-box-open"></i> <?php esc_html_e('For Package','lmb-core'); ?></label>
                    <select name="package_id" id="package_id" required>
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
                    <small><?php esc_html_e('This is the unique reference number provided on the invoice you downloaded.','lmb-core'); ?></small>
                </div>

                <div class="lmb-form-group">
                    <label for="proof_file"><i class="fas fa-paperclip"></i> <?php esc_html_e('Proof of Payment File','lmb-core'); ?></label>
                    <div class="lmb-file-upload">
                        <input type="file" name="proof_file" id="proof_file" class="lmb-file-input" accept="image/jpeg,image/png,application/pdf" required>
                        <label for="proof_file" class="lmb-file-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span><?php esc_html_e('Choose proof file...','lmb-core'); ?></span>
                        </label>
                    </div>
                    <small><?php esc_html_e('Accepted formats: JPG, PNG, PDF. Maximum size: 5MB.','lmb-core'); ?></small>
                </div>
                
                <div class="lmb-form-actions">
                    <button type="submit" name="lmb_upload_proof" class="lmb-btn lmb-btn-primary lmb-btn-large">
                        <i class="fas fa-check-circle"></i> <?php esc_html_e('Submit Proof for Verification','lmb-core'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    private static function handle_upload() {
        if (!is_user_logged_in() || !isset($_POST['package_id']) || empty($_FILES['proof_file']['name'])) {
            return ['success' => false, 'message' => __('Missing required information.', 'lmb-core')];
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        $package_id = (int) $_POST['package_id'];
        $package_title = get_the_title($package_id);
        $payment_reference = sanitize_text_field($_POST['payment_reference']);

        $file = $_FILES['proof_file'];
        $file_return = wp_check_filetype($file['name']);
        if(!in_array($file_return['ext'], ['jpg', 'jpeg', 'png', 'pdf'])) {
            return ['success' => false, 'message' => __('Invalid file type. Please upload a JPG, PNG, or PDF.', 'lmb-core')];
        }
        if($file['size'] > 5 * 1024 * 1024) { // 5MB
            return ['success' => false, 'message' => __('File is too large. Maximum size is 5MB.', 'lmb-core')];
        }

        $attachment_id = media_handle_upload('proof_file', 0);
        if (is_wp_error($attachment_id)) {
            return ['success' => false, 'message' => $attachment_id->get_error_message()];
        }

        $payment_post_title = sprintf('Proof from %s for %s', $user->display_name, $package_title);
        $payment_id = wp_insert_post([
            'post_type' => 'lmb_payment',
            'post_title' => $payment_post_title,
            'post_status' => 'publish',
            'post_author' => $user_id,
        ]);

        if (is_wp_error($payment_id)) {
            wp_delete_attachment($attachment_id, true);
            return ['success' => false, 'message' => __('Could not create payment record.', 'lmb-core')];
        }

        update_post_meta($payment_id, 'user_id', $user_id);
        update_post_meta($payment_id, 'package_id', $package_id);
        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        update_post_meta($payment_id, 'payment_reference', $payment_reference);
        update_post_meta($payment_id, 'notes', sanitize_textarea_field($_POST['notes'] ?? ''));
        update_post_meta($payment_id, 'payment_status', 'pending');
        
        wp_update_post(['ID' => $attachment_id, 'post_parent' => $payment_id]);

        LMB_Ad_Manager::log_activity(sprintf('Payment proof #%d submitted by %s for package "%s"', $payment_id, $user->display_name, $package_title));
        LMB_Notification_Manager::notify_admin('New Payment Proof Submitted', sprintf('User %s has submitted payment proof for the "%s" package. Please review it in the Payments menu.', $user->display_name, $package_title));

        return ['success' => true, 'message' => __('Your proof has been submitted and is pending review. We will notify you once it is processed.', 'lmb-core')];
    }
}