<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LMB_Upload_Accuse_Widget extends \Elementor\Widget_Base {

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
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
        return ['lmb-cw-admin'];
    }

    public function get_script_depends() {
        wp_enqueue_script(
            'lmb-upload-accuse',
            LMB_CORE_URL . 'assets/js/lmb-upload-accuse.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script(
            'lmb-upload-accuse',
            'lmb_accuse_ajax',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lmb_upload_accuse_nonce')
            ]
        );
        return ['lmb-upload-accuse'];
    }

    public function get_style_depends() {
        wp_enqueue_style(
            'lmb-upload-accuse',
            LMB_CORE_URL . 'assets/css/lmb-upload-accuse.css',
            [],
            '1.0.0'
        );
        return ['lmb-upload-accuse'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'lmb-core'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'default_legal_ad_id',
            [
                'label' => __('Default Legal Ad', 'lmb-core'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_legal_ads_options(),
                'default' => '',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'default_accuse_date',
            [
                'label' => __('Default Accuse Date', 'lmb-core'),
                'type' => \Elementor\Controls_Manager::DATE_TIME,
                'default' => date('Y-m-d'),
            ]
        );

        $this->add_control(
            'default_accuse_notes',
            [
                'label' => __('Default Notes', 'lmb-core'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
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
            return $options;
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
            <div class="lmb-upload-messages"></div>
            <form id="lmb-upload-accuse-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="lmb_upload_accuse">
                <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('lmb_upload_accuse_nonce')); ?>">
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
}
