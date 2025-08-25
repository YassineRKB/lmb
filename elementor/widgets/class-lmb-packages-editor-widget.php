<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Packages_Editor_Widget extends Widget_Base {
    public function get_name() { return 'lmb_packages_editor'; }
    public function get_title() { return __('LMB Packages Editor', 'lmb-core'); }
    public function get_icon() { return 'eicon-products'; }
    public function get_categories() { return ['lmb-cw-admin']; }

    public function get_script_depends() {
        return ['lmb-packages-editor'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied. Administrator privileges required.', 'lmb-core') . '</p></div>';
            return;
        }

        $packages = get_posts([
            'post_type' => 'lmb_package',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);
        ?>
        <div class="lmb-packages-editor-widget lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-box-open"></i> <?php esc_html_e('Packages Management', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <div class="lmb-add-package-section">
                    <h4><?php esc_html_e('Add New Package', 'lmb-core'); ?></h4>
                    <form id="lmb-package-form" class="lmb-package-form">
                        <input type="hidden" id="package-id" value="">
                        <div class="lmb-form-row">
                            <div class="lmb-form-group">
                                <label for="package-name"><?php esc_html_e('Package Name', 'lmb-core'); ?></label>
                                <input type="text" id="package-name" class="lmb-input" required>
                            </div>
                            <div class="lmb-form-group">
                                <label for="package-price"><?php esc_html_e('Price (MAD)', 'lmb-core'); ?></label>
                                <input type="number" id="package-price" class="lmb-input" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="lmb-form-row">
                            <div class="lmb-form-group">
                                <label for="package-points"><?php esc_html_e('Points Awarded', 'lmb-core'); ?></label>
                                <input type="number" id="package-points" class="lmb-input" min="0" step="1" required>
                            </div>
                            <div class="lmb-form-group">
                                <label for="package-cost-per-ad"><?php esc_html_e('Cost Per Ad (Points)', 'lmb-core'); ?></label>
                                <input type="number" id="package-cost-per-ad" class="lmb-input" min="1" step="1" required>
                            </div>
                        </div>
                        <div class="lmb-form-group">
                            <label for="package-description"><?php esc_html_e('Description', 'lmb-core'); ?></label>
                            <textarea id="package-description" class="lmb-textarea" rows="3"></textarea>
                        </div>
                        <div class="lmb-form-actions">
                            <button type="submit" id="lmb-save-package-btn" class="lmb-btn lmb-btn-success">
                                <i class="fas fa-save"></i> <?php esc_html_e('Save Package', 'lmb-core'); ?>
                            </button>
                            <button type="button" id="lmb-cancel-edit-btn" class="lmb-btn lmb-btn-secondary" style="display: none;">
                                <i class="fas fa-times"></i> <?php esc_html_e('Cancel', 'lmb-core'); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="lmb-packages-list-section">
                    <h4><?php esc_html_e('Existing Packages', 'lmb-core'); ?></h4>
                    <div class="lmb-packages-grid" id="lmb-packages-grid">
                        <?php if (!empty($packages)): ?>
                            <?php foreach ($packages as $package): ?>
                                <?php $this->render_package_card($package); ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="lmb-no-packages">
                                <p><?php esc_html_e('No packages found. Create your first package above.', 'lmb-core'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_package_card($package) {
        $price = get_post_meta($package->ID, 'price', true);
        $points = get_post_meta($package->ID, 'points', true);
        $cost_per_ad = get_post_meta($package->ID, 'cost_per_ad', true);
        ?>
        <div class="lmb-package-card" 
             data-price="<?php echo esc_attr($price); ?>"
             data-points="<?php echo esc_attr($points); ?>"
             data-cost-per-ad="<?php echo esc_attr($cost_per_ad); ?>"
             data-description="<?php echo esc_attr($package->post_content); ?>">
            
            <div class="lmb-package-header">
                <h5 class="lmb-package-title"><?php echo esc_html($package->post_title); ?></h5>
                <div class="lmb-package-price"><?php echo esc_html($price); ?> MAD</div>
            </div>
            <div class="lmb-package-details">
                <div class="lmb-package-detail">
                    <span><?php esc_html_e('Points:', 'lmb-core'); ?></span>
                    <strong><?php echo esc_html($points); ?></strong>
                </div>
                <div class="lmb-package-detail">
                    <span><?php esc_html_e('Cost per Ad:', 'lmb-core'); ?></span>
                    <strong><?php echo esc_html($cost_per_ad); ?> pts</strong>
                </div>
            </div>
            <?php if ($package->post_content): ?>
                <div class="lmb-package-description"><?php echo esc_html(wp_trim_words($package->post_content, 20)); ?></div>
            <?php endif; ?>
            <div class="lmb-package-actions">
                <button class="lmb-btn lmb-btn-sm lmb-btn-primary lmb-edit-package" data-package-id="<?php echo esc_attr($package->ID); ?>">
                    <i class="fas fa-edit"></i> <?php esc_html_e('Edit', 'lmb-core'); ?>
                </button>
                <button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-delete-package" data-package-id="<?php echo esc_attr($package->ID); ?>">
                    <i class="fas fa-trash"></i> <?php esc_html_e('Delete', 'lmb-core'); ?>
                </button>
            </div>
        </div>
        <?php
    }
}