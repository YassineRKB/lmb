<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_bank_proof'; }
    public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to upload payment proof.', 'lmb-core').'</p></div>';
            return;
        }

        // Handle form submission
        if (isset($_POST['lmb_upload_proof']) && wp_verify_nonce($_POST['_wpnonce'], 'lmb_upload_bank_proof')) {
            $result = self::handle_upload();
            if ($result['success']) {
                echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Proof Uploaded Successfully','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            } else {
                echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Upload Failed','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            }
        }

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1, 'orderby' => 'title', 'order' => 'ASC']);
        
        echo '<div class="lmb-upload-form-container">';
        echo '<h2>'.esc_html__('Upload Payment Proof','lmb-core').'</h2>';
        echo '<p>'.esc_html__('After making a bank transfer, please upload a proof of payment (e.g., a screenshot or PDF receipt) for the package you purchased.','lmb-core').'</p>';
        
        echo '<form method="post" enctype="multipart/form-data" class="lmb-form">';
            wp_nonce_field('lmb_upload_bank_proof');
            
            echo '<div class="lmb-form-group">';
                echo '<label for="package_id">'.esc_html__('For Package','lmb-core').'</label>';
                echo '<select name="package_id" id="package_id" required>';
                    echo '<option value="">'.esc_html__('Select the package you paid for...','lmb-core').'</option>';
                    foreach ($packages as $p) {
                        $price = get_post_meta($p->ID, 'price', true);
                        echo '<option value="'.esc_attr($p->ID).'">'.esc_html($p->post_title).' - '.esc_html($price).' MAD</option>';
                    }
                echo '</select>';
            echo '</div>';
            
            echo '<div class="lmb-form-group">';
                echo '<label for="payment_reference">'.esc_html__('Payment Reference','lmb-core').'</label>';
                echo '<input type="text" name="payment_reference" id="payment_reference" placeholder="'.esc_attr__('Enter the reference from your invoice','lmb-core').'" required>';
                echo '<small>'.esc_html__('This is the unique reference number provided on the invoice you downloaded.','lmb-core').'</small>';
            echo '</div>';

            echo '<div class="lmb-form-group">';
                echo '<label for="proof_file">'.esc_html__('Proof of Payment File','lmb-core').'</label>';
                echo '<input type="file" name="proof_file" id="proof_file" accept="image/jpeg,image/png,application/pdf" required>';
                echo '<small>'.esc_html__('Accepted formats: JPG, PNG, PDF. Maximum size: 5MB.','lmb-core').'</small>';
            echo '</div>';
            
            echo '<div class="lmb-form-group">';
                echo '<label for="notes">'.esc_html__('Additional Notes (Optional)','lmb-core').'</label>';
                echo '<textarea name="notes" id="notes" rows="3" placeholder="'.esc_attr__('Any additional information for our team...','lmb-core').'"></textarea>';
            echo '</div>';
            
            echo '<div class="lmb-form-group">';
                echo '<button type="submit" name="lmb_upload_proof" class="lmb-btn lmb-btn-primary">'.esc_html__('Submit Proof for Verification','lmb-core').'</button>';
            echo '</div>';
        echo '</form>';
        echo '</div>';
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

        // File validation
        $file = $_FILES['proof_file'];
        $file_return = wp_check_filetype($file['name']);
        if(!in_array($file_return['ext'], ['jpg', 'jpeg', 'png', 'pdf'])) {
            return ['success' => false, 'message' => __('Invalid file type. Please upload a JPG, PNG, or PDF.', 'lmb-core')];
        }
        if($file['size'] > 5 * 1024 * 1024) { // 5MB
            return ['success' => false, 'message' => __('File is too large. Maximum size is 5MB.', 'lmb-core')];
        }

        // Handle the upload
        $attachment_id = media_handle_upload('proof_file', 0);
        if (is_wp_error($attachment_id)) {
            return ['success' => false, 'message' => $attachment_id->get_error_message()];
        }

        // Create the 'lmb_payment' post
        $payment_post_title = sprintf('Proof from %s for %s', $user->display_name, $package_title);
        $payment_id = wp_insert_post([
            'post_type' => 'lmb_payment',
            'post_title' => $payment_post_title,
            'post_status' => 'publish', // Important: it must be a public status to be visible in the admin list
            'post_author' => $user_id,
        ]);

        if (is_wp_error($payment_id)) {
            wp_delete_attachment($attachment_id, true); // Clean up uploaded file
            return ['success' => false, 'message' => __('Could not create payment record.', 'lmb-core')];
        }

        // Save all metadata
        update_post_meta($payment_id, 'user_id', $user_id);
        update_post_meta($payment_id, 'package_id', $package_id);
        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        update_post_meta($payment_id, 'payment_reference', $payment_reference);
        update_post_meta($payment_id, 'notes', sanitize_textarea_field($_POST['notes'] ?? ''));
        update_post_meta($payment_id, 'payment_status', 'pending'); // Initial status
        
        // Associate the attachment with the payment post
        wp_update_post(['ID' => $attachment_id, 'post_parent' => $payment_id]);

        LMB_Ad_Manager::log_activity(sprintf('Payment proof #%d submitted by %s for package "%s"', $payment_id, $user->display_name, $package_title));
        LMB_Notification_Manager::notify_admin('New Payment Proof Submitted', sprintf('User %s has submitted payment proof for the "%s" package. Please review it in the Payments menu.', $user->display_name, $package_title));

        return ['success' => true, 'message' => __('Your proof has been submitted and is pending review. We will notify you once it is processed.', 'lmb-core')];
    }
}