<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LMB_Upload_Accuse_Widget extends Widget_Base {

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        add_action('admin_post_lmb_upload_accuse', [$this, 'handle_upload']);
        add_action('admin_post_nopriv_lmb_upload_accuse', [$this, 'handle_upload']); // If needed for non-logged-in, but restrict to admins
    }

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
        return ['lmb-widgets'];
    }

    // No script depends needed if only using jQuery, as it's core

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'lmb-core'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_legal_ad_id',
            [
                'label' => __('Default Legal Ad', 'lmb-core'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_legal_ads_options(),
                'default' => '',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'default_accuse_date',
            [
                'label' => __('Default Accuse Date', 'lmb-core'),
                'type' => Controls_Manager::DATE_TIME,
                'default' => date('Y-m-d'),
            ]
        );

        $this->add_control(
            'default_accuse_notes',
            [
                'label' => __('Default Notes', 'lmb-core'),
                'type' => Controls_Manager::TEXTAREA,
                'default' => '',
                'placeholder' => __('Add any additional notes...', 'lmb-core'),
            ]
        );

        $this->end_controls_section();
    }

    private function get_legal_ads_options() {
        $options = ['' => __('Select a legal ad', 'lmb-core')];
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

        if (is_wp_error($legal_ads)) {
            return $options; // Silent fail
        }

        foreach ($legal_ads as $ad) {
            $options[$ad->ID] = '#' . $ad->ID . ' - ' . $ad->post_title;
        }

        return $options;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        if (!current_user_can('manage_options')) {
            echo '<p>' . esc_html__('You must be an administrator to upload accuse documents.', 'lmb-core') . '</p>';
            return;
        }

        // Display success/error messages if set (e.g., from redirect)
        if (isset($_GET['upload_result']) && isset($_GET['message'])) {
            $success = $_GET['upload_result'] === 'success';
            $message = sanitize_text_field($_GET['message']);
            echo '<div class="' . ($success ? 'success' : 'error') . '"><h3>' . esc_html($success ? __('Accuse Uploaded Successfully', 'lmb-core') : __('Upload Failed', 'lmb-core')) . '</h3><p>' . esc_html($message) . '</p></div>';
        }

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

        ?>
        <div class="lmb-upload-accuse-widget">
            <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="lmb_upload_accuse">
                <?php wp_nonce_field('lmb_upload_accuse', '_wpnonce'); ?>
                <label for="legal_ad_id"><?php esc_html_e('Select Legal Ad:', 'lmb-core'); ?></label>
                <select name="legal_ad_id" id="legal_ad_id" required>
                    <option value=""><?php esc_html_e('Select a legal ad', 'lmb-core'); ?></option>
                    <?php foreach ($legal_ads as $ad) : ?>
                        <option value="<?php echo esc_attr($ad->ID); ?>" <?php selected($settings['default_legal_ad_id'], $ad->ID); ?>>#<?php echo esc_attr($ad->ID); ?> - <?php echo esc_html($ad->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="accuse_date"><?php esc_html_e('Accuse Date:', 'lmb-core'); ?></label>
                <input type="date" name="accuse_date" id="accuse_date" required value="<?php echo esc_attr($settings['default_accuse_date'] ?: date('Y-m-d')); ?>">
                <label for="accuse_notes"><?php esc_html_e('Notes:', 'lmb-core'); ?></label>
                <textarea name="accuse_notes" id="accuse_notes"><?php echo esc_textarea($settings['default_accuse_notes']); ?></textarea>
                <label for="accuse_file"><?php esc_html_e('Upload Accuse File (PDF, JPG, PNG):', 'lmb-core'); ?></label>
                <input type="file" name="accuse_file" id="accuse_file" required accept=".pdf,.jpg,.jpeg,.png">
                <button type="submit" class="button button-primary"><?php esc_html_e('Upload Accuse', 'lmb-core'); ?></button>
            </form>
            <h4><?php esc_html_e('Recent Accuses', 'lmb-core'); ?></h4>
            <?php $this->render_recent_accuses(); ?>
        </div>
        <?php
    }

    private function render_recent_accuses() {
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

            echo '<div class="accuse-item">';
            echo '<div class="accuse-header">';
            echo '<h5>' . esc_html($accuse->post_title) . '</h5>';
            echo '<p>';
            echo esc_html__('For Ad:', 'lmb-core') . ' ';
            if ($ad) {
                echo '#' . esc_html($ad_id) . ' - ' . esc_html($ad->post_title);
            } else {
                echo esc_html__('Ad not found', 'lmb-core');
            }
            echo ' • ' . esc_html__('Date:', 'lmb-core') . ' ' . esc_html($accuse_date);
            if ($notes) {
                echo '<br>' . esc_html__('Notes:', 'lmb-core') . ' ' . esc_html($notes);
            }
            echo '</p>';
            echo '</div>';
            echo '<div class="accuse-content">';
            echo '<a href="' . esc_url(wp_get_attachment_url($accuse->ID)) . '" target="_blank">' . esc_html__('View Accuse', 'lmb-core') . '</a>';
            echo '</div>';
            echo '</div>';
        }
    }

    public function handle_upload() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'lmb_upload_accuse') || !isset($_POST['legal_ad_id']) || empty($_FILES['accuse_file']['name'])) {
            wp_redirect(add_query_arg(['upload_result' => 'error', 'message' => __('Missing required information.', 'lmb-core')], wp_get_referer()));
            exit;
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $legal_ad_id = intval($_POST['legal_ad_id']);
        $accuse_date = sanitize_text_field($_POST['accuse_date']);
        $notes = sanitize_textarea_field($_POST['accuse_notes']);
        $file = $_FILES['accuse_file'];

        $legal_ad = get_post($legal_ad_id);
        if (!$legal_ad || $legal_ad->post_type !== 'lmb_legal_ad') {
            wp_redirect(add_query_arg(['upload_result' => 'error', 'message' => __('Invalid legal ad selected.', 'lmb-core')], wp_get_referer()));
            exit;
        }

        $filetype = wp_check_filetype($file['name']);
        if (!in_array($filetype['ext'], ['pdf', 'jpg', 'jpeg', 'png'])) {
            wp_redirect(add_query_arg(['upload_result' => 'error', 'message' => __('Invalid file type. Please upload a PDF, JPG, or PNG file.', 'lmb-core')], wp_get_referer()));
            exit;
        }

        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            wp_redirect(add_query_arg(['upload_result' => 'error', 'message' => __('File too large. Maximum size is 10MB.', 'lmb-core')], wp_get_referer()));
            exit;
        }

        // Upload the file
        $attachment_id = media_handle_upload('accuse_file', 0); // Upload to media library, not attached to a post
        if (is_wp_error($attachment_id)) {
            wp_redirect(add_query_arg(['upload_result' => 'error', 'message' => $attachment_id->get_error_message()], wp_get_referer()));
            exit;
        }

        // Save metadata
        update_post_meta($attachment_id, 'lmb_accuse_for_ad', $legal_ad_id);
        update_post_meta($attachment_id, 'lmb_accuse_date', $accuse_date);
        update_post_meta($attachment_id, 'lmb_accuse_notes', $notes);

        wp_redirect(add_query_arg(['upload_result' => 'success', 'message' => __('Accuse uploaded and saved successfully.', 'lmb-core')], wp_get_referer()));
        exit;
    }
}