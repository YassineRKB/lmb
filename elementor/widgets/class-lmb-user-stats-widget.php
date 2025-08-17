<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_stats'; }
    public function get_title(){ return __('LMB User Stats','lmb-core'); }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        // This widget simply renders the content from the main shortcode
        echo do_shortcode('[lmb_user_stats]');
    }
}