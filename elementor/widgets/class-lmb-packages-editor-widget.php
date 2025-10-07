<?php
// FILE: elementor/widgets/class-lmb-packages-editor-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

if (!class_exists('LMB_Packages_Editor_Widget')) {
    class LMB_Packages_Editor_Widget extends Widget_Base {
        public function get_name() { return 'lmb_packages_editor'; }
        public function get_title() { return __('Éditeur de Packages LMB', 'lmb-core'); }
        public function get_icon() { return 'eicon-price-list'; }
        public function get_categories() { return ['lmb-admin-widgets-v2']; }

        public function get_script_depends() { return ['lmb-packages-editor']; }
        public function get_style_depends() { return ['lmb-packages-editor']; }

        protected function render() {
            ?>
            <div class="lmb-packages-editor">
                <div class="lmb-editor-form-container">
                    <div class="lmb-widget-header">
                        <h3><i class="fas fa-edit"></i> Ajouter / Modifier Package</h3>
                    </div>
                    <form id="lmb-package-form" class="lmb-editor-form">
                        <input type="hidden" name="package_id" id="package_id">
                        <div class="lmb-form-group">
                            <label for="package_name">Nom du Package</label>
                            <input type="text" id="package_name" name="name" class="lmb-input" required>
                        </div>
                        <div class="lmb-form-group">
                            <label for="package_price">Prix (MAD)</label>
                            <input type="number" id="package_price" name="price" class="lmb-input" step="0.01" required>
                        </div>
                        <div class="lmb-form-group">
                            <label for="package_points">Points Attribués</label>
                            <input type="number" id="package_points" name="points" class="lmb-input" required>
                        </div>
                        <div class="lmb-form-group">
                            <label for="package_cost">Coût Par Annonce (Points)</label>
                            <input type="number" id="package_cost" name="cost_per_ad" class="lmb-input" required>
                        </div>
                        <div class="lmb-form-group">
                            <label for="package_desc">Description</label>
                            <textarea id="package_desc" name="description" class="lmb-textarea"></textarea>
                        </div>
                        <div class="lmb-form-group">
                            <label for="package_client_visible">
                                <input type="checkbox" id="package_client_visible" name="client_visible" value="1" checked>
                                Visible pour les clients
                            </label>
                        </div>
                        <button type="submit" class="lmb-btn lmb-btn-primary"><i class="fas fa-save"></i> Enregistrer Package</button>
                        <button type="button" id="lmb-clear-form-btn" class="lmb-btn lmb-btn-secondary" style="margin-top:10px;"><i class="fas fa-undo"></i> Nouveau Package</button>
                    </form>
                </div>

                <div class="lmb-packages-list-container">
                    <div class="lmb-widget-header">
                        <h3><i class="fas fa-list-ul"></i> Packages Existants</h3>
                    </div>
                    <div id="lmb-packages-list" class="lmb-packages-list">
                        <?php
                        $packages_query = new WP_Query(['post_type' => 'lmb_package', 'posts_per_page' => -1]);
                        if ($packages_query->have_posts()) {
                            while ($packages_query->have_posts()) {
                                $packages_query->the_post();
                                $this->render_package_card(get_the_ID());
                            }
                        } else {
                            echo '<p>Aucun package trouvé. Ajoutez-en un en utilisant le formulaire.</p>';
                        }
                        wp_reset_postdata();
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }

        public static function render_package_card($package_id) {
            $package = get_post($package_id);
            if (!$package) return;

            $price = get_post_meta($package_id, 'price', true);
            $points = get_post_meta($package_id, 'points', true);
            $cost_per_ad = get_post_meta($package_id, 'cost_per_ad', true);
            $client_visible = get_post_meta($package_id, 'client_visible', true);
            $desc = wp_trim_words($package->post_content, 20);
            ?>
            <div class="lmb-package-card" data-package-id="<?php echo $package_id; ?>">
                <div class="lmb-package-card-header">
                    <div>
                        <h4 class="lmb-package-card-title"><?php echo esc_html($package->post_title); ?></h4>
                        <span class="lmb-package-card-visibility">
                            <?php if ($client_visible) : ?>
                                <i class="fas fa-eye"></i> Visible
                            <?php else : ?>
                                <i class="fas fa-eye-slash"></i> Caché
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="lmb-package-card-actions">
                        <button class="lmb-edit-package-btn lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-pencil-alt"></i></button>
                        <button class="lmb-delete-package-btn lmb-btn lmb-btn-sm lmb-btn-danger"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
                <div class="lmb-package-card-body">
                    <?php if ($desc): ?><p><?php echo esc_html($desc); ?></p><?php endif; ?>
                    <div class="lmb-package-card-details">
                        <span><strong>Prix:</strong> <?php echo esc_html($price); ?> MAD</span>
                        <span><strong>Points:</strong> <?php echo esc_html($points); ?></span>
                        <span><strong>Coût/Annonce:</strong> <?php echo esc_html($cost_per_ad); ?></span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}