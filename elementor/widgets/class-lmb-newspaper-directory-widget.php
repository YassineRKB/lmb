<?php
// FILE: elementor/widgets/class-lmb-newspaper-directory-widget.php

use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Newspaper_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_newspaper_directory'; }
    public function get_title() { return __('LMB Newspaper Directory','lmb-core'); }
    public function get_icon()  { return 'eicon-library-upload'; }
    
    public function get_categories(){ return ['lmb-user-widgets']; }
    
    public function get_script_depends() { return []; }

    public function get_style_depends() { return ['lmb-core']; }


    protected function render() {
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));
        
        ?>
        <div class="lmb-directory-container">
            <form method="get" class="lmb-filters-form">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search newspapers by name...','lmb-core'); ?>" />
                <button type="submit"><?php esc_html_e('Search','lmb-core'); ?></button>
            </form>

            <?php
            $q = new WP_Query([
                'post_type' => 'lmb_newspaper',
                'post_status' => 'publish',
                's' => $search,
                'posts_per_page' => 15,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            if ($q->have_posts()){
                echo '<div class="lmb-newspaper-list">';
                while($q->have_posts()){ 
                    $q->the_post();
                    $pdf_id = get_post_meta(get_the_ID(), 'newspaper_pdf', true);
                    $pdf_url = wp_get_attachment_url($pdf_id);
                    
                    echo '<div class="lmb-newspaper-list-item">';
                    echo '<div class="lmb-newspaper-info">';
                    echo '<strong>' . esc_html(get_the_title()) . '</strong>';
                    echo '<span>' . esc_html(get_the_date()) . '</span>';
                    echo '</div>';
                    echo '<div class="lmb-actions-cell">';
                    if ($pdf_url) {
                        echo '<a target="_blank" href="'.esc_url($pdf_url).'" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-eye"></i> ' . esc_html__('View','lmb-core') . '</a>';
                        echo '<a target="_blank" href="'.esc_url($pdf_url).'" class="lmb-btn lmb-btn-sm lmb-btn-primary" download><i class="fas fa-download"></i> ' . esc_html__('Download','lmb-core') . '</a>';
                    } else {
                        echo '<em>' . esc_html__('Not Available', 'lmb-core') . '</em>';
                    }
                    echo '</div>';
                    echo '</div>';
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
                echo '<div class="lmb-empty-state" style="padding: 40px 0;"><h4>' . esc_html__('No Newspapers Found', 'lmb-core') . '</h4><p>' . esc_html__('No newspapers matched your search criteria.', 'lmb-core') . '</p></div>';
            }
        ?>
        </div>
        <?php
    }
}