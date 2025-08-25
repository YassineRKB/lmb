<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Invoices_Widget extends Widget_Base {
    public function get_name() { return 'lmb_invoices'; }
    public function get_title() { return __('LMB User Invoices', 'lmb-core'); }
    public function get_icon() { return 'eicon-file-download'; }
    public function get_categories() { return ['lmb-cw-user']; }

    public function get_script_depends() {
        return ['lmb-invoices'];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('You must be logged in to view your invoices.', 'lmb-core') . '</p></div>';
            return;
        }

        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        $payments_query = new WP_Query([
            'post_type' => 'lmb_payment',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => [['key' => 'user_id', 'value' => $user_id, 'compare' => '=']],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        $payments = $payments_query->posts;
        ?>
        <div class="lmb-invoices-widget lmb-user-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-file-invoice"></i> <?php esc_html_e('Your Invoices', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <?php if (!empty($payments)): ?>
                    <div class="lmb-table-container">
                        <table class="lmb-data-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Invoice ID', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Date', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Package', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Amount', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Status', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment):
                                    $package_id = get_post_meta($payment->ID, 'package_id', true);
                                    $package = get_post($package_id);
                                    $package_price = get_post_meta($package_id, 'price', true);
                                    $payment_status = get_post_meta($payment->ID, 'payment_status', true);
                                    $payment_reference = get_post_meta($payment->ID, 'payment_reference', true);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($payment_reference ?: '#' . $payment->ID); ?></strong></td>
                                        <td><?php echo esc_html(get_the_date('Y-m-d H:i', $payment->ID)); ?></td>
                                        <td><?php echo $package ? esc_html($package->post_title) : '<em>' . esc_html__('N/A', 'lmb-core') . '</em>'; ?></td>
                                        <td><strong><?php echo esc_html($package_price); ?> MAD</strong></td>
                                        <td><span class="lmb-status-badge lmb-status-<?php echo esc_attr($payment_status); ?>"><?php echo esc_html(ucfirst($payment_status)); ?></span></td>
                                        <td>
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-primary lmb-download-invoice" data-payment-id="<?php echo esc_attr($payment->ID); ?>">
                                                <i class="fas fa-download"></i> <?php esc_html_e('Download PDF', 'lmb-core'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($payments_query->max_num_pages > 1): ?>
                        <div class="lmb-pagination">
                            <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $paged, 'total' => $payments_query->max_num_pages, 'prev_text' => '&laquo; ' . esc_html__('Previous'), 'next_text' => esc_html__('Next') . ' &raquo;']); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="lmb-no-results-container">
                        <div class="lmb-empty-state">
                            <i class="fas fa-file-invoice fa-3x"></i>
                            <h4><?php esc_html_e('No Invoices Found', 'lmb-core'); ?></h4>
                            <p><?php esc_html_e('You haven\'t made any purchases yet. When you buy a package, your invoices will appear here.', 'lmb-core'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}