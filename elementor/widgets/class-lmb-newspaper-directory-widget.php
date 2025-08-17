<?php
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Newspaper_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_newspaper_directory'; }
    public function get_title() { return __('LMB Newspaper Directory','lmb-core'); }
    public function get_icon()  { return 'eicon-library-upload'; }
    public function get_categories(){ return ['general']; }

    protected function render() {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        echo '<form method="get" class="lmb-filter">';
        echo '<input type="text" name="s" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Search title…','lmb-core').'" />';
        echo '<button type="submit">'.esc_html__('Search','lmb-core').'</button>';
        echo '</form>';

        $q = new WP_Query([
            'post_type' => 'lmb_newspaper',
            'post_status' => 'publish',
            's' => $search,
            'posts_per_page' => 12,
            'paged' => max(1, (int)($_GET['paged'] ?? 1))
        ]);

        if ($q->have_posts()){
            echo '<div class="lmb-news-grid">';
            while($q->have_posts()){ $q->the_post();
                $pdf = get_post_meta(get_the_ID(), 'newspaper_pdf_url', true);
                echo '<article class="lmb-news">';
                echo '<h3>'.esc_html(get_the_title()).'</h3>';
                if ($pdf) echo '<p><a target="_blank" href="'.esc_url($pdf).'">'.esc_html__('Download PDF','lmb-core').'</a></p>';
                echo '</article>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>'.esc_html__('No newspapers found.','lmb-core').'</p>';
        }
    }
}
