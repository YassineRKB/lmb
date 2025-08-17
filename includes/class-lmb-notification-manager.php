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
}