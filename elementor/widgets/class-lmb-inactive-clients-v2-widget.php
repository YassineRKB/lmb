<?php
// FILE: elementor/widgets/class-lmb-inactive-clients-v2-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Inactive_Clients_V2_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_inactive_clients_v2';
    }

    public function get_title() {
        return __('Clients Management: Inactive V2', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-user-lock';
    }

    public function get_categories() {
        return ['lmb-admin-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-inactive-clients-v2'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets-v2'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content Settings', 'lmb-core'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'clients_per_page',
            [
                'label' => __('Clients Per Page', 'lmb-core'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 5,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $this->add_render_attribute('wrapper', [
            'class' => 'lmb-admin-widget lmb-inactive-clients-v2',
            'data-per-page' => $settings['clients_per_page'],
        ]);
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <div class="lmb-widget-header">
                <h3><i class="fas fa-user-clock"></i> Inactive Clients Management</h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-box">
                    <div class="lmb-filter-grid">
                        <input type="text" id="lmb-inactive-client-search" placeholder="Search by name, company, or email..." class="lmb-filter-input">
                        <button type="reset" id="lmb-inactive-client-reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>

                <div class="lmb-inactive-clients-list">
                    <!-- Client cards will be loaded here by JavaScript -->
                </div>

                <div class="lmb-pagination-container" style="margin-top: 20px;">
                    <!-- Pagination will be loaded here by JavaScript -->
                </div>
            </div>
        </div>
        <?php
    }
}