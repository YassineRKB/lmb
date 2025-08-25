<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Newspaper_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_newspaper'; }
    public function get_title(){ return __('LMB Upload Newspaper','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-admin-widgets']; }

    public function get_script_depends() {
        return ['lmb-upload-newspaper'];
    }

    public function get_style_depends() {
        return ['lmb-admin-widgets'];
    }
    
    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be an administrator to upload newspapers.', 'lmb-core').'</p></div>';
            return;
        }
        ?>
        <div class="lmb-upload-newspaper-container lmb-admin-widget">
            <div class="lmb-widget-header"><h3><i class="fas fa-newspaper"></i> <?php esc_html_e('Upload New Newspaper','lmb-core'); ?></h3></div>
            <div class="lmb-widget-content">
                <form id="lmb-upload-newspaper-form" class="lmb-form" enctype="multipart/form-data">
                    <div class="lmb-form-row">
                        <div class="lmb-form-group">
                            <label for="newspaper_title"><?php esc_html_e('Newspaper Title','lmb-core'); ?></label>
                            <input type="text" name="newspaper_title" id="newspaper_title" required class="lmb-input">
                        </div>
                        <div class="lmb-form-group">
                            <label for="newspaper_date"><?php esc_html_e('Publication Date','lmb-core'); ?></label>
                            <input type="date" name="newspaper_date" id="newspaper_date" value="<?php echo date('Y-m-d'); ?>" required class="lmb-input">
                        </div>
                    </div>
                    <div class="lmb-form-row">
                        <div class="lmb-form-group">
                            <label for="newspaper_pdf"><?php esc_html_e('Newspaper PDF','lmb-core'); ?></label>
                            <input type="file" name="newspaper_pdf" id="newspaper_pdf" accept="application/pdf" required class="lmb-input">
                            <small><?php esc_html_e('Maximum file size: 10MB.','lmb-core'); ?></small>
                        </div>
                        <div class="lmb-form-group">
                            <label for="newspaper_thumbnail"><?php esc_html_e('Thumbnail Image','lmb-core'); ?></label>
                            <input type="file" name="newspaper_thumbnail" id="newspaper_thumbnail" accept="image/jpeg,image/png,image/jpg" class="lmb-input">
                            <small><?php esc_html_e('Optional. JPG, PNG formats. Maximum 2MB.','lmb-core'); ?></small>
                        </div>
                    </div>
                    <div class="lmb-form-actions">
                        <button type="submit" class="lmb-btn lmb-btn-primary lmb-btn-large"><i class="fas fa-upload"></i> <?php esc_html_e('Upload Newspaper','lmb-core'); ?></button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}