<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) exit;

abstract class LMB_Form_Widget_Base extends Widget_Base {

    abstract protected function get_form_name();
    abstract protected function get_ad_type();
    abstract public function build_legal_text($data);

    public function get_name() {
        return 'lmb_form_' . $this->get_form_name();
    }

    public function get_title() {
        return __('Form: ', 'lmb-core') . $this->get_ad_type();
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return ['lmb-widgets'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to use this form.', 'lmb-core').'</p></div>';
            return;
        }

        if (!empty($_GET['lmb_form_success'])) {
            echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Ad Submitted Successfully','lmb-core').'</h3><p>'.esc_html__('Your legal ad has been saved as a draft. You can review it in your dashboard.', 'lmb-core').'</p></div>';
        }
        if (!empty($_GET['lmb_form_error'])) {
            echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Submission Failed','lmb-core').'</h3><p>'.esc_html(urldecode($_GET['lmb_form_error'])).'</p></div>';
        }

        $settings = $this->get_settings_for_display();
        $form_id = 'lmb-form-' . $this->get_id();
        ?>
        <form class="lmb-form elementor-form" method="post" name="<?php echo esc_attr($this->get_form_name()); ?>" id="<?php echo esc_attr($form_id); ?>">
            <input type="hidden" name="action" value="lmb_submit_dynamic_form">
            <input type="hidden" name="lmb_form_name" value="<?php echo esc_attr($this->get_form_name()); ?>">
            <?php wp_nonce_field('lmb_submit_' . $this->get_form_name()); ?>

            <div class="elementor-form-fields-wrapper elementor-labels-above">
                <?php
                // The child widget's render_form_fields() method will be called here.
                $this->render_form_fields();
                ?>
            </div>

            <div class.="elementor-field-group elementor-column elementor-field-type-submit elementor-col-100">
                <button type="submit" class="lmb-btn lmb-btn-primary">
                    <span><?php echo esc_html($settings['submit_button_text']); ?></span>
                </button>
            </div>
        </form>
        <?php
    }

    // Each child widget MUST implement this method to render its fields.
    abstract protected function render_form_fields();
}