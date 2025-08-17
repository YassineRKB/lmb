<?php
if (!defined('ABSPATH')) { exit; }

class LMB_Points {
    public static function get_user_points($user_id) {
        return (int) get_user_meta($user_id, 'lmb_points', true);
    }

    public static function add_points($user_id, $amount, $note='') {
        $current = self::get_user_points($user_id);
        $new = $current + (int)$amount;
        update_user_meta($user_id, 'lmb_points', $new);
        self::log($user_id, "+$amount points. $note");
        return $new;
    }

    public static function deduct_points($user_id, $amount, $note='') {
        $current = self::get_user_points($user_id);
        $amount = (int)$amount;
        if ($current < $amount) return new WP_Error('insufficient_points', __('Not enough points','lmb-core'));
        $new = $current - $amount;
        update_user_meta($user_id, 'lmb_points', $new);
        self::log($user_id, "-$amount points. $note");
        return $new;
    }

    public static function get_user_ad_cost($user_id) {
        $v = get_user_meta($user_id, 'lmb_ad_cost_per_ad', true);
        if ($v === '' || $v === null) $v = get_option('lmb_default_ad_cost', 0);
        return (int)$v;
    }

    public static function set_user_package($user_id, $points, $ad_cost) {
        update_user_meta($user_id, 'lmb_points', (int)$points + self::get_user_points($user_id));
        update_user_meta($user_id, 'lmb_ad_cost_per_ad', (int)$ad_cost);
        self::log($user_id, sprintf(__('Package applied: +%d points, ad cost %d','lmb-core'), $points, $ad_cost));
    }

    private static function log($user_id, $message) {
        $history = (array) get_user_meta($user_id, 'lmb_points_history', true);
        $history[] = [ 'time' => current_time('mysql'), 'message' => $message ];
        update_user_meta($user_id, 'lmb_points_history', $history);
    }
}