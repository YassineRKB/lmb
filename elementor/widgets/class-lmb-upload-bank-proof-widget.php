<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_bank_proof'; }
    public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) { echo '<p>'.esc_html__('Login required.','lmb-core').'</p>'; return; }

        if (isset($_POST['lmb_upload_proof']) && wp_verify_nonce($_POST['_wpnonce'], 'lmb_upload_bank_proof')) {
            $result = self::handle_upload();
            if ($result['success']) {
                echo '<div class="lmb-success-message">';
                echo '<h3>'.esc_html__('Payment Proof Uploaded Successfully','lmb-core').'</h3>';
                echo '<p>'.esc_html($result['message']).'</p>';
                echo '</div>';
            } else {
                echo '<div class="lmb-error-message">';
                echo '<h3>'.esc_html__('Upload Failed','lmb-core').'</h3>';
                echo '<p>'.esc_html($result['message']).'</p>';
                echo '</div>';
            }
        }

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1]);
        
        echo '<div class="lmb-upload-form-container">';
        echo '<h3>'.esc_html__('Upload Payment Proof','lmb-core').'</h3>';
        echo '<p>'.esc_html__('Please upload proof of your bank transfer to verify your payment.','lmb-core').'</p>';
        
        echo '<form method="post" enctype="multipart/form-data" class="lmb-upload-form">';
        wp_nonce_field('lmb_upload_bank_proof');
        
        echo '<div class="lmb-form-group">';
        echo '<label for="package_id">'.esc_html__('Package','lmb-core').'</label>';
        echo '<select name="package_id" id="package_id" required>';
        echo '<option value="">'.esc_html__('Select a package','lmb-core').'</option>';
        foreach ($packages as $p) {
            $price = get_post_meta($p->ID, 'price', true);
            echo '<option value="'.$p->ID.'">'.esc_html($p->post_title).' - '.esc_html($price).' MAD</option>';
        }
        echo '</select>';
        echo '</div>';
        
        echo '<div class="lmb-form-group">';
        echo '<label for="proof_file">'.esc_html__('Payment Proof','lmb-core').'</label>';
        echo '<input type="file" name="proof_file" id="proof_file" accept="image/*,.pdf" required>';
        echo '<small>'.esc_html__('Accepted formats: JPG, PNG, PDF (Max 5MB)','lmb-core').'</small>';
        echo '</div>';
        
        echo '<div class="lmb-form-group">';
        echo '<label for="payment_reference">'.esc_html__('Payment Reference','lmb-core').'</label>';
        echo '<input type="text" name="payment_reference" id="payment_reference" placeholder="'.esc_attr__('Enter your payment reference','lmb-core').'">';
        echo '<small>'.esc_html__('The reference number from your invoice','lmb-core').'</small>';
        echo '</div>';
        
        echo '<div class="lmb-form-group">';
        echo '<label for="notes">'.esc_html__('Additional Notes','lmb-core').'</label>';
        echo '<textarea name="notes" id="notes" rows="3" placeholder="'.esc_attr__('Any additional information...','lmb-core').'"></textarea>';
        echo '</div>';
        
        echo '<div class="lmb-form-group">';
        echo '<button type="submit" name="lmb_upload_proof" class="lmb-submit-btn">'.esc_html__('Upload Payment Proof','lmb-core').'</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        
        $user_payments = get_posts([
            'post_type' => 'lmb_payment',
            'meta_query' => [
                ['key' => 'user_id', 'value' => get_current_user_id(), 'compare' => '=']
            ],
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        if ($user_payments) {
            echo '<div class="lmb-payment-history">';
            echo '<h3>'.esc_html__('Your Recent Payments','lmb-core').'</h3>';
            echo '<table class="lmb-payments-table">';
            echo '<thead><tr><th>'.esc_html__('Date','lmb-core').'</th><th>'.esc_html__('Package','lmb-core').'</th><th>'.esc_html__('Status','lmb-core').'</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($user_payments as $payment) {
                $package_id = get_post_meta($payment->ID, 'package_id', true);
                $status = get_post_meta($payment->ID, 'payment_status', true);
                $package_title = $package_id ? get_the_title($package_id) : 'Unknown';
                
                echo '<tr>';
                echo '<td>'.esc_html(get_the_date('', $payment->ID)).'</td>';
                echo '<td>'.esc_html($package_title).'</td>';
                echo '<td><span class="lmb-status-'.esc_attr($status).'">'.esc_html(ucfirst($status)).'</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>';
        }
    }
    
    private static function handle_upload() {
        if (!is_user_logged_in()) {
            return ['success' => false, 'message' => 'Authentication required'];
        }
        
        $user_id = get_current_user_id();
        $package_id = (int) ($_POST['package_id'] ?? 0);
        $payment_reference = sanitize_text_field($_POST['payment_reference'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$package_id) {
            return ['success' => false, 'message' => 'Please select a package'];
        }
        
        if (empty($_FILES['proof_file']['name'])) {
            return ['success' => false, 'message' => 'Please select a file to upload'];
        }
        
        $file = $_FILES['proof_file'];
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file['type'], $allowed_types)) {
            return ['success' => false, 'message' => 'Invalid file type. Please upload JPG, PNG, or PDF files only.'];
        }
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'message' => 'File too large. Maximum size is 5MB.'];
        }
        
        $attachment_id = media_handle_upload('proof_file', 0);
        if (is_wp_error($attachment_id)) {
            return ['success' => false, 'message' => 'File upload failed: ' . $attachment_id->get_error_message()];
        }
        
        $payment_id = wp_insert_post([
            'post_type' => 'lmb_payment',
            'post_title' => 'Payment proof by ' . wp_get_current_user()->display_name,
            'post_status' => 'publish',
        ]);
        
        if (is_wp_error($payment_id)) {
            return ['success' => false, 'message' => 'Failed to create payment record'];
        }
        
        update_post_meta($payment_id, 'user_id', $user_id);
        update_post_meta($payment_id, 'package_id', $package_id);
        update_post_meta($payment_id, 'proof_attachment_id', $attachment_id);
        update_post_meta($payment_id, 'payment_reference', $payment_reference);
        update_post_meta($payment_id, 'payment_status', 'pending');
        update_post_meta($payment_id, 'notes', $notes);
        
        LMB_Ad_Manager::log_activity(sprintf(
            'Payment proof uploaded by %s for package %s',
            wp_get_current_user()->display_name,
            get_the_title($package_id)
        ));
        
        LMB_Notification_Manager::notify_admin(
            'New Payment Proof Uploaded',
            sprintf(
                'User %s has uploaded payment proof for package %s. Please review and verify.',
                wp_get_current_user()->display_name,
                get_the_title($package_id)
            )
        );
        
        return [
            'success' => true, 
            'message' => 'Payment proof uploaded successfully. We will review and verify your payment within 24 hours.'
        ];
    }
}