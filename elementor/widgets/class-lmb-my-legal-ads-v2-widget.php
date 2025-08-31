<?php
// FILE: elementor/widgets/class-lmb-my-legal-ads-v2-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_My_Legal_Ads_V2_Widget extends Widget_Base {
    public function get_name() {
        return 'lmb_my_legal_ads_v2';
    }

    public function get_title() {
        return __('My Legal Ads V2', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return ['lmb-user-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-my-legal-ads-v2'];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets-v2'];
    }

    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'lmb-core'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_status',
            [
                'label' => __('Default Status Tab', 'lmb-core'),
                'type' => Controls_Manager::SELECT,
                'default' => 'published',
                'options' => [
                    'published' => __('Published', 'lmb-core'),
                    'pending'   => __('Pending', 'lmb-core'),
                    'drafts'    => __('Drafts', 'lmb-core'),
                    'denied'    => __('Denied', 'lmb-core'),
                ],
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Ads Per Page', 'lmb-core'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 10,
            ]
        );

        $this->add_control(
			'view_more_link',
			[
				'label' => __( 'View More Link', 'lmb-core' ),
				'type' => \Elementor\Controls_Manager::URL,
				'placeholder' => __( 'https://your-link.com', 'lmb-core' ),
				'show_external' => true,
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
				],
			]
		);


        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $default_status = $settings['default_status'];
        $view_more_url = $settings['view_more_link']['url'];
        ?>
        <div class="lmb-my-legal-ads-v2 lmb-user-widget-v2">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-list-alt"></i> My Legal Ads</h3>
            </div>
            <div class="lmb-widget-content">
                
                <div class="lmb-tabs-nav-user">
                    <button class="lmb-tab-btn-user <?php echo $default_status === 'published' ? 'active' : ''; ?>" data-target="#tab-published">Published</button>
                    <button class="lmb-tab-btn-user <?php echo $default_status === 'pending' ? 'active' : ''; ?>" data-target="#tab-pending">Pending</button>
                    <button class="lmb-tab-btn-user <?php echo $default_status === 'drafts' ? 'active' : ''; ?>" data-target="#tab-drafts">Drafts</button>
                    <button class="lmb-tab-btn-user <?php echo $default_status === 'denied' ? 'active' : ''; ?>" data-target="#tab-denied">Denied</button>
                </div>

                <div class="lmb-tabs-content">
                    <!-- Published Ads Table -->
                    <div id="tab-published" class="lmb-tab-pane <?php echo $default_status === 'published' ? 'active' : ''; ?>">
                        <div class="lmb-filters-box">
                            <form>
                                <div class="lmb-filter-grid lmb-filter-grid-user">
                                    <input type="text" placeholder="Ref (ID)" class="lmb-filter-input">
                                    <input type="text" placeholder="Company" class="lmb-filter-input">
                                    <input type="text" placeholder="Type" class="lmb-filter-input">
                                    <input type="date" class="lmb-filter-input">
                                    <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                                </div>
                            </form>
                        </div>
                        <div class="lmb-table-container">
                            <table class="lmb-data-table lmb-my-ads-table-v2">
                                <thead>
                                    <tr>
                                        <th>ID (Ref)</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Approved By</th>
                                        <th>Accuse</th>
                                        <th>Journal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- AJAX Content for Published Ads -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Ads Table -->
                    <div id="tab-pending" class="lmb-tab-pane <?php echo $default_status === 'pending' ? 'active' : ''; ?>">
                        <div class="lmb-filters-box">
                             <form>
                                <div class="lmb-filter-grid lmb-filter-grid-user">
                                    <input type="text" placeholder="Ref (ID)" class="lmb-filter-input">
                                    <input type="text" placeholder="Company" class="lmb-filter-input">
                                    <input type="text" placeholder="Type" class="lmb-filter-input">
                                    <input type="date" class="lmb-filter-input">
                                    <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                                </div>
                            </form>
                        </div>
                         <div class="lmb-table-container">
                            <table class="lmb-data-table lmb-my-ads-table-v2">
                                <thead>
                                    <tr>
                                        <th>ID (Ref)</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Date Submitted</th>
                                    </tr>
                                </thead>
                                <tbody>
                                   <!-- AJAX Content for Pending Ads -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Drafts Table -->
                    <div id="tab-drafts" class="lmb-tab-pane <?php echo $default_status === 'drafts' ? 'active' : ''; ?>">
                        <div class="lmb-filters-box">
                             <form>
                                <div class="lmb-filter-grid lmb-filter-grid-user">
                                    <input type="text" placeholder="Ref (ID)" class="lmb-filter-input">
                                    <input type="text" placeholder="Company" class="lmb-filter-input">
                                    <input type="text" placeholder="Type" class="lmb-filter-input">
                                    <input type="date" class="lmb-filter-input">
                                    <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                                </div>
                            </form>
                        </div>
                        <div class="lmb-table-container">
                            <table class="lmb-data-table lmb-my-ads-table-v2">
                                <thead>
                                    <tr>
                                        <th>ID (Ref)</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Date Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- AJAX Content for Drafts -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Denied Ads Table -->
                    <div id="tab-denied" class="lmb-tab-pane <?php echo $default_status === 'denied' ? 'active' : ''; ?>">
                        <div class="lmb-filters-box">
                             <form>
                                <div class="lmb-filter-grid lmb-filter-grid-user">
                                    <input type="text" placeholder="Ref (ID)" class="lmb-filter-input">
                                    <input type="text" placeholder="Company" class="lmb-filter-input">
                                    <input type="text" placeholder="Type" class="lmb-filter-input">
                                    <input type="date" class="lmb-filter-input">
                                    <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                                </div>
                            </form>
                        </div>
                         <div class="lmb-table-container">
                            <table class="lmb-data-table lmb-my-ads-table-v2">
                                <thead>
                                    <tr>
                                        <th>ID (Ref)</th>
                                        <th>Company</th>
                                        <th>Type</th>
                                        <th>Date Denied</th>
                                        <th>Reason</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- AJAX Content for Denied Ads -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- /.lmb-tabs-content -->
                
                <div class="lmb-pagination-container">
                    <!-- Pagination will be loaded here by JS -->
                </div>

                <?php if (!empty($view_more_url)): ?>
                <div class="lmb-view-more-container" style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo esc_url($view_more_url); ?>" class="lmb-btn lmb-btn-view" <?php if($settings['view_more_link']['is_external']) { echo 'target="_blank"'; } ?>>
                        View All
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

