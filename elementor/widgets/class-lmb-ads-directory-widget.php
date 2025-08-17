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
        $paged  = max(1, (int)($_GET['paged'] ?? 1));

        echo '<div class="lmb-directory-header">';
        echo '<h2>' . esc_html__('Legal Ads Directory', 'lmb-core') . '</h2>';
        echo '<form method="get" class="lmb-filter-form">';
        echo '<div class="lmb-filter-row">';
        echo '<input type="text" name="s" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Search ads...','lmb-core').'" class="lmb-search-input" />';
        echo '<select name="type" class="lmb-type-select">';
        echo '<option value="">'.esc_html__('All Types','lmb-core').'</option>';
        
        $ad_types = [
            'Liquidation - definitive',
            'Liquidation - anticipee', 
            'Constitution - SARL',
            'Constitution - SARL AU',
            'Modification - Capital',
            'Modification - parts',
            'Modification - denomination',
            'Modification - seige',
            'Modification - gerant',
            'Modification - objects'
        ];
        
        foreach ($ad_types as $ad_type) {
            $selected = ($type === $ad_type) ? 'selected' : '';
            echo '<option value="'.esc_attr($ad_type).'" '.$selected.'>'.esc_html($ad_type).'</option>';
        }
        
        echo '</select>';
        echo '<button type="submit" class="lmb-search-btn">'.esc_html__('Search','lmb-core').'</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';

        $meta = [];
        if ($type) {
            $meta[] = ['key'=>'ad_type','value'=>$type,'compare'=>'='];
        }
        
        // Only show published ads with published status
        $meta[] = ['key'=>'lmb_status','value'=>'published','compare'=>'='];

        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            's' => $search,
            'meta_query' => $meta,
            'posts_per_page' => 12,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        if ($q->have_posts()){
            echo '<div class="lmb-results-info">';
            echo '<p>' . sprintf(
                esc_html__('Showing %d-%d of %d results', 'lmb-core'),
                (($paged - 1) * 12) + 1,
                min($paged * 12, $q->found_posts),
                $q->found_posts
            ) . '</p>';
            echo '</div>';
            
            echo '<div class="lmb-ads-grid">';
            while($q->have_posts()){ $q->the_post();
                $full = get_field('full_text', get_the_ID());
                $ad_type = get_field('ad_type', get_the_ID());
                $pdf = get_field('ad_pdf_url', get_the_ID());
                
                echo '<article class="lmb-ad">';
                echo '<div class="lmb-ad-header">';
                echo '<h3 class="lmb-ad-title">'.esc_html(get_the_title()).'</h3>';
                echo '<span class="lmb-ad-type-badge">'.esc_html($ad_type).'</span>';
                echo '</div>';
                
                echo '<div class="lmb-ad-meta">';
                echo '<span class="lmb-ad-date">'.esc_html(get_the_date()).'</span>';
                echo '</div>';
                
                echo '<div class="lmb-ad-content">';
                // Show excerpt of content
                $excerpt = wp_trim_words(strip_tags($full), 30, '...');
                echo '<p>'.esc_html($excerpt).'</p>';
                echo '</div>';
                
                echo '<div class="lmb-ad-actions">';
                if ($pdf) {
                    echo '<a target="_blank" href="'.esc_url($pdf).'" class="lmb-download-btn">'.esc_html__('Download PDF','lmb-core').'</a>';
                }
                echo '<button class="lmb-view-details" data-id="'.get_the_ID().'">'.esc_html__('View Details','lmb-core').'</button>';
                echo '</div>';
                
                // Hidden full content for modal
                echo '<div class="lmb-ad-full-content" style="display:none;">'.wp_kses_post($full).'</div>';
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
            echo '<p>'.esc_html__('No legal ads found matching your criteria.','lmb-core').'</p>';
            if ($search || $type) {
                echo '<p><a href="?" class="lmb-clear-filters">'.esc_html__('Clear filters','lmb-core').'</a></p>';
            }
            echo '</div>';
        }
        
        // Add modal for viewing full content
        echo '<div id="lmb-ad-modal" class="lmb-modal" style="display:none;">';
        echo '<div class="lmb-modal-content">';
        echo '<span class="lmb-modal-close">&times;</span>';
        echo '<div class="lmb-modal-body"></div>';
        echo '</div>';
        echo '</div>';
        
        // Add JavaScript for modal
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.lmb-view-details').on('click', function() {
                var adId = $(this).data('id');
                var fullContent = $(this).closest('.lmb-ad').find('.lmb-ad-full-content').html();
                var title = $(this).closest('.lmb-ad').find('.lmb-ad-title').text();
                
                $('#lmb-ad-modal .lmb-modal-body').html('<h3>' + title + '</h3>' + fullContent);
                $('#lmb-ad-modal').show();
            });
            
            $('.lmb-modal-close, #lmb-ad-modal').on('click', function(e) {
                if (e.target === this) {
                    $('#lmb-ad-modal').hide();
                }
            });
        });
        </script>
        
        <style>
        .lmb-directory-header { margin-bottom: 30px; }
        .lmb-filter-form { margin-top: 20px; }
        .lmb-filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .lmb-search-input, .lmb-type-select { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; }
        .lmb-search-input { flex: 1; min-width: 200px; }
        .lmb-search-btn { padding: 8px 20px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .lmb-ads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .lmb-ad { border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: white; }
        .lmb-ad-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .lmb-ad-title { margin: 0; font-size: 18px; }
        .lmb-ad-type-badge { background: #e7f3ff; color: #0073aa; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .lmb-ad-meta { margin-bottom: 15px; color: #666; font-size: 14px; }
        .lmb-ad-actions { display: flex; gap: 10px; margin-top: 15px; }
        .lmb-download-btn, .lmb-view-details { padding: 8px 16px; border: 1px solid #0073aa; background: white; color: #0073aa; text-decoration: none; border-radius: 4px; cursor: pointer; }
        .lmb-download-btn:hover, .lmb-view-details:hover { background: #0073aa; color: white; }
        .lmb-modal { position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .lmb-modal-content { background: white; margin: 5% auto; padding: 20px; width: 80%; max-width: 800px; border-radius: 8px; position: relative; max-height: 80vh; overflow-y: auto; }
        .lmb-modal-close { position: absolute; right: 20px; top: 15px; font-size: 28px; cursor: pointer; }
        .lmb-pagination { text-align: center; margin: 30px 0; }
        .lmb-pagination a, .lmb-pagination span { padding: 8px 12px; margin: 0 4px; border: 1px solid #ddd; text-decoration: none; }
        .lmb-pagination .current { background: #0073aa; color: white; }
        .lmb-no-results { text-align: center; padding: 40px; color: #666; }
        </style>
        <?php
    }
}
