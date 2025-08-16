<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Payment verification system for manual bank transfer validation
 */
class LMB_Payment_Verifier {
    
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_verification_page']);
        add_action('wp_ajax_lmb_verify_payment', [__CLASS__, 'ajax_verify_payment']);
        add_action('wp_ajax_lmb_reject_payment', [__CLASS__, 'ajax_reject_payment']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        
        // Add payment upload functionality for users
        add_action('wp_ajax_lmb_upload_payment_proof', [__CLASS__, 'ajax_upload_payment_proof']);
        add_shortcode('lmb_payment_upload', [__CLASS__, 'payment_upload_shortcode']);
    }
    
    /**
     * Add payment verification page to admin menu
     */
    public static function add_verification_page() {
        add_submenu_page(
            'lmb-core',
            __('Payment Verification', 'lmb-core'),
            __('Verify Payments', 'lmb-core'),
            'manage_options',
            'lmb-payment-verification',
            [__CLASS__, 'render_verification_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public static function enqueue_scripts($hook) {
        if (strpos($hook, 'lmb-payment-verification') === false) {
            return;
        }
        
        wp_enqueue_script('lmb-payment-verifier', LMB_CORE_URL . 'assets/js/payment-verifier.js', ['jquery'], LMB_CORE_VERSION, true);
        wp_localize_script('lmb-payment-verifier', 'lmbPaymentVerifier', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmb_payment_verification'),
            'strings' => [
                'confirm_verify' => __('Are you sure you want to verify this payment?', 'lmb-core'),
                'confirm_reject' => __('Are you sure you want to reject this payment?', 'lmb-core'),
                'verified' => __('Payment verified successfully.', 'lmb-core'),
                'rejected' => __('Payment rejected.', 'lmb-core'),
                'error' => __('An error occurred. Please try again.', 'lmb-core')
            ]
        ]);
    }
    
    /**
     * Render payment verification page
     */
    public static function render_verification_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'lmb-core'));
        }
        
        $pending_payments = self::get_pending_payments();
        $verified_payments = self::get_verified_payments(20);
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Payment Verification', 'lmb-core'); ?></h1>
            
            <div class="lmb-payment-verification">
                <!-- Pending Payments -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('Pending Payment Verifications', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <?php if (empty($pending_payments)): ?>
                            <p><?php esc_html_e('No pending payments to verify.', 'lmb-core'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('User', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Package', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Amount', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Reference', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Proof', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Date', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_payments as $payment): ?>
                                        <tr data-payment-id="<?php echo esc_attr($payment->ID); ?>">
                                            <td>
                                                <?php 
                                                $user = get_userdata($payment->post_author);
                                                echo $user ? esc_html($user->display_name) : __('Unknown User', 'lmb-core');
                                                ?>
                                                <br><small><?php echo $user ? esc_html($user->user_email) : ''; ?></small>
                                            </td>
                                            <td><?php echo esc_html(get_post_meta($payment->ID, 'package_type', true)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($payment->ID, 'amount', true)); ?> MAD</td>
                                            <td><code><?php echo esc_html(get_post_meta($payment->ID, 'reference_number', true)); ?></code></td>
                                            <td>
                                                <?php 
                                                $proof_url = get_post_meta($payment->ID, 'payment_proof_url', true);
                                                if ($proof_url): ?>
                                                    <a href="<?php echo esc_url($proof_url); ?>" target="_blank" class="button button-small">
                                                        <?php esc_html_e('View Proof', 'lmb-core'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <em><?php esc_html_e('No proof uploaded', 'lmb-core'); ?></em>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html(date('Y-m-d H:i', strtotime($payment->post_date))); ?></td>
                                            <td>
                                                <button class="button button-primary lmb-verify-payment" 
                                                        data-payment-id="<?php echo esc_attr($payment->ID); ?>">
                                                    <?php esc_html_e('Verify', 'lmb-core'); ?>
                                                </button>
                                                <button class="button button-secondary lmb-reject-payment" 
                                                        data-payment-id="<?php echo esc_attr($payment->ID); ?>">
                                                    <?php esc_html_e('Reject', 'lmb-core'); ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Verified Payments -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2><?php esc_html_e('Recent Verified Payments', 'lmb-core'); ?></h2>
                    </div>
                    <div class="inside">
                        <?php if (empty($verified_payments)): ?>
                            <p><?php esc_html_e('No verified payments yet.', 'lmb-core'); ?></p>
                        <?php else: ?>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('User', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Package', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Amount', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Points Added', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Verified Date', 'lmb-core'); ?></th>
                                        <th><?php esc_html_e('Verified By', 'lmb-core'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($verified_payments as $payment): ?>
                                        <tr>
                                            <td>
                                                <?php 
                                                $user = get_userdata($payment->post_author);
                                                echo $user ? esc_html($user->display_name) : __('Unknown User', 'lmb-core');
                                                ?>
                                            </td>
                                            <td><?php echo esc_html(get_post_meta($payment->ID, 'package_type', true)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($payment->ID, 'amount', true)); ?> MAD</td>
                                            <td><?php echo esc_html(get_post_meta($payment->ID, 'points_added', true)); ?></td>
                                            <td><?php echo esc_html(get_post_meta($payment->ID, 'verified_date', true)); ?></td>
                                            <td>
                                                <?php 
                                                $verified_by = get_post_meta($payment->ID, 'verified_by', true);
                                                $admin = get_userdata($verified_by);
                                                echo $admin ? esc_html($admin->display_name) : __('Unknown', 'lmb-core');
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get pending payments
     */
    private static function get_pending_payments() {
        return get_posts([
            'post_type' => 'lmb_payment',
            'post_status' => 'pending',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'ASC'
        ]);
    }
    
    /**
     * Get verified payments
     */
    private static function get_verified_payments($limit = 20) {
        return get_posts([
            'post_type' => 'lmb_payment',
            'post_status' => 'verified',
            'posts_per_page' => $limit,
            'orderby' => 'modified',
            'order' => 'DESC'
        ]);
    }
    
    /**
     * AJAX verify payment
     */
    public static function ajax_verify_payment() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'lmb-core')]);
        }
        
        check_ajax_referer('lmb_payment_verification', 'nonce');
        
        $payment_id = absint($_POST['payment_id'] ?? 0);
        
        if (!$payment_id) {
            wp_send_json_error(['message' => __('Invalid payment ID.', 'lmb-core')]);
        }
        
        $payment = get_post($payment_id);
        if (!$payment || $payment->post_type !== 'lmb_payment') {
            wp_send_json_error(['message' => __('Payment not found.', 'lmb-core')]);
        }
        
        // Get payment details
        $user_id = $payment->post_author;
        $package_type = get_post_meta($payment_id, 'package_type', true);
        $amount = get_post_meta($payment_id, 'amount', true);
        
        // Calculate points to add based on package
        $points_to_add = self::calculate_points_for_package($package_type, $amount);
        
        // Add points to user
        $new_balance = LMB_Points::add($user_id, $points_to_add, sprintf(
            'Payment verified - %s package (%s MAD)',
            $package_type,
            $amount
        ));
        
        if ($new_balance === false) {
            wp_send_json_error(['message' => __('Failed to add points.', 'lmb-core')]);
        }
        
        // Update payment status
        wp_update_post([
            'ID' => $payment_id,
            'post_status' => 'verified'
        ]);
        
        // Add verification metadata
        update_post_meta($payment_id, 'verified_by', get_current_user_id());
        update_post_meta($payment_id, 'verified_date', current_time('mysql'));
        update_post_meta($payment_id, 'points_added', $points_to_add);
        
        // Send notification to user
        self::notify_payment_verified($user_id, $package_type, $points_to_add, $new_balance);
        
        wp_send_json_success([
            'message' => __('Payment verified successfully.', 'lmb-core'),
            'points_added' => $points_to_add,
            'new_balance' => $new_balance
        ]);
    }
    
    /**
     * AJAX reject payment
     */
    public static function ajax_reject_payment() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'lmb-core')]);
        }
        
        check_ajax_referer('lmb_payment_verification', 'nonce');
        
        $payment_id = absint($_POST['payment_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$payment_id) {
            wp_send_json_error(['message' => __('Invalid payment ID.', 'lmb-core')]);
        }
        
        $payment = get_post($payment_id);
        if (!$payment || $payment->post_type !== 'lmb_payment') {
            wp_send_json_error(['message' => __('Payment not found.', 'lmb-core')]);
        }
        
        // Update payment status
        wp_update_post([
            'ID' => $payment_id,
            'post_status' => 'rejected'
        ]);
        
        // Add rejection metadata
        update_post_meta($payment_id, 'rejected_by', get_current_user_id());
        update_post_meta($payment_id, 'rejected_date', current_time('mysql'));
        update_post_meta($payment_id, 'rejection_reason', $reason);
        
        // Send notification to user
        self::notify_payment_rejected($payment->post_author, $reason);
        
        wp_send_json_success([
            'message' => __('Payment rejected.', 'lmb-core')
        ]);
    }
    
    /**
     * Calculate points for package
     */
    private static function calculate_points_for_package($package_type, $amount) {
        // Define package points mapping
        $package_points = apply_filters('lmb_package_points', [
            'basic' => 10,
            'standard' => 25,
            'premium' => 50,
            'enterprise' => 100
        ]);
        
        // If package type is defined, use it
        if (isset($package_points[$package_type])) {
            return $package_points[$package_type];
        }
        
        // Otherwise, calculate based on amount (1 point per 10 MAD)
        return max(1, floor($amount / 10));
    }
    
    /**
     * Notify user of payment verification
     */
    private static function notify_payment_verified($user_id, $package_type, $points_added, $new_balance) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = sprintf(__('[%s] Payment Verified - Points Added', 'lmb-core'), get_bloginfo('name'));
        $message = sprintf(
            __("Hello %s,\n\nGreat news! Your payment has been verified and points have been added to your account.\n\nPackage: %s\nPoints Added: %d\nNew Balance: %d points\n\nYou can now submit legal ads using your points.\n\nView your dashboard: %s\n\nBest regards,\nThe %s Team", 'lmb-core'),
            $user->display_name,
            $package_type,
            $points_added,
            $new_balance,
            home_url('/dashboard'),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        do_action('lmb_payment_verified', $user_id, $package_type, $points_added);
    }
    
    /**
     * Notify user of payment rejection
     */
    private static function notify_payment_rejected($user_id, $reason) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = sprintf(__('[%s] Payment Verification Issue', 'lmb-core'), get_bloginfo('name'));
        $message = sprintf(
            __("Hello %s,\n\nWe were unable to verify your recent payment.\n\nReason: %s\n\nPlease contact our support team for assistance or submit a new payment with correct information.\n\nSupport: %s\n\nBest regards,\nThe %s Team", 'lmb-core'),
            $user->display_name,
            $reason ?: __('Payment information could not be verified.', 'lmb-core'),
            get_option('admin_email'),
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
        
        do_action('lmb_payment_rejected', $user_id, $reason);
    }
    
    /**
     * Payment upload shortcode for users
     */
    public static function payment_upload_shortcode($atts = []) {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to upload payment proof.', 'lmb-core') . '</p>';
        }
        
        $atts = shortcode_atts([
            'packages' => 'basic,standard,premium'
        ], $atts);
        
        $packages = array_map('trim', explode(',', $atts['packages']));
        
        ob_start();
        ?>
        <div class="lmb-payment-upload">
            <form id="lmb-payment-form" enctype="multipart/form-data">
                <?php wp_nonce_field('lmb_upload_payment_proof', 'payment_nonce'); ?>
                
                <div class="form-group">
                    <label for="package_type"><?php esc_html_e('Select Package', 'lmb-core'); ?></label>
                    <select name="package_type" id="package_type" required>
                        <option value=""><?php esc_html_e('Choose a package...', 'lmb-core'); ?></option>
                        <?php foreach ($packages as $package): ?>
                            <option value="<?php echo esc_attr($package); ?>">
                                <?php echo esc_html(ucfirst($package)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="amount"><?php esc_html_e('Amount Paid (MAD)', 'lmb-core'); ?></label>
                    <input type="number" name="amount" id="amount" min="1" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="reference_number"><?php esc_html_e('Bank Reference Number', 'lmb-core'); ?></label>
                    <input type="text" name="reference_number" id="reference_number" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_proof"><?php esc_html_e('Payment Proof (Image/PDF)', 'lmb-core'); ?></label>
                    <input type="file" name="payment_proof" id="payment_proof" accept="image/*,application/pdf" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Submit Payment Proof', 'lmb-core'); ?>
                    </button>
                </div>
            </form>
            
            <div id="lmb-payment-result" style="display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#lmb-payment-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = new FormData(this);
                formData.append('action', 'lmb_upload_payment_proof');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#lmb-payment-result').html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>').show();
                            $('#lmb-payment-form')[0].reset();
                        } else {
                            $('#lmb-payment-result').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>').show();
                        }
                    },
                    error: function() {
                        $('#lmb-payment-result').html('<div class="notice notice-error"><p><?php esc_html_e('An error occurred. Please try again.', 'lmb-core'); ?></p></div>').show();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * AJAX upload payment proof
     */
    public static function ajax_upload_payment_proof() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'lmb-core')]);
        }
        
        check_ajax_referer('lmb_upload_payment_proof', 'payment_nonce');
        
        $package_type = sanitize_text_field($_POST['package_type'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $reference_number = sanitize_text_field($_POST['reference_number'] ?? '');
        
        if (empty($package_type) || $amount <= 0 || empty($reference_number)) {
            wp_send_json_error(['message' => __('Please fill all required fields.', 'lmb-core')]);
        }
        
        // Handle file upload
        if (empty($_FILES['payment_proof'])) {
            wp_send_json_error(['message' => __('Please upload payment proof.', 'lmb-core')]);
        }
        
        $uploaded_file = wp_handle_upload($_FILES['payment_proof'], ['test_form' => false]);
        
        if (isset($uploaded_file['error'])) {
            wp_send_json_error(['message' => $uploaded_file['error']]);
        }
        
        // Create payment record
        $payment_id = wp_insert_post([
            'post_type' => 'lmb_payment',
            'post_status' => 'pending',
            'post_title' => sprintf(
                'Payment - %s - %s MAD - %s',
                $package_type,
                $amount,
                wp_get_current_user()->display_name
            ),
            'post_author' => get_current_user_id(),
            'meta_input' => [
                'package_type' => $package_type,
                'amount' => $amount,
                'reference_number' => $reference_number,
                'payment_proof_url' => $uploaded_file['url'],
                'payment_proof_path' => $uploaded_file['file']
            ]
        ]);
        
        if (is_wp_error($payment_id)) {
            wp_send_json_error(['message' => __('Failed to create payment record.', 'lmb-core')]);
        }
        
        // Notify admins
        self::notify_admins_new_payment($payment_id);
        
        wp_send_json_success([
            'message' => __('Payment proof uploaded successfully. We will verify it within 24 hours.', 'lmb-core')
        ]);
    }
    
    /**
     * Notify admins of new payment
     */
    private static function notify_admins_new_payment($payment_id) {
        $payment = get_post($payment_id);
        $user = get_userdata($payment->post_author);
        
        $admin_emails = get_users(['role' => 'administrator', 'fields' => 'user_email']);
        
        $subject = sprintf(__('[%s] New Payment Verification Required', 'lmb-core'), get_bloginfo('name'));
        $message = sprintf(
            __("A new payment requires verification.\n\nUser: %s (%s)\nPackage: %s\nAmount: %s MAD\nReference: %s\n\nVerify payment: %s", 'lmb-core'),
            $user->display_name,
            $user->user_email,
            get_post_meta($payment_id, 'package_type', true),
            get_post_meta($payment_id, 'amount', true),
            get_post_meta($payment_id, 'reference_number', true),
            admin_url('admin.php?page=lmb-payment-verification')
        );
        
        foreach ($admin_emails as $admin_email) {
            wp_mail($admin_email, $subject, $message);
        }
    }
}

// Initialize payment verifier
LMB_Payment_Verifier::init();