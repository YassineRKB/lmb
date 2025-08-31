<?php
// FILE: elementor/widgets/class-lmb-active-clients-v2-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Active_Clients_V2_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_active_clients_v2';
    }

    public function get_title() {
        return __('Clients Management: Active V2', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-users';
    }

    public function get_categories() {
        return ['lmb-admin-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-active-clients-v2'];
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
                'default' => 10,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        
        $this->add_render_attribute('wrapper', [
            'class' => 'lmb-admin-widget lmb-active-clients-v2',
            'data-per-page' => $settings['clients_per_page'],
        ]);
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <div class="lmb-widget-header">
                <h3><i class="fas fa-users"></i> Active Clients Management</h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-box">
                    <form id="lmb-active-clients-filters">
                        <div class="lmb-filter-grid">
                            <input type="text" name="filter_id" placeholder="Filter by ID..." class="lmb-filter-input">
                            <input type="text" name="filter_name" placeholder="Filter by Name/Company..." class="lmb-filter-input">
                            <input type="text" name="filter_city" placeholder="Filter by City..." class="lmb-filter-input">
                            <select name="filter_type" class="lmb-filter-select">
                                <option value="">All Types</option>
                                <option value="regular">Regular</option>
                                <option value="professional">Professional</option>
                                <option value="administrator">Admin</option>
                            </select>
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </form>
                </div>

                <div class="lmb-table-container">
                    <table class="lmb-data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>City</th>
                                <th>Type</th>
                                <th>Published Ads</th>
                                <th>Balance (PTS)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- User rows will be loaded here by JavaScript -->
                        </tbody>
                    </table>
                </div>
                 <div class="lmb-pagination-container" style="margin-top: 20px;">
                    <!-- Pagination will be loaded here by JavaScript -->
                </div>
            </div>
        </div>
        <?php
    }
}