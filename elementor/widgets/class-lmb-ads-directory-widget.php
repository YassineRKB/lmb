<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Ads_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_ads_directory'; }
    public function get_title() { return __('LMB Ads Directory','lmb-core'); }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return ['lmb-widgets']; }

    protected function render() {
        $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        ?>
        <div class="lmb-directory-container">
            <form role="search" method="get" class="lmb-filters-form" action="">
                <input type="search" placeholder="<?php esc_attr_e('Search by keyword...', 'lmb-core'); ?>" name="search" value="<?php echo esc_attr($search_query); ?>">
                
                <select name="type">
                    <option value=""><?php esc_html_e('All Ad Types', 'lmb-core'); ?></option>
                    <?php 
                    $ad_types = ['Constitution - SARL', 'Constitution - SARL AU', 'Modification - Capital', 'Modification - parts', 'Liquidation - definitive'];
                    foreach ($ad_types as $type) {
                        echo '<option value="'.esc_attr($type).'" '.selected($type, $type_filter, false).'>'.esc_html($type).'</option>';
                    }
                    ?>
                </select>
                
                <button type="submit"><?php esc_html_e('Filter', 'lmb-core'); ?></button>
            </form>

            <?php
            $args = [
                'post_type' => 'lmb_legal_ad',
                'post_status' => 'publish',
                'posts_per_page' => 9,
                'paged' => $paged,
                's' => $search_query,
                'meta_query' => [
                    'relation' => 'AND',
                    ['key' => 'lmb_status', 'value' => 'published', 'compare' => '=']
                ],
            ];

            if (!empty($type_filter)) {
                $args['meta_query'][] = ['key' => 'ad_type', 'value' => $type_filter, 'compare' => '='];
            }

            $query = new WP_Query($args);

            if ($query->have_posts()) : ?>
                <div class="lmb-ads-grid">
                    <?php while ($query->have_posts()) : $query->the_post(); 
                        $ad_type = get_post_meta(get_the_ID(), 'ad_type', true);
                        $pdf_url = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                    ?>
                        <article class="lmb-ad-card">
                            <div class="lmb-ad-card-header">
                                <span class="lmb-ad-type-badge"><?php echo esc_html($ad_type); ?></span>
                                <span class="lmb-ad-date"><?php echo get_the_date(); ?></span>
                            </div>
                            <h3 class="lmb-ad-title"><?php the_title(); ?></h3>
                            <div class="lmb-ad-excerpt">
                                <?php echo wp_trim_words(strip_tags(get_post_meta(get_the_ID(), 'full_text', true)), 25, '...'); ?>
                            </div>
                            <div class="lmb-ad-actions">
                                <?php if ($pdf_url): ?>
                                    <a href="<?php echo esc_url($pdf_url); ?>" class="lmb-btn lmb-btn-primary" target="_blank"><?php esc_html_e('Download PDF', 'lmb-core'); ?></a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
                <div class="lmb-pagination">
                    <?php
                    echo paginate_links([
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'total' => $query->max_num_pages,
                        'current' => max(1, $paged),
                        'format' => '?paged=%#%',
                        'prev_text' => __('&laquo; Prev', 'lmb-core'),
                        'next_text' => __('Next &raquo;', 'lmb-core'),
                    ]);
                    ?>
                </div>
            <?php else : ?>
                <div class="lmb-notice"><p><?php esc_html_e('No legal ads found matching your criteria.', 'lmb-core'); ?></p></div>
            <?php endif;
            wp_reset_postdata();
            ?>
        </div>
        <?php
    }
}