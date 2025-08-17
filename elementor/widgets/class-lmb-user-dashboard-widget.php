<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

// CORRECTED CLASS NAME: This class must have a unique name.
class LMB_User_Dashboard_Widget extends Widget_Base {
    public function get_name() { 
        return 'lmb_user_dashboard'; 
    }

    public function get_title() { 
        return __('LMB User Dashboard Container','lmb-core'); 
    }

    public function get_icon() { 
        return 'eicon-user-circle-o'; 
    }

    public function get_categories() { 
        return ['lmb-widgets']; 
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>'.esc_html__('You must be logged in to view your dashboard.', 'lmb-core').'</p>';
            return;
        }

        // This widget now acts as a container for your other dashboard components.
        // You can place the [lmb_user_stats] and [lmb_user_ads_list] shortcodes
        // directly onto your Elementor page for more layout flexibility.
        echo '<div class="lmb-user-dashboard-widget">';
        
        echo '<h2>'.__('My Dashboard', 'lmb-core').'</h2>';

        // Instructions for the user
        echo '<p>'.__('Please add the "LMB User Stats" and "LMB User Ads List" widgets or shortcodes to this page to build your dashboard.', 'lmb-core').'</p>';

        echo '</div>';
    }
}