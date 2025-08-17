<?php
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Ads_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_ads_directory'; }
    public function get_title() { return __('LMB Ads Directory','lmb-core'); }
    public function get_icon() { return 'eicon-library-download'; }
    public function get_categories() { return ['general']; }

    protected function render() {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $type   = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';

        echo '<form method="get" class="lmb-filter">';
        echo '<input type="text" name="s" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Search title…','lmb-core').'" />';
        echo '<input type="text" name="type" value="'.esc_attr($type).'" placeholder="'.esc_attr__('Ad type…','lmb-core').'" />';
        echo '<button type="submit">'.esc_html__('Search','lmb-core').'</button>';
        echo '</form>';

        $meta = [];
        if ($type) $meta[] = ['key'=>'ad_type','value'=>$type,'compare'=>'LIKE'];

        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            's' => $search,
            'meta_query' => $meta,
            'posts_per_page' => 12,
            'paged' => max(1, (int)($_GET['paged'] ?? 1))
        ]);

        if ($q->have_posts()){
            echo '<div class="lmb-ads-grid">';
            while($q->have_posts()){ $q->the_post();
                $full = (string) get_post_meta(get_the_ID(), 'full_text', true);
                $ad_type = (string) get_post_meta(get_the_ID(), 'ad_type', true);
                $pdf = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                echo '<article class="lmb-ad">';
                echo '<h3>'.esc_html(get_the_title()).'</h3>';
                echo '<p><strong>'.esc_html__('Ad Type:','lmb-core').'</strong> '.esc_html($ad_type).'</p>';
                echo '<div class="lmb-ad-content">'.wp_kses_post($full).'</div>';
                if ($pdf) echo '<p><a target="_blank" href="'.esc_url($pdf).'">'.esc_html__('Download PDF','lmb-core').'</a></p>';
                echo '</article>';
            }
            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p>'.esc_html__('No legal ads found.','lmb-core').'</p>';
        }
    }
}
