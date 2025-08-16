<?php
if (!defined('ABSPATH')) {
    exit;
}

class LMB_Form_Handler {
    
    private static $allowed_ad_types = [
        'Liquidation - definitive',
        'Liquidation - anticipee', 
        'Constitution - SARL',
        'Constitution - SARL AU',
        'Modification - Capital',
        'Modification - parts',
        'Modification - denomination',
        'Modification - seige',
        'Modification - gerant',
        'Modification - objects'
    ];

    /**
     * Initialize form handling with multiple hooks
     */
    public static function init() {
        // Primary Elementor Pro hook
        add_action('elementor_pro/forms/new_record', [__CLASS__, 'handle_ad_submission'], 10, 2);
        
        // Alternative hooks for better compatibility
        add_action('elementor_pro/forms/process', [__CLASS__, 'handle_elementor_form'], 10, 2);
        
        // AJAX fallback for direct form submissions
        add_action('wp_ajax_lmb_submit_ad', [__CLASS__, 'ajax_submit_ad']);
        add_action('wp_ajax_nopriv_lmb_submit_ad', [__CLASS__, 'ajax_submit_ad_logged_out']);
        
        // Hook into WordPress form submissions as fallback
        add_action('wp_loaded', [__CLASS__, 'maybe_process_form']);
        
        // Validation AJAX
        add_action('wp_ajax_lmb_validate_ad', [__CLASS__, 'ajax_validate_ad']);
        add_action('wp_ajax_nopriv_lmb_validate_ad', [__CLASS__, 'ajax_validate_ad_guest']);
        
        // Add admin status change handlers
        add_action('wp_ajax_lmb_change_ad_status', [__CLASS__, 'ajax_change_ad_status']);
        add_action('transition_post_status', [__CLASS__, 'on_ad_status_change'], 10, 3);
    }

    /**
     * Main entry point for form submissions
     */
    public static function maybe_process_form() {
        if (!isset($_POST['lmb_form_submit']) || !isset($_POST['ad_type'])) {
            return;
        }

        // Verify nonce for security
        if (!wp_verify_nonce($_POST['lmb_form_nonce'] ?? '', 'lmb_submit_ad_form')) {
            wp_die(__('Security check failed.', 'lmb-core'));
        }

        try {
            $result = self::process_ad_submission($_POST);
            
            if (is_wp_error($result)) {
                // Store error for display
                set_transient('lmb_form_error_' . session_id(), $result->get_error_message(), 300);
                wp_redirect(wp_get_referer() . '#lmb-form-error');
            } else {
                // Success - redirect with success message
                set_transient('lmb_form_success_' . session_id(), __('Ad submitted successfully!', 'lmb-core'), 300);
                wp_redirect(wp_get_referer() . '#lmb-form-success');
            }
            exit;
            
        } catch (Exception $e) {
            LMB_Error_Handler::log_error('Form processing exception: ' . $e->getMessage(), $_POST);
            wp_die(__('An error occurred. Please try again.', 'lmb-core'));
        }
    }

    /**
     * Handle Elementor Pro form submissions
     */
    public static function handle_ad_submission($record, $handler) {
        // Check if this is an LMB form by looking for ad_type field
        $form_data = $record->get_formatted_data();
        
        if (!isset($form_data['ad_type']) || !isset($form_data['full_text'])) {
            return; // Not an LMB form
        }

        try {
            $result = self::process_ad_submission($form_data);
            
            if (is_wp_error($result)) {
                $handler->add_error($result->get_error_code(), $result->get_error_message());
                return;
            }

            // Set success response data
            $handler->add_response_data(true, 'success', __(
                'Your legal ad has been submitted successfully and is now pending review.', 
                'lmb-core'
            ));

        } catch (Exception $e) {
            LMB_Error_Handler::log_error('Elementor form error: ' . $e->getMessage(), $form_data);
            $handler->add_error('submission_failed', __('Submission failed. Please try again.', 'lmb-core'));
        }
    }

    /**
     * Alternative Elementor form handler
     */
    public static function handle_elementor_form($record, $handler) {
        return self::handle_ad_submission($record, $handler);
    }

