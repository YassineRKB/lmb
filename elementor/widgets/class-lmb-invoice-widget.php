<?php
use Elementor\Widget_Base;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class LMB_Invoice_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_invoice_widget';
    }

    public function get_title() {
        return __( 'LMB Invoice Generator', 'lmb-core' );
    }

    public function get_icon() {
        return 'eicon-file-download';
    }

    public function get_categories() {
        return [ 'lmb-widgets' ];
    }

    protected function render() {
        // Note: The AJAX functionality for package invoices is handled by the pricing table widget.
        // This widget is a fallback or for a different purpose, e.g., a generic invoice.
        $invoice_url = site_url( '?lmb-generate-invoice=true' );
        ?>
        <div class="lmb-package-item" style="text-align: left; max-width: 500px; margin: auto;">
            <div class="lmb-package-title" style="text-align: center;">
                <i class="fas fa-file-invoice" style="margin-right: 8px;"></i>
                <?php esc_html_e( 'Generate Payment Invoice', 'lmb-core' ); ?>
            </div>
            <p class="lmb-package-description" style="text-align: center;">
                <?php esc_html_e( 'Click the button below to generate a PDF invoice with a unique reference number for bank payment.', 'lmb-core' ); ?>
            </p>
            <div class="lmb-package-action">
                <a href="<?php echo esc_url( $invoice_url ); ?>" class="lmb-btn lmb-btn-primary" target="_blank">
                    <i class="fas fa-download"></i>
                    <span><?php esc_html_e( 'Generate Invoice', 'lmb-core' ); ?></span>
                </a>
            </div>
        </div>
        <?php
    }
}