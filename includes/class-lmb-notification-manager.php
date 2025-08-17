<?php
if (!defined('ABSPATH')) { exit; }

class LMB_Notification_Manager {
    
    public static function init() {
        // Hook into points changes
        add_action('lmb_points_changed', [__CLASS__, 'notify_points_change'], 10, 4);
    }
    
    public static function admin_email() {
        return get_option('admin_email');
    }

    public static function send_email($to, $subject, $message) {
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        return wp_mail($to, $subject, $message, $headers);
    }

    public static function notify_admin($subject, $message) {
        return self::send_email(self::admin_email(), $subject, $message);
    }
    
    /**
     * Notify user when their ad is approved
     */
    public static function notify_ad_approved($user_id, $ad_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $ad_title = get_the_title($ad_id);
        $subject = sprintf(__('Your legal ad "%s" has been approved', 'lmb-core'), $ad_title);
        
        $message = sprintf(
            __('Hello %s,<br><br>Your legal ad "%s" has been approved and published.<br><br>You can download the PDF and invoice from your dashboard.<br><br>Best regards,<br>LMB Team', 'lmb-core'),
            $user->display_name,
            $ad_title
        );
        
        self::send_email($user->user_email, $subject, $message);
        
        // Log activity
        LMB_Ad_Manager::log_activity(sprintf('Approval notification sent to %s for ad #%d', $user->display_name, $ad_id));
    }
    
    /**
     * Notify user when their ad is denied
     */
    public static function notify_ad_denied($user_id, $ad_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $ad_title = get_the_title($ad_id);
        $subject = sprintf(__('Your legal ad "%s" has been denied', 'lmb-core'), $ad_title);
        
        $message = sprintf(
            __('Hello %s,<br><br>Unfortunately, your legal ad "%s" has been denied.<br><br>Please contact us for more information or to resubmit with corrections.<br><br>Best regards,<br>LMB Team', 'lmb-core'),
            $user->display_name,
            $ad_title
        );
        
        self::send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Notify user when their payment is verified
     */
    public static function notify_payment_verified($user_id, $package_id, $points_added) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $package_title = get_the_title($package_id);
        $subject = __('Payment verified - Points added to your account', 'lmb-core');
        
        $message = sprintf(
            __('Hello %s,<br><br>Your payment for package "%s" has been verified.<br><br>%d points have been added to your account.<br><br>You can now submit legal ads from your dashboard.<br><br>Best regards,<br>LMB Team', 'lmb-core'),
            $user->display_name,
            $package_title,
            $points_added
        );
        
        self::send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Notify user when points balance changes
     */
    public static function notify_points_change($user_id, $new_balance, $amount_changed, $reason) {
        // Only notify for significant changes (not small deductions)
        if (abs($amount_changed) < 10) return;
        
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = __('Your points balance has been updated', 'lmb-core');
        
        $change_text = $amount_changed > 0 
            ? sprintf(__('%d points added', 'lmb-core'), $amount_changed)
            : sprintf(__('%d points deducted', 'lmb-core'), abs($amount_changed));
            
        $message = sprintf(
            __('Hello %s,<br><br>%s<br>Reason: %s<br><br>Your new balance is: %d points<br><br>Best regards,<br>LMB Team', 'lmb-core'),
            $user->display_name,
            $change_text,
            $reason,
            $new_balance
        );
        
        self::send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Notify admin of new user registration
     */
    public static function notify_new_user($user_id) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = sprintf(__('New user registration: %s', 'lmb-core'), $user->display_name);
        
        $message = sprintf(
            __('A new user has registered:<br><br>Name: %s<br>Email: %s<br>Registration Date: %s<br><br>View user: %s', 'lmb-core'),
            $user->display_name,
            $user->user_email,
            $user->user_registered,
            admin_url('user-edit.php?user_id=' . $user_id)
        );
        
        self::notify_admin($subject, $message);
    }
    
    /**
     * Notify user when balance is updated by admin
     */
    public static function notify_user_balance_update($user_id, $new_balance) {
        $user = get_userdata($user_id);
        if (!$user) return;
        
        $subject = __('Your account balance has been updated', 'lmb-core');
        
        $message = sprintf(
            __('Hello %s,<br><br>Your points balance has been updated by an administrator.<br><br>Your new balance is: %d points<br><br>Best regards,<br>LMB Team', 'lmb-core'),
            $user->display_name,
            $new_balance
        );
        
        self::send_email($user->user_email, $subject, $message);
    }
}
