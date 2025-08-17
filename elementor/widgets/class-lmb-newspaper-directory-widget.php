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
        $paged = max(1, (int)($_GET['paged'] ?? 1));
        
        echo '<div class="lmb-directory-header">';
        echo '<h2>' . esc_html__('Newspaper Directory', 'lmb-core') . '</h2>';
        echo '<form method="get" class="lmb-filter-form">';
        echo '<div class="lmb-filter-row">';
        echo '<input type="text" name="s" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Search newspapers...','lmb-core').'" class="lmb-search-input" />';
        echo '<button type="submit" class="lmb-search-btn">'.esc_html__('Search','lmb-core').'</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';

        $q = new WP_Query([
            'post_type' => 'lmb_newspaper',
            'post_status' => 'publish',
            's' => $search,
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        if ($q->have_posts()){
            echo '<div class="lmb-results-info">';
            echo '<p>' . sprintf(
                esc_html__('Showing %d-%d of %d newspapers', 'lmb-core'),
                (($paged - 1) * 12) + 1,
                min($paged * 12, $q->found_posts),
                $q->found_posts
            ) . '</p>';
            echo '</div>';
            
            echo '<div class="lmb-news-grid">';
            while($q->have_posts()){ $q->the_post();
                $pdf_field = get_field('newspaper_pdf', get_the_ID());
                $pdf_url = '';
                
                if ($pdf_field) {
                    if (is_array($pdf_field)) {
                        $pdf_url = $pdf_field['url'] ?? '';
                    } else {
                        $pdf_url = wp_get_attachment_url($pdf_field);
                    }
                }
                
                echo '<article class="lmb-news">';
                echo '<div class="lmb-news-header">';
                echo '<h3 class="lmb-news-title">'.esc_html(get_the_title()).'</h3>';
                echo '<span class="lmb-news-date">'.esc_html(get_the_date()).'</span>';
                echo '</div>';
                
                if (has_post_thumbnail()) {
                    echo '<div class="lmb-news-thumbnail">';
                    echo get_the_post_thumbnail(get_the_ID(), 'medium');
                    echo '</div>';
                }
                
                if (get_the_excerpt()) {
                    echo '<div class="lmb-news-excerpt">';
                    echo '<p>'.esc_html(get_the_excerpt()).'</p>';
                    echo '</div>';
                }
                
                echo '<div class="lmb-news-actions">';
                if ($pdf_url) {
                    echo '<a target="_blank" href="'.esc_url($pdf_url).'" class="lmb-download-btn">'.esc_html__('Download PDF','lmb-core').'</a>';
                }
                echo '<a href="'.esc_url(get_permalink()).'" class="lmb-view-btn">'.esc_html__('View Details','lmb-core').'</a>';
                echo '</div>';
                echo '</article>';
            }
            echo '</div>';
            
            // Pagination
            if ($q->max_num_pages > 1) {
                echo '<div class="lmb-pagination">';
                $big = 999999999;
                echo paginate_links([
                    'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                    'format' => '?paged=%#%',
                    'current' => $paged,
                    'total' => $q->max_num_pages,
                    'prev_text' => '&laquo; ' . esc_html__('Previous', 'lmb-core'),
                    'next_text' => esc_html__('Next', 'lmb-core') . ' &raquo;'
                ]);
                echo '</div>';
            }
            
            wp_reset_postdata();
        } else {
            echo '<div class="lmb-no-results">';
            echo '<p>'.esc_html__('No newspapers found matching your criteria.','lmb-core').'</p>';
            if ($search) {
                echo '<p><a href="?" class="lmb-clear-filters">'.esc_html__('Clear search','lmb-core').'</a></p>';
            }
            echo '</div>';
        }
        
        ?>
        <style>
        .lmb-directory-header { margin-bottom: 30px; }
        .lmb-filter-form { margin-top: 20px; }
        .lmb-filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .lmb-search-input { flex: 1; min-width: 200px; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .lmb-search-btn { padding: 8px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .lmb-news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .lmb-news { border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: white; }
        .lmb-news-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .lmb-news-title { margin: 0; font-size: 18px; }
        .lmb-news-date { color: #666; font-size: 14px; }
        .lmb-news-thumbnail { margin-bottom: 15px; }
        .lmb-news-thumbnail img { width: 100%; height: auto; border-radius: 4px; }
        .lmb-news-excerpt { margin-bottom: 15px; color: #666; }
        .lmb-news-actions { display: flex; gap: 10px; }
        .lmb-download-btn, .lmb-view-btn { padding: 8px 16px; border: 1px solid #0073aa; background: white; color: #0073aa; text-decoration: none; border-radius: 4px; }
        .lmb-download-btn:hover, .lmb-view-btn:hover { background: #0073aa; color: white; }
        .lmb-pagination { text-align: center; margin: 30px 0; }
        .lmb-pagination a, .lmb-pagination span { padding: 8px 12px; margin: 0 4px; border: 1px solid #ddd; text-decoration: none; }
        .lmb-pagination .current { background: #0073aa; color: white; }
        .lmb-no-results { text-align: center; padding: 40px; color: #666; }
        </style>
        <?php
    }
}