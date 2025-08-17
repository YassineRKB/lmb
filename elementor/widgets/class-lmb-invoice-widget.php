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
        $invoice_url = site_url( '?lmb-generate-invoice=true' );
        echo '<div class="lmb-invoice-widget">';
        echo '<h3>' . esc_html__( 'Generate Payment Invoice', 'lmb-core' ) . '</h3>';
        echo '<p>' . esc_html__( 'Click the button below to generate a PDF invoice with a unique reference number for bank payment.', 'lmb-core' ) . '</p>';
        echo '<a href="' . esc_url( $invoice_url ) . '" class="elementor-button elementor-button-link elementor-size-sm" target="_blank">';
        echo '<span>' . esc_html__( 'Generate Invoice', 'lmb-core' ) . '</span>';
        echo '</a>';
        echo '</div>';
    }
}