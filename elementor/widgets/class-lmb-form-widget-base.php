<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

abstract class LMB_Form_Widget_Base extends Widget_Base {

    // These abstract methods MUST be defined by each child form widget.
    abstract protected function get_form_name();
    abstract protected function get_ad_type();
    abstract protected function register_form_controls(Widget_Base $widget);
    abstract public function build_legal_text($data); // This must be public now

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

    protected function _register_controls() {
        $this->start_controls_section(
            'form_fields_section',
            [
                'label' => __('Form Fields & Settings', 'lmb-core'),
            ]
        );

        // This function is defined in the child widget and adds the specific fields.
        $this->register_form_controls($this);

        $this->add_control(
            'submit_button_text',
            [
                'label' => __('Submit Button Text', 'lmb-core'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Submit Ad', 'lmb-core'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to use this form.', 'lmb-core').'</p></div>';
            return;
        }

        // Display success or error messages after form submission.
        if (!empty($_GET['lmb_form_success'])) {
            echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Ad Submitted Successfully','lmb-core').'</h3><p>'.esc_html__('Your legal ad has been saved as a draft. You can review it in your dashboard.', 'lmb-core').'</p></div>';
        }
        if (!empty($_GET['lmb_form_error'])) {
            echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Submission Failed','lmb-core').'</h3><p>'.esc_html(urldecode($_GET['lmb_form_error'])).'</p></div>';
        }

        $settings = $this->get_settings_for_display();
        ?>
        <form class="lmb-form elementor-form" method="post">
            <input type="hidden" name="action" value="lmb_submit_dynamic_form">
            <input type="hidden" name="lmb_form_name" value="<?php echo esc_attr($this->get_form_name()); ?>">
            <?php wp_nonce_field('lmb_submit_' . $this->get_form_name()); ?>

            <div class="elementor-form-fields-wrapper elementor-labels-above">
                <?php
                // We need a way to render the controls defined in register_form_controls.
                // Elementor handles this automatically on the frontend.
                // This message is a placeholder for the backend editor view.
                if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                    echo '<p><em>' . esc_html__('Form fields are configured in the widget settings and will display on the live page.', 'lmb-core') . '</em></p>';
                }
                ?>
            </div>

            <div class="elementor-field-group elementor-column elementor-field-type-submit">
                <button type="submit" class="lmb-btn lmb-btn-primary">
                    <span><?php echo esc_html($settings['submit_button_text']); ?></span>
                </button>
            </div>
        </form>
        <?php
    }
}