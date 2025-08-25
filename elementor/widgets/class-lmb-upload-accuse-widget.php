<?php
if (!defined('ABSPATH')) {
    exit;
}

class LMB_Upload_Accuse_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'lmb-upload-accuse';
    }

    public function get_title() {
        return __('Upload Accuse', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['lmb-admin-widgets'];
    }

    public function get_script_depends() {
        // This script is now part of the main lmb-core.js, so no specific dependency needed here
        // unless you create a separate file for it.
        return ['lmb-core'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<p>' . esc_html__('You must be an administrator to upload accuse documents.', 'lmb-core') . '</p>';
            return;
        }

        $legal_ads = get_posts([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'numberposts' => 100, // Increased limit
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [['key' => 'lmb_status', 'value' => 'published']]
        ]);
        ?>
        <div class="lmb-upload-accuse-widget lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-file-upload"></i> <?php esc_html_e('Upload Accuse', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-upload-messages"></div>
                <form id="lmb-upload-accuse-form" class="lmb-form" enctype="multipart/form-data">
                    <div class="lmb-form-group">
                        <label for="legal_ad_id"><?php esc_html_e('Select Legal Ad:', 'lmb-core'); ?></label>
                        <select name="legal_ad_id" id="legal_ad_id" class="lmb-select" required>
                            <option value=""><?php esc_html_e('Select a legal ad', 'lmb-core'); ?></option>
                            <?php foreach ($legal_ads as $ad) : ?>
                                <option value="<?php echo esc_attr($ad->ID); ?>">#<?php echo esc_attr($ad->ID); ?> - <?php echo esc_html($ad->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="lmb-form-group">
                        <label for="accuse_date"><?php esc_html_e('Accuse Date:', 'lmb-core'); ?></label>
                        <input type="date" name="accuse_date" id="accuse_date" class="lmb-input" required value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="lmb-form-group">
                        <label for="accuse_notes"><?php esc_html_e('Notes (Optional):', 'lmb-core'); ?></label>
                        <textarea name="accuse_notes" id="accuse_notes" class="lmb-textarea" rows="3"></textarea>
                    </div>
                     <div class="lmb-form-group">
                        <label for="accuse_file"><?php esc_html_e('Upload Accuse File (PDF, JPG, PNG):', 'lmb-core'); ?></label>
                        <input type="file" name="accuse_file" id="accuse_file" class="lmb-input" required accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class="lmb-form-actions">
                        <button type="submit" class="lmb-btn lmb-btn-primary"><?php esc_html_e('Upload Accuse', 'lmb-core'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}