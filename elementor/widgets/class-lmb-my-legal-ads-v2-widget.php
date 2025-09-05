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
        return __('Mes Annonces Légales V2', 'lmb-core');
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
        return ['lmb-my-legal-ads-v2'];
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
                'label' => __('Status to Display', 'lmb-core'),
                'type' => Controls_Manager::SELECT,
                'default' => 'published',
                'options' => [
                    'published' => __('Publié', 'lmb-core'),
                    'pending'   => __('En Attente', 'lmb-core'),
                    'drafts'    => __('Brouillons', 'lmb-core'),
                    'denied'    => __('Refusé', 'lmb-core'),
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
        $status_to_display = $settings['default_status'];
        $view_more_url = $settings['view_more_link']['url'];

        // Pass settings to JavaScript via data attributes
        $this->add_render_attribute('wrapper', [
            'class' => 'lmb-my-legal-ads-v2',
            'data-status' => $status_to_display,
            'data-posts-per-page' => $settings['posts_per_page'],
        ]);
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <div class="lmb-widget-header">
                <h3><i class="fas fa-list-alt"></i> Mes Annonces Légales: <?php echo esc_html(ucfirst($status_to_display)); ?></h3>
            </div>
            <div class="lmb-widget-content">
                
                <div class="lmb-filters-box">
                    <form>
                        <div class="lmb-filter-grid lmb-filter-grid-user">
                            <input type="text" placeholder="Réf (ID)" class="lmb-filter-input" name="filter_ref">
                            <input type="text" placeholder="Société" class="lmb-filter-input" name="filter_company">
                            <input type="text" placeholder="Type" class="lmb-filter-input" name="filter_type">
                            <input type="date" class="lmb-filter-input" name="filter_date">
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Réinitialiser</button>
                        </div>
                    </form>
                </div>

                <div class="lmb-table-container">
                    <table class="lmb-data-table lmb-my-ads-table-v2">
                        <thead>
                            <tr>
                                <?php
                                // Render table headers based on the selected status
                                switch ($status_to_display) {
                                    case 'published':
                                        echo '<th>ID (Réf)</th><th>Société</th><th>Type</th><th>Date</th><th>Approuvé Par</th><th>Accusé</th><th>Journal</th>';
                                        break;
                                    case 'pending':
                                        echo '<th>ID (Réf)</th><th>Société</th><th>Type</th><th>Date de Soumission</th>';
                                        break;
                                    case 'drafts':
                                        echo '<th>ID (Réf)</th><th>Société</th><th>Type</th><th>Date de Création</th><th>Actions</th>';
                                        break;
                                    case 'denied':
                                        echo '<th>ID (Réf)</th><th>Société</th><th>Type</th><th>Date de Refus</th><th>Raison</th><th>Actions</th>';
                                        break;
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
                
                <div class="lmb-pagination-container">
                    </div>

                <?php if (!empty($view_more_url)): ?>
                <div class="lmb-view-more-container" style="text-align: center; margin-top: 20px;">
                    <a href="<?php echo esc_url($view_more_url); ?>" class="lmb-btn lmb-btn-view" <?php if($settings['view_more_link']['is_external']) { echo 'target="_blank"'; } ?>>
                        Voir Tout
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}