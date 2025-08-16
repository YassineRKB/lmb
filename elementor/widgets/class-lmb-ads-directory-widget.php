<?php
use Elementor\Widget_Base;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class LMB_Ads_Directory_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_ads_directory';
    }

    public function get_title() {
        return __( 'LMB Ads Directory', 'lmb-core' );
    }

    public function get_icon() {
        return 'eicon-folder-o';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function render() {
        $args = array(
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key'     => 'lmb_status',
                    'value'   => 'published',
                    'compare' => '='
                )
            )
        );

        $ads_query = new WP_Query( $args );

        if ( $ads_query->have_posts() ) {
            echo '<h2>' . esc_html__( 'Legal Ads Directory', 'lmb-core' ) . '</h2>';
            echo '<div class="lmb-ad-list">';
            while ( $ads_query->have_posts() ) {
                $ads_query->the_post();
                $ad_type = get_field( 'ad_type' );
                $full_text = get_field( 'full_text' );
                $ad_pdf_url = get_field('ad_pdf_url'); // You'll need to create this field and save the PDF URL here.
                
                echo '<div class="lmb-ad-item">';
                echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
                echo '<p><strong>' . esc_html__( 'Ad Type:', 'lmb-core' ) . '</strong> ' . esc_html( $ad_type ) . '</p>';
                echo '<div class="lmb-ad-content">' . wp_kses_post( $full_text ) . '</div>';
                if ($ad_pdf_url) {
                    echo '<a href="' . esc_url($ad_pdf_url) . '" class="lmb-download-btn" target="_blank">' . esc_html__('Download PDF', 'lmb-core') . '</a>';
                }
                echo '</div>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'No legal ads found.', 'lmb-core' ) . '</p>';
        }
    }
}