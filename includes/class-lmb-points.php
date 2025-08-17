<?php
if (!defined('ABSPATH')) exit;

class LMB_Points {
    const META_BALANCE = '_lmb_points_balance';
    const META_COST_PER_AD = '_lmb_cost_per_ad';

    public static function get_balance($user_id) {
        return (int) get_user_meta($user_id, self::META_BALANCE, true);
    }
    public static function set_balance($user_id, $points) {
        update_user_meta($user_id, self::META_BALANCE, (int)$points);
    }
    public static function add($user_id, $points) {
        self::set_balance($user_id, self::get_balance($user_id) + (int)$points);
    }
    public static function deduct($user_id, $points) {
        $bal = self::get_balance($user_id);
        if ($bal < $points) return false;
        self::set_balance($user_id, $bal - (int)$points);
        return true;
    }
    public static function set_cost_per_ad($user_id, $points) {
        update_user_meta($user_id, self::META_COST_PER_AD, (int)$points);
    }
    public static function get_cost_per_ad($user_id) {
        $v = get_user_meta($user_id, self::META_COST_PER_AD, true);
        return $v === '' ? 0 : (int)$v;
    }
}
