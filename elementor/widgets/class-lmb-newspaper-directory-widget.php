<?php
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Newspaper_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_newspaper_directory'; }
    public function get_title() { return __('LMB Newspaper Directory','lmb-core'); }
    public function get_icon()  { return 'eicon-library-upload'; }
    public function get_categories(){ return ['lmb-cw-admin']; }

    protected function render() {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));
        
        ?>
        <div class="lmb-directory-container">
            <form method="get" class="lmb-filters-form">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search newspapers...','lmb-core'); ?>" />
                <button type="submit"><?php esc_html_e('Search','lmb-core'); ?></button>
            </form>

            <?php
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
                echo '<div class="lmb-news-grid">';
                while($q->have_posts()){ $q->the_post();
                    $pdf_field = get_post_meta(get_the_ID(), 'newspaper_pdf', true);
                    $pdf_url = wp_get_attachment_url($pdf_field);
                    
                    echo '<article class="lmb-news-card">';
                    if (has_post_thumbnail()) {
                        echo '<div class="lmb-news-thumbnail">';
                        the_post_thumbnail('medium_large');
                        echo '</div>';
                    }
                    echo '<div class="lmb-news-content">';
                    echo '<h3 class="lmb-news-title">'.esc_html(get_the_title()).'</h3>';
                    echo '<div class="lmb-news-meta">'.esc_html(get_the_date()).'</div>';
                    
                    if (get_the_excerpt()) {
                        echo '<div class="lmb-news-excerpt"><p>'.esc_html(get_the_excerpt()).'</p></div>';
                    }
                    
                    echo '<div class="lmb-news-actions">';
                    if ($pdf_url) {
                        echo '<a target="_blank" href="'.esc_url($pdf_url).'" class="lmb-btn lmb-btn-primary">'.esc_html__('Download PDF','lmb-core').'</a>';
                    }
                    echo '</div>';
                    echo '</div>';
                    echo '</article>';
                }
                echo '</div>';
                
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
                echo '<div class="lmb-notice"><p>'.esc_html__('No newspapers found matching your criteria.','lmb-core').'</p></div>';
            }
        ?>
        </div>
        <?php
    }
}