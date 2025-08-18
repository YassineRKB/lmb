<?php
if (!defined('ABSPATH')) exit;

class LMB_Notification_Manager {
    
    public static function should_send_email() {
        return (bool) get_option('lmb_enable_email_notifications', 1);
    }

    public static function send_email($to, $subject, $message) {
        if (!self::should_send_email()) {
            return false;
        }
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        return wp_mail($to, $subject, $message, $headers);
    }

    public static function notify_admin($subject, $message) {
        return self::send_email(get_option('admin_email'), $subject, $message);
    }
    
    public static function notify_ad_approved($user_id, $ad_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $ad_title = get_the_title($ad_id);
        $subject = sprintf(__('Your legal ad "%s" has been approved', 'lmb-core'), $ad_title);
        $message = sprintf(__('Hello %s,<br><br>Your legal ad "%s" has been approved and published. You can view it and download the PDF from your dashboard.<br><br>Regards,<br>The LMB Team', 'lmb-core'), $user->display_name, $ad_title);
        
        self::send_email($user->user_email, $subject, $message);
    }
    
    public static function notify_ad_denied($user_id, $ad_id, $reason) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $ad_title = get_the_title($ad_id);
        $subject = sprintf(__('Action required on your legal ad "%s"', 'lmb-core'), $ad_title);
        $message = sprintf(__('Hello %s,<br><br>Your legal ad "%s" has been denied. Reason: %s<br><br>Please review the ad in your dashboard.<br><br>Regards,<br>The LMB Team', 'lmb-core'), $user->display_name, $ad_title, esc_html($reason));
        
        self::send_email($user->user_email, $subject, $message);
    }
    
    public static function notify_payment_verified($user_id, $package_id, $points_added) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $package_title = get_the_title($package_id);
        $subject = __('Your account has been credited', 'lmb-core');
        $message = sprintf(__('Hello %s,<br><br>Your payment for the "%s" package has been verified. %d points have been added to your account.<br><br>Regards,<br>The LMB Team', 'lmb-core'), $user->display_name, $package_title, $points_added);
        
        self::send_email($user->user_email, $subject, $message);
    }
    
    // Add notification to user's notification list
    public static function add_user_notification($user_id, $title, $message, $type = 'info', $icon = 'fas fa-info-circle') {
        $notifications = get_user_meta($user_id, 'lmb_notifications', true);
        if (!is_array($notifications)) {
            $notifications = [];
        }
        
        $notification = [
            'id' => time() . rand(100, 999),
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'type' => $type,
            'time' => time(),
            'time_ago' => 'Just now',
            'read' => false
        ];
        
        array_unshift($notifications, $notification);
        
        // Keep only last 50 notifications
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, 0, 50);
        }
        
        update_user_meta($user_id, 'lmb_notifications', $notifications);
    }
    
    // Enhanced notification methods that also add to notification list
    public static function notify_ad_approved($user_id, $ad_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $ad_title = get_the_title($ad_id);
        $subject = sprintf(__('Your legal ad "%s" has been approved', 'lmb-core'), $ad_title);
        $message = sprintf(__('Your legal ad "%s" has been approved and published. You can view it and download the PDF from your dashboard.', 'lmb-core'), $ad_title);
        
        // Add to notification list
        self::add_user_notification($user_id, __('Ad Approved', 'lmb-core'), $message, 'success', 'fas fa-check-circle');
        
        // Send email
        $email_message = sprintf(__('Hello %s,<br><br>%s<br><br>Regards,<br>The LMB Team', 'lmb-core'), $user->display_name, $message);
        self::send_email($user->user_email, $subject, $email_message);
    }
    
    public static function notify_ad_denied($user_id, $ad_id, $reason) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $ad_title = get_the_title($ad_id);
        $subject = sprintf(__('Action required on your legal ad "%s"', 'lmb-core'), $ad_title);
        $message = sprintf(__('Your legal ad "%s" has been denied. Reason: %s', 'lmb-core'), $ad_title, $reason);
        
        // Add to notification list
        self::add_user_notification($user_id, __('Ad Denied', 'lmb-core'), $message, 'error', 'fas fa-times-circle');
        
        // Send email
        $email_message = sprintf(__('Hello %s,<br><br>%s<br><br>Please review the ad in your dashboard.<br><br>Regards,<br>The LMB Team', 'lmb-core'), $user->display_name, $message);
        self::send_email($user->user_email, $subject, $email_message);
    }
    
    public static function notify_payment_verified($user_id, $package_id, $points_added) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $package_title = get_the_title($package_id);
        $subject = __('Your account has been credited', 'lmb-core');
        $message = sprintf(__('Your payment for the "%s" package has been verified. %d points have been added to your account.', 'lmb-core'), $package_title, $points_added);
        
        // Add to notification list
        self::add_user_notification($user_id, __('Payment Verified', 'lmb-core'), $message, 'success', 'fas fa-credit-card');
        
        // Send email
        $email_message = sprintf(__('Hello %s,<br><br>%s<br><br>Regards,<br>The LMB Team', 'lmb-core'), $user->display_name, $message);
        self::send_email($user->user_email, $subject, $email_message);
    }
}