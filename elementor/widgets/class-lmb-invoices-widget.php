<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Invoices_Widget extends Widget_Base {
    public function get_name() { return 'lmb_invoices'; }
    public function get_title() { return __('LMB User Invoices', 'lmb-core'); }
    public function get_icon() { return 'eicon-file-download'; }
    public function get_categories() { return ['lmb-2']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('You must be logged in to view your invoices.', 'lmb-core') . '</p></div>';
            return;
        }

        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        // Get user's payment records (which contain invoice information)
        $payments_query = new WP_Query([
            'post_type' => 'lmb_payment',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => [
                [
                    'key' => 'user_id',
                    'value' => $user_id,
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $payments = $payments_query->posts;

        wp_enqueue_script('jquery');
        ?>
        <div class="lmb-invoices-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-file-invoice"></i> <?php esc_html_e('Your Invoices', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <?php if (!empty($payments)): ?>
                    <div class="lmb-invoices-table-container">
                        <table class="lmb-invoices-table">
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
                                <?php foreach ($payments as $payment): ?>
                                    <?php
                                    $package_id = get_post_meta($payment->ID, 'package_id', true);
                                    $package = get_post($package_id);
                                    $package_price = get_post_meta($package_id, 'price', true);
                                    $payment_status = get_post_meta($payment->ID, 'payment_status', true);
                                    $payment_reference = get_post_meta($payment->ID, 'payment_reference', true);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($payment_reference ?: '#' . $payment->ID); ?></strong>
                                        </td>
                                        <td><?php echo esc_html(get_the_date('Y-m-d H:i', $payment->ID)); ?></td>
                                        <td>
                                            <?php if ($package): ?>
                                                <?php echo esc_html($package->post_title); ?>
                                            <?php else: ?>
                                                <em><?php esc_html_e('Package not found', 'lmb-core'); ?></em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($package_price); ?> MAD</strong>
                                        </td>
                                        <td>
                                            <span class="lmb-status-badge lmb-status-<?php echo esc_attr($payment_status); ?>">
                                                <?php echo esc_html(ucfirst($payment_status)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-primary lmb-download-invoice" 
                                                    data-payment-id="<?php echo esc_attr($payment->ID); ?>">
                                                <i class="fas fa-download"></i> <?php esc_html_e('Download PDF', 'lmb-core'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($payments_query->max_num_pages > 1): ?>
                        <div class="lmb-pagination">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $paged,
                                'total' => $payments_query->max_num_pages,
                                'prev_text' => '&laquo; ' . esc_html__('Previous', 'lmb-core'),
                                'next_text' => esc_html__('Next', 'lmb-core') . ' &raquo;'
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="lmb-no-invoices">
                        <div class="lmb-empty-state">
                            <i class="fas fa-file-invoice fa-3x"></i>
                            <h4><?php esc_html_e('No Invoices Found', 'lmb-core'); ?></h4>
                            <p><?php esc_html_e('You haven\'t made any purchases yet. When you buy a package, your invoices will appear here.', 'lmb-core'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .lmb-invoices-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lmb-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .lmb-widget-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lmb-widget-content {
            padding: 20px;
        }
        .lmb-invoices-table-container {
            overflow-x: auto;
        }
        .lmb-invoices-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        .lmb-invoices-table th,
        .lmb-invoices-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-invoices-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .lmb-invoices-table tbody tr:hover {
            background: #f8f9fa;
        }
        .lmb-no-invoices {
            text-align: center;
            padding: 40px 20px;
        }
        .lmb-empty-state {
            max-width: 400px;
            margin: 0 auto;
        }
        .lmb-empty-state i {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .lmb-empty-state h4 {
            color: #495057;
            margin-bottom: 10px;
        }
        .lmb-empty-state p {
            color: #6c757d;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .lmb-invoices-table {
                font-size: 14px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.lmb-download-invoice').on('click', function() {
                const paymentId = $(this).data('payment-id');
                const button = $(this);
                const originalText = button.html();
                
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php esc_js_e('Generating...', 'lmb-core'); ?>');
                
                // Generate and download invoice PDF
                $.post(lmbAjax.lmbAjax.ajaxurl, {
                    action: 'lmb_generate_invoice_pdf',
                    nonce: '<?php echo wp_create_nonce('lmb_invoice_nonce'); ?>',
                    payment_id: paymentId
                }, function(response) {
                    if (response.success && response.data.pdf_url) {
                        // Open PDF in new tab
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        alert('<?php esc_js_e('Error generating invoice:', 'lmb-core'); ?> ' + (response.data.message || '<?php esc_js_e('Unknown error', 'lmb-core'); ?>'));
                    }
                }).fail(function() {
                    alert('<?php esc_js_e('Failed to generate invoice. Please try again.', 'lmb-core'); ?>');
                }).always(function() {
                    button.prop('disabled', false).html(originalText);
                });
            });
        });
        </script>
        <?php
        wp_reset_postdata();
    }
}

// Add AJAX handler for invoice generation
add_action('wp_ajax_lmb_generate_invoice_pdf', function() {
    check_ajax_referer('lmb_invoice_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Access denied']);
    }
    
    $payment_id = intval($_POST['payment_id']);
    $user_id = get_current_user_id();
    
    // Verify payment belongs to current user
    $payment_user_id = get_post_meta($payment_id, 'user_id', true);
    if ($payment_user_id != $user_id) {
        wp_send_json_error(['message' => 'Access denied']);
    }
    
    $payment = get_post($payment_id);
    if (!$payment || $payment->post_type !== 'lmb_payment') {
        wp_send_json_error(['message' => 'Payment not found']);
    }
    
    $package_id = get_post_meta($payment_id, 'package_id', true);
    $package = get_post($package_id);
    $package_price = get_post_meta($package_id, 'price', true);
    $payment_reference = get_post_meta($payment_id, 'payment_reference', true);
    
    // Generate invoice PDF
    try {
        if (class_exists('LMB_Invoice_Handler')) {
            $pdf_url = LMB_Invoice_Handler::create_package_invoice(
                $user_id,
                $package_id,
                $package_price,
                $package ? $package->post_content : '',
                $payment_reference ?: 'INV-' . $payment_id
            );
        } else {
            wp_send_json_error(['message' => 'Invoice handler not available']);
        }
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate PDF']);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});