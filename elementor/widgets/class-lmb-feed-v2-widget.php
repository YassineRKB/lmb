<?php
// FILE: elementor/widgets/class-lmb-feed-v2-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

if (!class_exists('LMB_Feed_V2_Widget')) {
    class LMB_Feed_V2_Widget extends Widget_Base {
        public function get_name() {
            return 'lmb_feed_v2';
        }

        public function get_title() {
            return __('Flux d\'Activité V2', 'lmb-core');
        }

        public function get_icon() {
            return 'eicon-history';
        }

        public function get_categories() {
            // Available for both admins and users
            return ['lmb-admin-widgets-v2', 'lmb-user-widgets-v2'];
        }

        public function get_script_depends() {
            return ['lmb-feed-v2'];
        }

        public function get_style_depends() {
            // We'll add the new feed styles to the existing V2 user CSS file
            return ['lmb-user-widgets-v2'];
        }

        protected function _register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Feed Settings', 'lmb-core'),
                    'tab' => Controls_Manager::TAB_CONTENT,
                ]
            );

            $this->add_control(
                'items_to_show',
                [
                    'label' => __('Number of Items to Show', 'lmb-core'),
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
            $is_admin = current_user_can('manage_options');
            
            $this->add_render_attribute('wrapper', [
                'class' => 'lmb-user-widget-v2 lmb-feed-v2-widget',
                'data-limit' => $settings['items_to_show'],
            ]);
            ?>
            <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
                <div class="lmb-widget-header">
                    <?php if ($is_admin): ?>
                        <h3><i class="fas fa-stream"></i> Flux d'Activité Global</h3>
                    <?php else: ?>
                        <h3><i class="fas fa-history"></i> Votre Activité Récente</h3>
                    <?php endif; ?>
                </div>
                <div class="lmb-widget-content">
                    <div class="feed-list">
                        <!-- Les éléments du flux seront chargés ici par JavaScript -->
                        <div class="feed-item-placeholder" style="text-align: center; padding: 20px;">
                            <i class="fas fa-spinner fa-spin"></i> Chargement du Flux...
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}
