<?php
use Elementor\Widget_Base;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class LMB_Newspaper_Directory_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_newspaper_directory';
    }

    public function get_title() {
        return __( 'LMB Newspaper Directory', 'lmb-core' );
    }

    public function get_icon() {
        return 'eicon-book-o';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function render() {
        $args = array(
            'post_type' => 'lmb_newspaper',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        $newspaper_query = new WP_Query( $args );

        if ( $newspaper_query->have_posts() ) {
            echo '<h2>' . esc_html__( 'Newspaper Directory', 'lmb-core' ) . '</h2>';
            echo '<ul class="lmb-newspaper-list">';
            while ( $newspaper_query->have_posts() ) {
                $newspaper_query->the_post();
                $pdf_attachment_id = get_field( 'newspaper_pdf' );
                $pdf_url = wp_get_attachment_url( $pdf_attachment_id );

                if ( $pdf_url ) {
                    echo '<li>';
                    echo '<a href="' . esc_url( $pdf_url ) . '" target="_blank">' . esc_html( get_the_title() ) . '</a>';
                    echo '</li>';
                }
            }
            echo '</ul>';
            wp_reset_postdata();
        } else {
            echo '<p>' . esc_html__( 'No newspapers found.', 'lmb-core' ) . '</p>';
        }
    }
}