    /**
     * AJAX handler for logged-in users
     */
    public static function ajax_submit_ad() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'lmb-core')]);
        }

        check_ajax_referer('lmb_submit_ad', 'nonce');

        try {
            $result = self::process_ad_submission($_POST);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }

            wp_send_json_success([
                'message' => __('Ad submitted successfully!', 'lmb-core'),
                'ad_id' => $result
            ]);

        } catch (Exception $e) {
            wp_send_json_error(['message' => __('An error occurred. Please try again.', 'lmb-core')]);
        }
    }

    /**
     * AJAX handler for non-logged users (redirect to login)
     */
    public static function ajax_submit_ad_logged_out() {
        wp_send_json_error([
            'message' => __('You must be logged in to submit an ad.', 'lmb-core'),
            'redirect' => wp_login_url(home_url('/dashboard'))
        ]);
    }

    /**
     * Core ad submission processing
     */
    private static function process_ad_submission($form_data) {
        // Validate and sanitize data
        $validated_data = self::validate_and_sanitize($form_data);
        if (is_wp_error($validated_data)) {
            return $validated_data;
        }

        // Check user permissions and points
        $user_check = self::validate_user_and_points();
        if (is_wp_error($user_check)) {
            return $user_check;
        }

        $user_id = $user_check['user_id'];
        $is_staff = $user_check['is_staff'];
        $points_deducted = $user_check['points_deducted'] ?? 0;

        // Create the ad post
        $ad_id = self::create_ad_post($validated_data, $user_id, $is_staff);
        
        if (is_wp_error($ad_id)) {
            // Refund points if deducted
            if ($points_deducted > 0) {
                LMB_Points::add($user_id, $points_deducted, 'Refund for failed submission');
            }
            return $ad_id;
        }

        // Send notifications
        self::send_notifications($ad_id, $user_id);

        // Log the submission
        self::log_submission($ad_id, $user_id, $validated_data);

        return $ad_id;
    }

    /**
     * Enhanced validation and sanitization
     */
    private static function validate_and_sanitize($form_data) {
        $errors = [];
        $sanitized = [];

        // Validate ad_type
        $ad_type = sanitize_text_field($form_data['ad_type'] ?? '');
        if (empty($ad_type)) {
            $errors[] = __('Ad type is required.', 'lmb-core');
        } elseif (!in_array($ad_type, self::$allowed_ad_types)) {
            $errors[] = __('Invalid ad type selected.', 'lmb-core');
        } else {
            $sanitized['ad_type'] = $ad_type;
        }

        // Validate full_text
        $full_text = wp_kses_post($form_data['full_text'] ?? '');
        $text_length = strlen(trim(strip_tags($full_text)));
        
        if (empty($full_text)) {
            $errors[] = __('Ad content is required.', 'lmb-core');
        } elseif ($text_length < 50) {
            $errors[] = __('Ad content must be at least 50 characters long.', 'lmb-core');
        } elseif ($text_length > 5000) {
            $errors[] = __('Ad content cannot exceed 5000 characters.', 'lmb-core');
        } else {
            $sanitized['full_text'] = $full_text;
        }

        // Optional fields
        if (!empty($form_data['contact_email'])) {
            $email = sanitize_email($form_data['contact_email']);
            if (!is_email($email)) {
                $errors[] = __('Invalid email address.', 'lmb-core');
            } else {
                $sanitized['contact_email'] = $email;
            }
        }

        if (!empty($form_data['contact_phone'])) {
            $sanitized['contact_phone'] = preg_replace('/[^0-9+\-\s\(\)]/', '', $form_data['contact_phone']);
        }

        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }

        return $sanitized;
    }

    /**
     * Validate user permissions and handle points
     */
    private static function validate_user_and_points() {
        if (!is_user_logged_in()) {
            return new WP_Error('not_logged_in', __('You must be logged in to submit an ad.', 'lmb-core'));
        }

        $user_id = get_current_user_id();
        $user = wp_get_current_user();

        // Check if user account is active
        if (!$user->exists()) {
            return new WP_Error('invalid_user', __('Invalid user account.', 'lmb-core'));
        }

        // Check if user is staff (can bypass points)
        $is_staff = self::is_user_staff($user_id);
        $points_deducted = 0;

        // Handle points for non-staff users
        if (!$is_staff) {
            $points_per_ad = (int) get_option('lmb_points_per_ad', 1);
            
            if ($points_per_ad > 0) {
                $user_points = LMB_Points::get($user_id);
                
                if ($user_points < $points_per_ad) {
                    return new WP_Error(
                        'insufficient_points',
                        sprintf(
                            __('Insufficient points. You have %d points but need %d points to submit an ad.', 'lmb-core'),
                            $user_points,
                            $points_per_ad
                        )
                    );
                }

                // Deduct points
                LMB_Points::deduct($user_id, $points_per_ad, 'Ad submission fee');
                $points_deducted = $points_per_ad;
            }
        }

        return [
            'user_id' => $user_id,
            'user' => $user,
            'is_staff' => $is_staff,
            'points_deducted' => $points_deducted
        ];
    }

    /**
     * Create the ad post with proper metadata
     */
    private static function create_ad_post($validated_data, $user_id, $is_staff) {
        $user = get_userdata($user_id);
        
        // Determine initial status
        $initial_status = $is_staff ? 'pending_review' : 'draft';
        
        // Create post
        $post_data = [
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'draft', // Always start as draft for safety
            'post_title' => sprintf(
                '%s — %s (%s)', 
                $validated_data['ad_type'],
                $user->display_name,
                current_time('Y-m-d H:i')
            ),
            'post_author' => $user_id,
            'post_content' => '', // Content stored in ACF fields
            'meta_input' => [
                '_lmb_ad_status' => $initial_status,
                '_lmb_client_id' => $user_id,
                '_lmb_ad_type' => $validated_data['ad_type'],
                '_lmb_submission_ip' => self::get_client_ip(),
                '_lmb_submission_time' => current_time('mysql')
            ]
        ];

        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            return new WP_Error(
                'post_creation_failed',
                __('Failed to create ad post. Please try again.', 'lmb-core')
            );
        }

        // Save ACF fields
        $acf_success = true;
        $acf_fields = [
            'ad_type' => $validated_data['ad_type'],
            'full_text' => $validated_data['full_text'],
            'lmb_status' => $initial_status,
            'lmb_client_id' => $user_id,
        ];

        // Add optional fields if present
        if (!empty($validated_data['contact_email'])) {
            $acf_fields['contact_email'] = $validated_data['contact_email'];
        }
        
        if (!empty($validated_data['contact_phone'])) {
            $acf_fields['contact_phone'] = $validated_data['contact_phone'];
        }

        // Save each ACF field
        foreach ($acf_fields as $field_key => $field_value) {
            if (!update_field($field_key, $field_value, $post_id)) {
                $acf_success = false;
                LMB_Error_Handler::log_error("Failed to save ACF field: {$field_key}", [
                    'post_id' => $post_id,
                    'field_value' => $field_value
                ]);
            }
        }

        if (!$acf_success) {
            LMB_Error_Handler::log_error('Some ACF fields failed to save', [
                'post_id' => $post_id,
                'fields' => $acf_fields
            ]);
        }

        return $post_id;
    }

    /**
     * Send notifications for new ad submission
     */
    private static function send_notifications($post_id, $user_id) {
        $user = get_userdata($user_id);
        $ad_type = get_field('ad_type', $post_id);
        $ad_status = get_field('lmb_status', $post_id);

        // Notify administrators
        $admin_users = get_users(['role' => 'administrator']);
        $admin_emails = wp_list_pluck($admin_users, 'user_email');

        if (!empty($admin_emails)) {
            $admin_subject = sprintf(__('[%s] New Legal Ad Submitted', 'lmb-core'), get_bloginfo('name'));
            $admin_message = sprintf(
                __("A new legal ad has been submitted and requires review.\n\nDetails:\n- Ad ID: %d\n- Type: %s\n- Submitted by: %s (%s)\n- Status: %s\n- Submitted: %s\n\nReview in admin: %s", 'lmb-core'),
                $post_id,
                $ad_type,
                $user->display_name,
                $user->user_email,
                $ad_status,
                current_time('Y-m-d H:i:s'),
                admin_url("post.php?post={$post_id}&action=edit")
            );

            foreach ($admin_emails as $admin_email) {
                wp_mail($admin_email, $admin_subject, $admin_message);
            }
        }

        // Notify user
        $user_subject = sprintf(__('[%s] Ad Submission Confirmation', 'lmb-core'), get_bloginfo('name'));
        $user_message = sprintf(
            __("Thank you for submitting your legal ad.\n\nDetails:\n- Ad ID: %d\n- Type: %s\n- Status: %s\n- Submitted: %s\n\nYou will receive an email notification once your ad is reviewed and published.\n\nView your ads: %s", 'lmb-core'),
            $post_id,
            $ad_type,
            ucfirst(str_replace('_', ' ', $ad_status)),
            current_time('Y-m-d H:i:s'),
            home_url('/dashboard')
        );

        wp_mail($user->user_email, $user_subject, $user_message);

        // Fire action for extensibility
        do_action('lmb_ad_submitted', $post_id, $user_id, $ad_type);
    }

    /**
     * Log submission for audit trail
     */
    private static function log_submission($post_id, $user_id, $validated_data) {
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'action' => 'ad_submitted',
            'post_id' => $post_id,
            'user_id' => $user_id,
            'ip_address' => self::get_client_ip(),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'),
            'data' => wp_json_encode($validated_data)
        ];

        // Store in options (you might want to create a custom table for better performance)
        $logs = get_option('lmb_submission_logs', []);
        $logs[] = $log_entry;
        
        // Keep only the last 1000 entries
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        update_option('lmb_submission_logs', $logs, false);

        do_action('lmb_log_submission', $log_entry);
    }

    /**
     * Check if user has staff privileges
     */
    private static function is_user_staff($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return false;

        $staff_roles = array_map('trim', explode(',', get_option('lmb_staff_roles', 'administrator,editor')));
        return !empty(array_intersect($user->roles, $staff_roles));
    }

    /**
     * Get client IP address securely
     */
    private static function get_client_ip() {
        $ip_keys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * AJAX validation for real-time form feedback
     */
    public static function ajax_validate_ad() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'lmb-core')]);
        }

        check_ajax_referer('lmb_validate_ad', 'nonce');

        $ad_type = sanitize_text_field($_POST['ad_type'] ?? '');
        $full_text = wp_kses_post($_POST['full_text'] ?? '');

        $errors = [];
        $warnings = [];

        // Validate ad type
        if (empty($ad_type)) {
            $errors[] = __('Please select an ad type.', 'lmb-core');
        } elseif (!in_array($ad_type, self::$allowed_ad_types)) {
            $errors[] = __('Invalid ad type selected.', 'lmb-core');
        }

        // Validate content
        $text_length = strlen(trim(strip_tags($full_text)));
        
        if (empty($full_text)) {
            $errors[] = __('Ad content is required.', 'lmb-core');
        } elseif ($text_length < 50) {
            $errors[] = sprintf(__('Ad content is too short. Minimum 50 characters required, you have %d.', 'lmb-core'), $text_length);
        } elseif ($text_length > 5000) {
            $errors[] = sprintf(__('Ad content is too long. Maximum 5000 characters allowed, you have %d.', 'lmb-core'), $text_length);
        } elseif ($text_length < 100) {
            $warnings[] = __('Consider adding more detail to your ad for better results.', 'lmb-core');
        }

        // Check points requirement for non-staff users
        if (!self::is_user_staff(get_current_user_id())) {
            $points_per_ad = (int) get_option('lmb_points_per_ad', 1);
            $user_points = LMB_Points::get(get_current_user_id());

            if ($user_points < $points_per_ad) {
                $errors[] = sprintf(
                    __('Insufficient points. You have %d points but need %d to submit an ad.', 'lmb-core'),
                    $user_points,
                    $points_per_ad
                );
            }
        }

        if (!empty($errors)) {
            wp_send_json_error([
                'errors' => $errors,
                'warnings' => $warnings
            ]);
        }

        wp_send_json_success([
            'message' => __('Validation passed. You can submit your ad.', 'lmb-core'),
            'warnings' => $warnings
        ]);
    }

    /**
     * AJAX validation for non-logged users (show login requirement)
     */
    public static function ajax_validate_ad_guest() {
        wp_send_json_error([
            'message' => __('You must be logged in to submit an ad.', 'lmb-core'),
            'login_url' => wp_login_url(home_url('/dashboard'))
        ]);
    }

    /**
     * AJAX handler for admin status changes
     */
    public static function ajax_change_ad_status() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', 'lmb-core')]);
        }

        check_ajax_referer('lmb_admin_actions', 'nonce');

        $post_id = absint($_POST['post_id'] ?? 0);
        $new_status = sanitize_text_field($_POST['new_status'] ?? '');

        $allowed_statuses = ['draft', 'pending_review', 'published', 'denied'];
        
        if (!$post_id || !in_array($new_status, $allowed_statuses)) {
            wp_send_json_error(['message' => __('Invalid parameters.', 'lmb-core')]);
        }

        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'lmb_legal_ad') {
            wp_send_json_error(['message' => __('Invalid ad ID.', 'lmb-core')]);
        }

        // Update status
        update_field('lmb_status', $new_status, $post_id);
        update_post_meta($post_id, '_lmb_ad_status', $new_status);

        // Update post status if needed
        if ($new_status === 'published') {
            wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);
        }

        // Send notification to user
        $client_id = get_field('lmb_client_id', $post_id);
        if ($client_id) {
            self::notify_status_change($post_id, $client_id, $new_status);
        }

        wp_send_json_success([
            'message' => __('Status updated successfully.', 'lmb-core'),
            'new_status' => $new_status
        ]);
    }

    /**
     * Handle post status transitions
     */
    public static function on_ad_status_change($new_status, $old_status, $post) {
        if ($post->post_type !== 'lmb_legal_ad' || $new_status === $old_status) {
            return;
        }

        $client_id = get_field('lmb_client_id', $post->ID);
        if ($client_id) {
            // Update internal status to match post status
            $internal_status = ($new_status === 'publish') ? 'published' : $new_status;
            update_field('lmb_status', $internal_status, $post->ID);
            
            // Notify user of status change
            self::notify_status_change($post->ID, $client_id, $internal_status);
        }

        // Log the change
        LMB_Error_Handler::log_error("Ad status changed", [
            'post_id' => $post->ID,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'changed_by' => get_current_user_id()
        ]);
    }

    /**
     * Notify user of status change
     */
    private static function notify_status_change($post_id, $user_id, $new_status) {
        $user = get_userdata($user_id);
        $ad_type = get_field('ad_type', $post_id);
        
        if (!$user) return;

        $status_messages = [
            'pending_review' => __('Your ad is now pending review by our team.', 'lmb-core'),
            'published' => __('Congratulations! Your ad has been approved and is now published.', 'lmb-core'),
            'denied' => __('Unfortunately, your ad was not approved. Please contact support for more information.', 'lmb-core'),
            'draft' => __('Your ad has been moved back to draft status.', 'lmb-core')
        ];

        $subject = sprintf(__('[%s] Ad Status Update - %s', 'lmb-core'), get_bloginfo('name'), $ad_type);
        $message = sprintf(
            __("Hello %s,\n\nYour legal ad (ID: %d, Type: %s) status has been updated.\n\nNew Status: %s\n%s\n\nView your ads: %s\n\nBest regards,\n%s Team", 'lmb-core'),
            $user->display_name,
            $post_id,
            $ad_type,
            ucfirst(str_replace('_', ' ', $new_status)),
            $status_messages[$new_status] ?? '',
            home_url('/dashboard'),
            get_bloginfo('name')
        );

        wp_mail($user->user_email, $subject, $message);

        // Fire action for extensibility
        do_action('lmb_ad_status_changed', $post_id, $user_id, $new_status);
    }

    /**
     * Get form submission statistics
     */
    public static function get_submission_stats($period = '30 days') {
        global $wpdb;
        
        $date_query = "";
        if ($period) {
            $date_query = $wpdb->prepare("AND post_date >= DATE_SUB(NOW(), INTERVAL %s)", $period);
        }

        $stats = [
            'total_submissions' => (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'lmb_legal_ad' {$date_query}"
            ),
            'by_status' => [],
            'by_type' => [],
            'by_user_type' => ['staff' => 0, 'regular' => 0]
        ];

        // Get submissions by status
        $status_results = $wpdb->get_results(
            "SELECT pm.meta_value as status, COUNT(*) as count 
             FROM {$wpdb->posts} p 
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'lmb_legal_ad' 
             AND pm.meta_key = '_lmb_ad_status' 
             {$date_query}
             GROUP BY pm.meta_value"
        );

        foreach ($status_results as $result) {
            $stats['by_status'][$result->status] = (int) $result->count;
        }

        // Get submissions by ad type
        $type_results = $wpdb->get_results(
            "SELECT pm.meta_value as ad_type, COUNT(*) as count 
             FROM {$wpdb->posts} p 
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_type = 'lmb_legal_ad' 
             AND pm.meta_key = '_lmb_ad_type' 
             {$date_query}
             GROUP BY pm.meta_value"
        );

        foreach ($type_results as $result) {
            $stats['by_type'][$result->ad_type] = (int) $result->count;
        }

        return $stats;
    }

    /**
     * Clean up old submission logs
     */
    public static function cleanup_old_logs($days = 90) {
        $logs = get_option('lmb_submission_logs', []);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $cleaned_logs = array_filter($logs, function($log) use ($cutoff_date) {
            return $log['timestamp'] > $cutoff_date;
        });
        
        if (count($cleaned_logs) !== count($logs)) {
            update_option('lmb_submission_logs', array_values($cleaned_logs), false);
            
            LMB_Error_Handler::log_error(sprintf(
                'Cleaned up %d old submission log entries', 
                count($logs) - count($cleaned_logs)
            ));
        }
    }
}

// Initialize the form handler
LMB_Form_Handler::init();