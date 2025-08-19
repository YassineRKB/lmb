<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

abstract class LMB_Form_Widget_Base extends Widget_Base {

    // These must be defined by the child class
    abstract protected function get_form_name();
    abstract protected function get_ad_type();
    abstract protected function register_form_controls(Widget_Base $widget);
    abstract protected function build_legal_text($data);

    public function get_name() {
        return 'lmb_form_' . $this->get_form_name() . '_widget';
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
                'label' => __('Form Fields', 'lmb-core'),
            ]
        );

        // Let the child class register its specific controls
        $this->register_form_controls($this);

        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to use this form.', 'lmb-core').'</p></div>';
            return;
        }

        // Handle form submission feedback
        if (isset($_GET['lmb_form_success']) && $_GET['lmb_form_success'] === 'true') {
            echo '<div class="lmb-notice lmb-notice-success"><h3>'.esc_html__('Ad Submitted Successfully','lmb-core').'</h3><p>'.esc_html__('Your legal ad has been saved as a draft in your dashboard.', 'lmb-core').'</p></div>';
        }
        if (isset($_GET['lmb_form_error'])) {
            echo '<div class="lmb-notice lmb-notice-error"><h3>'.esc_html__('Submission Failed','lmb-core').'</h3><p>'.esc_html(urldecode($_GET['lmb_form_error'])).'</p></div>';
        }

        ?>
        <form class="lmb-form" method="post">
            <input type="hidden" name="action" value="lmb_submit_dynamic_form">
            <input type="hidden" name="lmb_form_name" value="<?php echo esc_attr($this->get_form_name()); ?>">
            <?php wp_nonce_field('lmb_submit_' . $this->get_form_name()); ?>

            <div class="elementor-form-fields-wrapper">
                <?php
                // This will render the controls defined in the child widget
                // In a real scenario, you'd loop through registered controls and render them.
                // For this implementation, the controls are defined directly in the child's render method for simplicity.
                echo 'This space is for Elementor to render the controls you define in the child widget.';
                ?>
            </div>
        </form>
        <?php
    }

    // Static method to handle the submission for all forms
    public static function handle_form_submission() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'lmb_submit_dynamic_form' || !isset($_POST['lmb_form_name'])) {
            return;
        }

        $form_name = sanitize_key($_POST['lmb_form_name']);

        if (!wp_verify_nonce($_POST['_wpnonce'], 'lmb_submit_' . $form_name)) {
            wp_die('Security check failed.');
        }

        // Find the correct widget class to handle this form
        $widget_class = self::get_widget_class_from_form_name($form_name);

        if (!$widget_class || !class_exists($widget_class)) {
            self::redirect_with_error('Invalid form type.');
            return;
        }

        $form_data = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'form_fields_') === 0) {
                $field_name = str_replace('form_fields_', '', $key);
                $form_data[$field_name] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            }
        }
        
        try {
            // Instantiate the widget to access its methods
            $widget_instance = new $widget_class();
            $full_text = $widget_instance->build_legal_text($form_data);
            
            $ad_data = [
                'ad_type'   => $widget_instance->get_ad_type(),
                'full_text' => $full_text,
                'title'     => isset($form_data['companyName']) ? sanitize_text_field($form_data['companyName']) : $widget_instance->get_ad_type()
            ];

            LMB_Form_Handler::create_legal_ad($ad_data);
            self::redirect_with_success();

        } catch (Exception $e) {
            self::redirect_with_error($e->getMessage());
        }
    }

    private static function get_widget_class_from_form_name($form_name) {
        // Map form names to their widget classes
        $forms = [
            'constitution_sarl' => 'LMB_Form_Constitution_Sarl_Widget',
            'constitution_sarl_au' => 'LMB_Form_Constitution_Sarl_Au_Widget',
            'modification_siege' => 'LMB_Form_Modification_Siege_Widget',
            'modification_objet' => 'LMB_Form_Modification_Objet_Widget',
            'modification_gerant' => 'LMB_Form_Modification_Gerant_Widget',
            'modification_denomination' => 'LMB_Form_Modification_Denomination_Widget',
            'modification_capital' => 'LMB_Form_Modification_Capital_Widget',
            'modification_cession' => 'LMB_Form_Modification_Cession_Widget',
            'dissolution_anticipee' => 'LMB_Form_Dissolution_Anticipee_Widget',
            'dissolution_cloture' => 'LMB_Form_Dissolution_Cloture_Widget',
        ];
        return $forms[$form_name] ?? null;
    }

    private static function redirect_with_error($message) {
        $redirect_url = add_query_arg('lmb_form_error', urlencode($message), wp_get_referer());
        wp_redirect($redirect_url);
        exit();
    }

    private static function redirect_with_success() {
        $redirect_url = add_query_arg('lmb_form_success', 'true', wp_get_referer());
        wp_redirect($redirect_url);
        exit();
    }
}