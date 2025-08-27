<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Ads_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_ads_directory'; }
    public function get_title() { return __('LMB Ads Directory','lmb-core'); }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return ['lmb-user-widgets']; }

    protected function render() {
        if (isset($_GET['legal-ad'])) {
            $ad_id = intval(substr($_GET['legal-ad'], 0, strpos($_GET['legal-ad'], '-')));
            $this->render_single_ad($ad_id);
        } else {
            $this->render_ads_table();
        }
    }

    protected function render_single_ad($ad_id) {
        $ad = get_post($ad_id);
        if ($ad && $ad->post_type == 'lmb_legal_ad') {
            $pdf_url = get_post_meta($ad_id, 'ad_pdf_url', true);
            ?>
            <div class="lmb-single-ad-container">
                <div class="lmb-single-ad-header">
                    <h1><?php echo esc_html($ad->post_title); ?></h1>
                    <div class="lmb-single-ad-actions">
                        <a href="<?php echo esc_url(get_permalink(get_page_by_path('announces'))); ?>" class="lmb-btn lmb-btn-secondary">
                            <i class="fas fa-list"></i> <?php esc_html_e('View All Legal Ads', 'lmb-core'); ?>
                        </a>
                        <?php if ($pdf_url): ?>
                            <a href="<?php echo esc_url($pdf_url); ?>" class="lmb-btn lmb-btn-primary" target="_blank">
                                <i class="fas fa-download"></i> <?php esc_html_e('Download PDF', 'lmb-core'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="lmb-single-ad-content">
                    <?php echo wp_kses_post(get_post_meta($ad_id, 'full_text', true)); ?>
                </div>
            </div>
            <?php
        } else {
            echo '<p>' . esc_html__('Legal ad not found.', 'lmb-core') . '</p>';
        }
    }

    protected function render_ads_table() {
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
                    $ad_types = LMB_Admin::get_unique_ad_types();
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
                'posts_per_page' => 15,
                'paged' => $paged,
                's' => $search_query,
                'meta_query' => [
                    'relation' => 'AND',
                ],
            ];

            if (!empty($type_filter)) {
                $args['meta_query'][] = ['key' => 'ad_type', 'value' => $type_filter, 'compare' => '='];
            }

            $query = new WP_Query($args);

            if ($query->have_posts()) : ?>
                <div class="lmb-table-container">
                    <table class="lmb-data-table lmb-ads-directory-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ad Title', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Ad Type', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Date', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($query->have_posts()) : $query->the_post(); 
                                $ad_type = get_post_meta(get_the_ID(), 'ad_type', true);
                                $pdf_url = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                            ?>
                                <tr>
                                    <td><?php the_title(); ?></td>
                                    <td><?php echo esc_html($ad_type); ?></td>
                                    <td><?php echo get_the_date(); ?></td>
                                    <td class="lmb-actions-cell">
                                        <a href="<?php echo esc_url(get_permalink()); ?>" class="lmb-btn lmb-btn-sm lmb-btn-secondary">
                                            <i class="fas fa-eye"></i> <?php esc_html_e('View', 'lmb-core'); ?>
                                        </a>
                                        <?php if ($pdf_url): ?>
                                            <a href="<?php echo esc_url($pdf_url); ?>" class="lmb-btn lmb-btn-sm lmb-btn-primary" target="_blank">
                                                <i class="fas fa-download"></i> <?php esc_html_e('Download', 'lmb-core'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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