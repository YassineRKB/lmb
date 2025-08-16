<?php
if (!defined('ABSPATH')) { exit; }

class LMB_Notification_Manager {
    public static function admin_email() {
        return get_option('admin_email');
    }

    public static function send_email($to, $subject, $message) {
        wp_mail($to, $subject, $message);
    }

    public static function notify_admin($subject, $message) {
        self::send_email(self::admin_email(), $subject, $message);
    }
}
