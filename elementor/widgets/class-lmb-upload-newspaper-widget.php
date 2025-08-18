<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Newspaper_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_newspaper'; }
    public function get_title(){ return __('LMB Upload Newspaper','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be an administrator to upload newspapers.', 'lmb-core').'</p></div>';
            return;
        }

        if (isset($_POST['lmb_upload_newspaper']) && wp_verify_nonce($_POST['_wpnonce'], 'lmb_upload_newspaper')) {
            $result = self::handle_upload();
            if ($result['success']) {
                echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Newspaper Uploaded Successfully','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            } else {
                echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Upload Failed','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            }
        }

        ?>
        <div class="lmb-upload-newspaper-container">
            <div class="lmb-upload-header">
                <h2>
                    <i class="fas fa-newspaper"></i>
                    <?php esc_html_e('Upload New Newspaper','lmb-core'); ?>
                </h2>
                <p><?php esc_html_e('Upload a new newspaper edition with PDF file and thumbnail image.','lmb-core'); ?></p>
            </div>
            
            <form method="post" enctype="multipart/form-data" class="lmb-upload-form">
                <?php wp_nonce_field('lmb_upload_newspaper'); ?>
                
                <div class="lmb-form-row">
                    <div class="lmb-form-group">
                        <label for="newspaper_title">
                            <i class="fas fa-heading"></i>
                            <?php esc_html_e('Newspaper Title','lmb-core'); ?>
                        </label>
                        <input type="text" name="newspaper_title" id="newspaper_title" 
                               placeholder="<?php esc_attr_e('Enter newspaper title...','lmb-core'); ?>" 
                               required class="lmb-input">
                    </div>
                    
                    <div class="lmb-form-group">
                        <label for="newspaper_date">
                            <i class="fas fa-calendar"></i>
                            <?php esc_html_e('Publication Date','lmb-core'); ?>
                        </label>
                        <input type="date" name="newspaper_date" id="newspaper_date" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               required class="lmb-input">
                    </div>
                </div>

                <div class="lmb-form-row">
                    <div class="lmb-form-group">
                        <label for="newspaper_pdf">
                            <i class="fas fa-file-pdf"></i>
                            <?php esc_html_e('Newspaper PDF','lmb-core'); ?>
                        </label>
                        <div class="lmb-file-upload">
                            <input type="file" name="newspaper_pdf" id="newspaper_pdf" 
                                   accept="application/pdf" required class="lmb-file-input">
                            <label for="newspaper_pdf" class="lmb-file-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span><?php esc_html_e('Choose PDF file...','lmb-core'); ?></span>
                            </label>
                        </div>
                        <small><?php esc_html_e('Maximum file size: 10MB. PDF format only.','lmb-core'); ?></small>
                    </div>
                    
                    <div class="lmb-form-group">
                        <label for="newspaper_thumbnail">
                            <i class="fas fa-image"></i>
                            <?php esc_html_e('Thumbnail Image','lmb-core'); ?>
                        </label>
                        <div class="lmb-file-upload">
                            <input type="file" name="newspaper_thumbnail" id="newspaper_thumbnail" 
                                   accept="image/jpeg,image/png,image/jpg" class="lmb-file-input">
                            <label for="newspaper_thumbnail" class="lmb-file-label">
                                <i class="fas fa-image"></i>
                                <span><?php esc_html_e('Choose image...','lmb-core'); ?></span>
                            </label>
                        </div>
                        <small><?php esc_html_e('Optional. JPG, PNG formats. Maximum 2MB.','lmb-core'); ?></small>
                    </div>
                </div>
                
                <div class="lmb-form-actions">
                    <button type="submit" name="lmb_upload_newspaper" class="lmb-btn lmb-btn-primary lmb-btn-large">
                        <i class="fas fa-upload"></i>
                        <?php esc_html_e('Upload Newspaper','lmb-core'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    private static function handle_upload() {
        if (!current_user_can('manage_options') || !isset($_POST['newspaper_title']) || empty($_FILES['newspaper_pdf']['name'])) {
            return ['success' => false, 'message' => __('Missing required information.', 'lmb-core')];
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $title = sanitize_text_field($_POST['newspaper_title']);
        $date = sanitize_text_field($_POST['newspaper_date']);
        $description = sanitize_textarea_field($_POST['newspaper_description'] ?? '');

        // Validate PDF file
        $pdf_file = $_FILES['newspaper_pdf'];
        $pdf_file_return = wp_check_filetype($pdf_file['name']);
        if($pdf_file_return['ext'] !== 'pdf') {
            return ['success' => false, 'message' => __('Invalid PDF file type.', 'lmb-core')];
        }
        if($pdf_file['size'] > 10 * 1024 * 1024) { // 10MB
            return ['success' => false, 'message' => __('PDF file is too large. Maximum size is 10MB.', 'lmb-core')];
        }

        // Handle PDF upload
        $pdf_attachment_id = media_handle_upload('newspaper_pdf', 0);
        if (is_wp_error($pdf_attachment_id)) {
            return ['success' => false, 'message' => $pdf_attachment_id->get_error_message()];
        }

        // Handle thumbnail upload (optional)
        $thumbnail_id = null;
        if (!empty($_FILES['newspaper_thumbnail']['name'])) {
            $thumbnail_file = $_FILES['newspaper_thumbnail'];
            $thumbnail_file_return = wp_check_filetype($thumbnail_file['name']);
            if(in_array($thumbnail_file_return['ext'], ['jpg', 'jpeg', 'png'])) {
                if($thumbnail_file['size'] <= 2 * 1024 * 1024) { // 2MB
                    $thumbnail_id = media_handle_upload('newspaper_thumbnail', 0);
                    if (is_wp_error($thumbnail_id)) {
                        $thumbnail_id = null; // Continue without thumbnail if upload fails
                    }
                }
            }
        }

        // Create the newspaper post
        $post_data = [
            'post_type' => 'lmb_newspaper',
            'post_title' => $title,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_date' => $date . ' 00:00:00',
        ];

        $newspaper_id = wp_insert_post($post_data);
        if (is_wp_error($newspaper_id)) {
            wp_delete_attachment($pdf_attachment_id, true);
            if ($thumbnail_id) wp_delete_attachment($thumbnail_id, true);
            return ['success' => false, 'message' => __('Could not create newspaper post.', 'lmb-core')];
        }

        // Set the PDF and thumbnail
        update_post_meta($newspaper_id, 'newspaper_pdf', $pdf_attachment_id);
        if ($thumbnail_id) {
            set_post_thumbnail($newspaper_id, $thumbnail_id);
        }

        // Associate attachments with the post
        wp_update_post(['ID' => $pdf_attachment_id, 'post_parent' => $newspaper_id]);
        if ($thumbnail_id) {
            wp_update_post(['ID' => $thumbnail_id, 'post_parent' => $newspaper_id]);
        }

        LMB_Ad_Manager::log_activity(sprintf('New newspaper "%s" uploaded by %s', $title, wp_get_current_user()->display_name));

        return ['success' => true, 'message' => __('Newspaper has been uploaded successfully.', 'lmb-core')];
    }
}