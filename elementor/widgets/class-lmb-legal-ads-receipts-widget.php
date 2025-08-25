<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class LMB_Legal_Ads_Receipts_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'lmb_legal_ads_receipts';
    }

    public function get_title() {
        return __('LMB Legal Ads Receipts', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['lmb-cw-user'];
    }

    public function get_script_depends() {
        // Enqueue Font Awesome
        wp_enqueue_script(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js',
            [],
            '5.15.4',
            true
        );
        wp_enqueue_script(
            'lmb-legal-ads-receipts',
            plugin_dir_url(__DIR__) . 'assets/js/lmb-legal-ads-receipts.js',
            ['jquery'],
            '1.0.0',
            true
        );
        wp_localize_script(
            'lmb-legal-ads-receipts',
            'lmbAjax',
            [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('lmb_receipt_nonce')
            ]
        );
        return ['font-awesome', 'lmb-legal-ads-receipts'];
    }

    public function get_style_depends() {
        wp_enqueue_style(
            'lmb-legal-ads-receipts',
            plugin_dir_url(__DIR__) . 'assets/css/lmb-legal-ads-receipts.css',
            [],
            '1.0.0'
        );
        return ['lmb-legal-ads-receipts'];
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
            'posts_per_page',
            [
                'label' => __('Receipts Per Page', 'lmb-core'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 10,
                'min' => 1,
                'max' => 50,
                'step' => 1,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('You must be logged in to view your receipts.', 'lmb-core') . '</p></div>';
            return;
        }

        $settings = $this->get_settings_for_display();
        $user_id = get_current_user_id();
        $paged = max(1, isset($_GET['lmb_page']) ? intval($_GET['lmb_page']) : 1);

        $ads_query = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => $settings['posts_per_page'] ?: 10,
            'paged' => $paged,
            'meta_query' => [
                [
                    'key' => 'lmb_status',
                    'value' => 'published',
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $ads = $ads_query->posts;
        ?>
        <div class="lmb-legal-ads-receipts-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-receipt"></i> <?php esc_html_e('Legal Ads Receipts', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <?php if (!empty($ads)): ?>
                    <div class="lmb-receipts-table-container">
                        <table class="lmb-receipts-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Ad ID', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Publication Date', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Ad Type', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Company', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Cost', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ads as $ad): ?>
                                    <?php
                                    $ad_type = get_post_meta($ad->ID, 'ad_type', true);
                                    $company_name = get_post_meta($ad->ID, 'company_name', true);
                                    $cost = get_user_meta($user_id, 'lmb_cost_per_ad', true) ?: get_option('lmb_default_cost_per_ad', 1);
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo esc_html($ad->ID); ?></strong></td>
                                        <td><?php echo esc_html(get_the_date('Y-m-d H:i', $ad->ID)); ?></td>
                                        <td><?php echo esc_html($ad_type ?: '-'); ?></td>
                                        <td><?php echo esc_html($company_name ?: '-'); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($cost); ?></strong> 
                                            <small><?php esc_html_e('points', 'lmb-core'); ?></small>
                                        </td>
                                        <td>
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-primary lmb-download-receipt" 
                                                    data-ad-id="<?php echo esc_attr($ad->ID); ?>"
                                                    data-ad-type="<?php echo esc_attr($ad_type); ?>">
                                                <i class="fas fa-download"></i> <?php esc_html_e('Download Receipt', 'lmb-core'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($ads_query->max_num_pages > 1): ?>
                        <div class="lmb-pagination">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('lmb_page', '%#%'),
                                'format' => '',
                                'current' => $paged,
                                'total' => $ads_query->max_num_pages,
                                'prev_text' => '&laquo; ' . esc_html__('Previous', 'lmb-core'),
                                'next_text' => esc_html__('Next', 'lmb-core') . ' &raquo;'
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="lmb-no-receipts">
                        <div class="lmb-empty-state">
                            <i class="fas fa-receipt fa-3x"></i>
                            <h4><?php esc_html_e('No Receipts Found', 'lmb-core'); ?></h4>
                            <p><?php esc_html_e('You don\'t have any published legal ads yet. Once your ads are approved and published, their receipts will appear here.', 'lmb-core'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}
