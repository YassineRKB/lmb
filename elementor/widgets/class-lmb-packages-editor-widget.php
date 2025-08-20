<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Packages_Editor_Widget extends Widget_Base {
    public function get_name() { return 'lmb_packages_editor'; }
    public function get_title() { return __('LMB Packages Editor', 'lmb-core'); }
    public function get_icon() { return 'eicon-products'; }
    public function get_categories() { return ['lmb-2']; }

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        add_action('wp_ajax_lmb_save_package', [$this, 'ajax_save_package']);
        add_action('wp_ajax_lmb_delete_package', [$this, 'ajax_delete_package']);
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Access denied. Administrator privileges required.', 'lmb-core') . '</p></div>';
            return;
        }

        // Get all packages
        $packages = get_posts([
            'post_type' => 'lmb_package',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ]);

        wp_enqueue_script('jquery');
        ?>
        <div class="lmb-packages-editor-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-box-open"></i> <?php esc_html_e('Packages Management', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <!-- Add New Package Form -->
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

                <!-- Packages List -->
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

        <style>
        .lmb-packages-editor-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lmb-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .lmb-widget-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lmb-widget-content {
            padding: 20px;
        }
        .lmb-add-package-section,
        .lmb-packages-list-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-packages-list-section:last-child {
            border-bottom: none;
        }
        .lmb-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .lmb-form-group {
            margin-bottom: 15px;
        }
        .lmb-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #495057;
        }
        .lmb-input, .lmb-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .lmb-form-actions {
            text-align: center;
        }
        .lmb-packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .lmb-package-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        .lmb-package-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .lmb-package-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .lmb-package-title {
            font-size: 18px;
            font-weight: 600;
            color: #495057;
            margin: 0;
        }
        .lmb-package-price {
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        .lmb-package-details {
            margin-bottom: 15px;
        }
        .lmb-package-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }
        .lmb-package-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .lmb-package-actions {
            display: flex;
            gap: 10px;
        }
        .lmb-no-packages {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            grid-column: 1 / -1;
        }
        @media (max-width: 768px) {
            .lmb-form-row {
                grid-template-columns: 1fr;
            }
            .lmb-packages-grid {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            let editingPackageId = null;

            // Save package
            $('#lmb-package-form').on('submit', function(e) {
                e.preventDefault();
                
                const formData = {
                    action: 'lmb_save_package',
                    nonce: '<?php echo wp_create_nonce('lmb_packages_nonce'); ?>',
                    package_id: $('#package-id').val(),
                    name: $('#package-name').val(),
                    price: $('#package-price').val(),
                    points: $('#package-points').val(),
                    cost_per_ad: $('#package-cost-per-ad').val(),
                    description: $('#package-description').val()
                };

                $('#lmb-save-package-btn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php esc_js_e('Saving...', 'lmb-core'); ?>');

                $.post(ajaxurl, formData, function(response) {
                    if (response.success) {
                        location.reload(); // Reload to show updated packages
                    } else {
                        alert('<?php esc_js_e('Error:', 'lmb-core'); ?> ' + response.data.message);
                    }
                }).always(function() {
                    $('#lmb-save-package-btn').prop('disabled', false).html('<i class="fas fa-save"></i> <?php esc_js_e('Save Package', 'lmb-core'); ?>');
                });
            });

            // Edit package
            $(document).on('click', '.lmb-edit-package', function() {
                const packageId = $(this).data('package-id');
                const card = $(this).closest('.lmb-package-card');
                
                $('#package-id').val(packageId);
                $('#package-name').val(card.find('.lmb-package-title').text());
                $('#package-price').val(card.data('price'));
                $('#package-points').val(card.data('points'));
                $('#package-cost-per-ad').val(card.data('cost-per-ad'));
                $('#package-description').val(card.data('description'));
                
                $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> <?php esc_js_e('Update Package', 'lmb-core'); ?>');
                $('#lmb-cancel-edit-btn').show();
                
                editingPackageId = packageId;
                
                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('.lmb-add-package-section').offset().top - 20
                }, 500);
            });

            // Cancel edit
            $('#lmb-cancel-edit-btn').on('click', function() {
                $('#lmb-package-form')[0].reset();
                $('#package-id').val('');
                $('#lmb-save-package-btn').html('<i class="fas fa-save"></i> <?php esc_js_e('Save Package', 'lmb-core'); ?>');
                $(this).hide();
                editingPackageId = null;
            });

            // Delete package
            $(document).on('click', '.lmb-delete-package', function() {
                if (!confirm('<?php esc_js_e('Are you sure you want to delete this package? This action cannot be undone.', 'lmb-core'); ?>')) {
                    return;
                }

                const packageId = $(this).data('package-id');
                const button = $(this);
                
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.post(ajaxurl, {
                    action: 'lmb_delete_package',
                    nonce: '<?php echo wp_create_nonce('lmb_packages_nonce'); ?>',
                    package_id: packageId
                }, function(response) {
                    if (response.success) {
                        button.closest('.lmb-package-card').fadeOut(300, function() {
                            $(this).remove();
                            if ($('.lmb-package-card').length === 0) {
                                $('#lmb-packages-grid').html('<div class="lmb-no-packages"><p><?php esc_js_e('No packages found. Create your first package above.', 'lmb-core'); ?></p></div>');
                            }
                        });
                    } else {
                        alert('<?php esc_js_e('Error:', 'lmb-core'); ?> ' + response.data.message);
                        button.prop('disabled', false).html('<i class="fas fa-trash"></i> <?php esc_js_e('Delete', 'lmb-core'); ?>');
                    }
                });
            });
        });
        </script>
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
                <div class="lmb-package-description">
                    <?php echo esc_html(wp_trim_words($package->post_content, 20)); ?>
                </div>
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

    public function ajax_save_package() {
        check_ajax_referer('lmb_packages_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied']);
        }

        $package_id = intval($_POST['package_id']);
        $name = sanitize_text_field($_POST['name']);
        $price = floatval($_POST['price']);
        $points = intval($_POST['points']);
        $cost_per_ad = intval($_POST['cost_per_ad']);
        $description = sanitize_textarea_field($_POST['description']);

        if (!$name || !$price || !$points || !$cost_per_ad) {
            wp_send_json_error(['message' => __('All fields are required', 'lmb-core')]);
        }

        $post_data = [
            'post_title' => $name,
            'post_content' => $description,
            'post_type' => 'lmb_package',
            'post_status' => 'publish'
        ];

        if ($package_id) {
            // Update existing package
            $post_data['ID'] = $package_id;
            $result = wp_update_post($post_data);
        } else {
            // Create new package
            $result = wp_insert_post($post_data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        $package_id = $package_id ?: $result;

        // Update meta fields
        update_post_meta($package_id, 'price', $price);
        update_post_meta($package_id, 'points', $points);
        update_post_meta($package_id, 'cost_per_ad', $cost_per_ad);

        // Log the action
        LMB_Ad_Manager::log_activity(sprintf(
            'Package "%s" %s by admin %s',
            $name,
            $_POST['package_id'] ? 'updated' : 'created',
            wp_get_current_user()->display_name
        ));

        wp_send_json_success(['package_id' => $package_id]);
    }

    public function ajax_delete_package() {
        check_ajax_referer('lmb_packages_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Access denied']);
        }

        $package_id = intval($_POST['package_id']);
        
        if (!$package_id) {
            wp_send_json_error(['message' => __('Invalid package ID', 'lmb-core')]);
        }

        $package = get_post($package_id);
        if (!$package || $package->post_type !== 'lmb_package') {
            wp_send_json_error(['message' => __('Package not found', 'lmb-core')]);
        }

        $result = wp_delete_post($package_id, true);
        
        if (!$result) {
            wp_send_json_error(['message' => __('Failed to delete package', 'lmb-core')]);
        }

        // Log the action
        LMB_Ad_Manager::log_activity(sprintf(
            'Package "%s" deleted by admin %s',
            $package->post_title,
            wp_get_current_user()->display_name
        ));

        wp_send_json_success();
    }
}