<?php
// FILE: elementor/widgets/class-lmb-admin-subscribe-user-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

if (!class_exists('LMB_Admin_Subscribe_User_Widget')) {
    class LMB_Admin_Subscribe_User_Widget extends Widget_Base {

        public function get_name() {
            return 'lmb_admin_subscribe_user';
        }

        public function get_title() {
            return __('Souscrire un Utilisateur à un Package', 'lmb-core');
        }

        public function get_icon() {
            return 'eicon-user-circle-o';
        }

        public function get_categories() {
            return ['lmb-admin-widgets-v2'];
        }

        public function get_script_depends() {
            // We will create this JS file in a later step
            return ['lmb-admin-subscribe-user'];
        }

        protected function render() {
            // This assumes the user ID is in the URL query var, e.g., /user-editor/?user_id=123
            $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

            if (!$user_id || !get_user_by('ID', $user_id)) {
                echo '<p>ID utilisateur non valide ou non trouvé.</p>';
                return;
            }
            
            $user_info = get_userdata($user_id);
            ?>
            <div class="lmb-admin-subscribe-user-widget">
                <div class="lmb-widget-header">
                    <h3><i class="fas fa-gift"></i> Offrir un Package à <?php echo esc_html($user_info->display_name); ?></h3>
                </div>
                <form id="lmb-admin-subscribe-form" class="lmb-editor-form">
                    <input type="hidden" id="lmb_user_id" value="<?php echo esc_attr($user_id); ?>">
                    
                    <div class="lmb-form-group">
                        <label for="lmb-package-select">Sélectionner un Package</label>
                        <select id="lmb-package-select" class="lmb-input" required>
                            <option value="">-- Choisir un package --</option>
                            <?php
                            $packages_query = new WP_Query([
                                'post_type' => 'lmb_package',
                                'posts_per_page' => -1,
                                'orderby' => 'title',
                                'order' => 'ASC'
                            ]);
                            if ($packages_query->have_posts()) {
                                while ($packages_query->have_posts()) {
                                    $packages_query->the_post();
                                    $price = get_post_meta(get_the_ID(), 'price', true);
                                    $points = get_post_meta(get_the_ID(), 'points', true);
                                    $title = get_the_title() . ' (' . esc_html($points) . ' pts / ' . esc_html($price) . ' MAD)';
                                    echo '<option value="' . get_the_ID() . '">' . esc_html($title) . '</option>';
                                }
                            }
                            wp_reset_postdata();
                            ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="lmb-btn lmb-btn-primary"><i class="fas fa-check"></i> Souscrire l'Utilisateur</button>
                </form>
            </div>
            <?php
        }
    }
}