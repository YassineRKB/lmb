<?php
// FILE: elementor/widgets/class-lmb-invoices-widget.php

use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Invoices_Widget extends Widget_Base {
    public function get_name() { return 'lmb_invoices'; }
    public function get_title() { return __('LMB Payment Invoices', 'lmb-core'); }
    public function get_title() { return __('Factures de Paiement LMB', 'lmb-core'); }
    public function get_icon() { return 'eicon-file-download'; }
    public function get_categories() { return ['lmb-user-widgets-v2']; } // Changed category

    public function get_script_depends() {
        return []; // Removed 'lmb-invoices' dependency
    }

    public function get_style_depends() {
        return ['lmb-user-widgets-v2']; // Changed to V2 styles
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Vous devez être connecté pour voir vos factures.', 'lmb-core') . '</p></div>';
            return;
        }

        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        $payments_query = new WP_Query([
            'post_type' => 'lmb_payment',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'paged' => $paged,
            'meta_query' => [['key' => 'user_id', 'value' => $user_id]],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        ?>
        <div class="lmb-invoices-widget lmb-user-widget-v2">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-file-invoice"></i> Factures de Paiement</h3>
            </div>
            <div class="lmb-widget-content">
                <?php if ($payments_query->have_posts()): ?>
                    <div class="lmb-table-container">
                        <table class="lmb-data-table">
                            <thead>
                                <tr>
                                    <th>ID Facture</th>
                                    <th>Date</th>
                                    <th>Package</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payments_query->have_posts()): $payments_query->the_post();
                                    $payment = get_post();
                                    $package_id = get_post_meta($payment->ID, 'package_id', true);
                                    $package = get_post($package_id);
                                    $package_price = get_post_meta($payment->ID, 'package_price', true);
                                    $payment_status = get_post_meta($payment->ID, 'payment_status', true);
                                    $payment_reference = get_post_meta($payment->ID, 'payment_reference', true);
                                    $rejection_reason = get_post_meta($payment->ID, 'rejection_reason', true);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($payment_reference ?: '#' . $payment->ID); ?></strong></td>
                                        <td><?php echo esc_html(get_the_date('Y-m-d H:i', $payment->ID)); ?></td>
                                        <td><?php echo $package ? esc_html($package->post_title) : '<em>N/A</em>'; ?></td>
                                        <td><strong><?php echo esc_html($package_price); ?> MAD</strong></td>
                                        <td>
                                            <span class="lmb-status-badge lmb-status-<?php echo esc_attr($payment_status); ?>">
                                                <?php echo esc_html(ucfirst($payment_status)); ?>
                                            </span>
                                            <?php if ($payment_status === 'rejected' && !empty($rejection_reason)): ?>
                                                <div class="lmb-rejection-reason">
                                                    <strong><?php esc_html_e('Reason:', 'lmb-core'); ?></strong> <?php echo esc_html($rejection_reason); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                                <strong>Raison:</strong> <?php echo esc_html($rejection_reason); ?>
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
                            <h4>Aucune Facture Trouvée</h4>
                            <p>Vous n'avez encore effectué aucun achat. Lorsque vous achèterez un package, vos factures apparaîtront ici.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}