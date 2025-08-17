<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

// The class name MUST be unique. This is the fix.
class LMB_User_Dashboard_Widget extends Widget_Base {
    public function get_name() { 
        return 'lmb_user_dashboard'; 
    }

    public function get_title() { 
        return __('LMB User Dashboard Main','lmb-core'); 
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

        // This widget now acts as a container for the other components
        echo '<div class="lmb-user-dashboard-widget">';
        
        // Display the new, redesigned user stats widget
        echo do_shortcode('[lmb_user_stats]');

        // Display the list of the user's recent ads
        echo do_shortcode('[lmb_user_ads_list]');

        echo '</div>';
    }
}