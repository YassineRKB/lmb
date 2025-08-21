<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Accuse_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_accuse'; }
    public function get_title(){ return __('LMB Upload Accuse','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be an administrator to upload accuse documents.', 'lmb-core').'</p></div>';
            return;
        }

        if (isset($_POST['lmb_upload_accuse']) && wp_verify_nonce($_POST['_wpnonce'], 'lmb_upload_accuse')) {
            $result = self::handle_upload();
            if ($result['success']) {
                echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Accuse Uploaded Successfully','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            } else {
                echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Upload Failed','lmb-core').'</h3><p>'.esc_html($result['message']).'</p></div>';
            }
        }

        // Get published legal ads for selection
        $legal_ads = get_posts([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => 'lmb_status',
                    'value' => 'published',
                    'compare' => '='
                ]
            ]
        ]);

        wp_enqueue_script('jquery');
        ?>
        <div class="lmb-upload-accuse-container">
            <div class="lmb-upload-header">
                <h2>
                    <i class="fas fa-file-signature"></i>
                    <?php esc_html_e('Upload Accuse Document','lmb-core'); ?>
                </h2>
                <p><?php esc_html_e('Upload a scanned accuse document and associate it with a specific legal ad.','lmb-core'); ?></p>
            </div>
            
            <form method="post" enctype="multipart/form-data" class="lmb-upload-form">
                <?php wp_nonce_field('lmb_upload_accuse'); ?>
                
                <div class="lmb-form-row">
                    <div class="lmb-form-group">
                        <label for="legal_ad_id">
                            <i class="fas fa-gavel"></i>
                            <?php esc_html_e('Select Legal Ad','lmb-core'); ?>
                        </label>
                        <select name="legal_ad_id" id="legal_ad_id" required class="lmb-select">
                            <option value=""><?php esc_html_e('Choose a legal ad...','lmb-core'); ?></option>
                            <?php foreach ($legal_ads as $ad): ?>
                                <?php
                                $client_id = get_post_meta($ad->ID, 'lmb_client_id', true);
                                $user = get_userdata($client_id);
                                $user_name = $user ? $user->display_name : 'Unknown User';
                                ?>
                                <option value="<?php echo esc_attr($ad->ID); ?>">
                                    #<?php echo $ad->ID; ?> - <?php echo esc_html($ad->post_title); ?> (<?php echo esc_html($user_name); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="lmb-form-group">
                        <label for="accuse_date">
                            <i class="fas fa-calendar"></i>
                            <?php esc_html_e('Accuse Date','lmb-core'); ?>
                        </label>
                        <input type="date" name="accuse_date" id="accuse_date" 
                               value="<?php echo date('Y-m-d'); ?>" 
                               required class="lmb-input">
                    </div>
                </div>

                <div class="lmb-form-group">
                    <label for="accuse_file">
                        <i class="fas fa-file-pdf"></i>
                        <?php esc_html_e('Accuse Document','lmb-core'); ?>
                    </label>
                    <div class="lmb-file-upload">
                        <input type="file" name="accuse_file" id="accuse_file" 
                               accept="application/pdf,image/jpeg,image/png,image/jpg" required class="lmb-file-input">
                        <label for="accuse_file" class="lmb-file-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <span><?php esc_html_e('Choose accuse file...','lmb-core'); ?></span>
                        </label>
                    </div>
                    <small><?php esc_html_e('Accepted formats: PDF, JPG, PNG. Maximum size: 10MB.','lmb-core'); ?></small>
                </div>

                <div class="lmb-form-group">
                    <label for="accuse_notes">
                        <i class="fas fa-sticky-note"></i>
                        <?php esc_html_e('Notes (Optional)','lmb-core'); ?>
                    </label>
                    <textarea name="accuse_notes" id="accuse_notes" rows="3" class="lmb-textarea" 
                              placeholder="<?php esc_attr_e('Add any additional notes about this accuse...','lmb-core'); ?>"></textarea>
                </div>
                
                <div class="lmb-form-actions">
                    <button type="submit" name="lmb_upload_accuse" class="lmb-btn lmb-btn-primary lmb-btn-large">
                        <i class="fas fa-upload"></i>
                        <?php esc_html_e('Upload Accuse','lmb-core'); ?>
                    </button>
                </div>
            </form>

            <!-- Recent Accuses -->
            <div class="lmb-recent-accuses">
                <h3><?php esc_html_e('Recent Accuses', 'lmb-core'); ?></h3>
                <?php $this->render_recent_accuses(); ?>
            </div>
        </div>

        <style>
        .lmb-upload-accuse-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lmb-upload-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .lmb-upload-header h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .lmb-upload-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        .lmb-upload-form {
            padding: 30px;
        }
        .lmb-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .lmb-form-group {
            margin-bottom: 20px;
        }
        .lmb-form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .lmb-input, .lmb-textarea, .lmb-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #fafafa;
        }
        .lmb-input:focus, .lmb-textarea:focus, .lmb-select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .lmb-file-upload {
            position: relative;
        }
        .lmb-file-input {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        .lmb-file-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            background: #fafafa;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #50575e;
        }
        .lmb-file-label:hover {
            border-color: #667eea;
            background: #f8f9ff;
            color: #667eea;
        }
        .lmb-form-actions {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #f0f0f1;
        }
        .lmb-recent-accuses {
            padding: 30px;
            border-top: 1px solid #f0f0f1;
            background: #f8f9fa;
        }
        .lmb-accuse-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .lmb-accuse-info h5 {
            margin: 0 0 5px 0;
            color: #1d2327;
        }
        .lmb-accuse-meta {
            font-size: 12px;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .lmb-form-row {
                grid-template-columns: 1fr;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Update file label when file is selected
            $('#accuse_file').on('change', function() {
                const fileName = $(this)[0].files[0]?.name || '<?php esc_js_e('Choose accuse file...', 'lmb-core'); ?>';
                $(this).siblings('.lmb-file-label').find('span').text(fileName);
            });
        });
        </script>
        <?php
    }
    
    private function render_recent_accuses() {
        // Get recent accuse uploads
        $recent_accuses = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'numberposts' => 5,
            'meta_query' => [
                [
                    'key' => 'lmb_accuse_for_ad',
                    'compare' => 'EXISTS'
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        if (empty($recent_accuses)) {
            echo '<p>' . esc_html__('No accuses uploaded yet.', 'lmb-core') . '</p>';
            return;
        }

        foreach ($recent_accuses as $accuse) {
            $ad_id = get_post_meta($accuse->ID, 'lmb_accuse_for_ad', true);
            $ad = get_post($ad_id);
            $accuse_date = get_post_meta($accuse->ID, 'lmb_accuse_date', true);
            $notes = get_post_meta($accuse->ID, 'lmb_accuse_notes', true);
            
            echo '<div class="lmb-accuse-item">';
            echo '<div class="lmb-accuse-info">';
            echo '<h5>' . esc_html($accuse->post_title) . '</h5>';
            echo '<div class="lmb-accuse-meta">';
            echo '<strong>' . esc_html__('For Ad:', 'lmb-core') . '</strong> ';
            if ($ad) {
                echo '#' . $ad_id . ' - ' . esc_html($ad->post_title);
            } else {
                echo esc_html__('Ad not found', 'lmb-core');
            }
            echo ' • <strong>' . esc_html__('Date:', 'lmb-core') . '</strong> ' . esc_html($accuse_date);
            if ($notes) {
                echo '<br><strong>' . esc_html__('Notes:', 'lmb-core') . '</strong> ' . esc_html($notes);
            }
            echo '</div>';
            echo '</div>';
            echo '<div class="lmb-accuse-actions">';
            echo '<a href="' . wp_get_attachment_url($accuse->ID) . '" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-primary">';
            echo '<i class="fas fa-eye"></i> ' . esc_html__('View', 'lmb-core');
            echo '</a>';
            echo '</div>';
            echo '</div>';
        }
    }
    
    private static function handle_upload() {
        if (!current_user_can('manage_options') || !isset($_POST['legal_ad_id']) || empty($_FILES['accuse_file']['name'])) {
            return ['success' => false, 'message' => __('Missing required information.', 'lmb-core')];
        }
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $legal_ad_id = intval($_POST['legal_ad_id']);
        $accuse_date = sanitize_text_field($_POST['accuse_date']);
        $notes = sanitize_textarea_field($_POST['accuse_notes']);

        // Validate legal ad exists
        $legal_ad = get_post($legal_ad_id);
        if (!$legal_ad || $legal_ad->post_type !== 'lmb_legal_ad') {
            return ['success' => false, 'message' => __('Invalid legal ad selected.', 'lmb-core')];
        }

        // Validate file
        $file = $_FILES['accuse_file'];
        $file_return = wp_check_filetype($file['name']);
        if(!in_array($file_return['ext'], ['pdf', 'jpg', 'jpeg', 'png'])) {
            return ['success' => false, 'message' => __('Invalid file type. Please upload a PDF, JPG, or PNG file.', 'lmb-core')];
        }
        if($file['size'] > 10 * 1024 * 1024) { // 10MB
            return ['success' => false, 'message' => __('File is too large. Maximum size is 10MB.', 'lmb-core')];
        }

        // Handle file upload
        $attachment_id = media_handle_upload('accuse_file', 0);
        if (is_wp_error($attachment_id)) {
            return ['success' => false, 'message' => $attachment_id->get_error_message()];
        }

        // Update attachment title
        $client_id = get_post_meta($legal_ad_id, 'lmb_client_id', true);
        $user = get_userdata($client_id);
        $user_name = $user ? $user->display_name : 'Unknown User';
        
        wp_update_post([
            'ID' => $attachment_id,
            'post_title' => sprintf('Accuse for Ad #%d (%s)', $legal_ad_id, $user_name)
        ]);

        // Save metadata
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $legal_ad_id);
        update_post_meta($attachment_id, 'lmb_accuse_date', $accuse_date);
        if ($notes) {
            update_post_meta($attachment_id, 'lmb_accuse_notes', $notes);
        }

        // Update the legal ad with accuse info
        update_post_meta($legal_ad_id, 'lmb_accuse_attachment_id', $attachment_id);
        update_post_meta($legal_ad_id, 'lmb_accuse_uploaded_date', current_time('mysql'));

        // Log the activity
        if (class_exists('LMB_Ad_Manager')) {
            LMB_Ad_Manager::log_activity(sprintf(
                'Accuse uploaded for legal ad #%d (%s) by %s',
                $legal_ad_id,
                $legal_ad->post_title,
                wp_get_current_user()->display_name
            ));
        }

        // Notify the user
        if (class_exists('LMB_Notification_Manager') && $client_id) {
            LMB_Notification_Manager::add_user_notification(
                $client_id,
                __('Accuse Document Available', 'lmb-core'),
                sprintf(__('The accuse document for your legal ad "%s" has been uploaded and is now available.', 'lmb-core'), $legal_ad->post_title),
                'success',
                'fas fa-file-signature'
            );
        }

        return ['success' => true, 'message' => __('Accuse has been uploaded successfully and associated with the legal ad.', 'lmb-core')];
    }
}