<?php
// FILE: elementor/widgets/class-lmb-legal-ads-receipts-widget.php

use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_Receipts_Widget extends Widget_Base {
    public function get_name() { return 'lmb_legal_ads_receipts'; }
    public function get_title() { return __('LMB Legal Ads Receipts', 'lmb-core'); }
    public function get_icon() { return 'eicon-document-file'; }
    public function get_categories() { return ['lmb-user-widgets']; }

    // --- FIX: Removed the unnecessary JavaScript dependency ---
    public function get_script_depends() {
        return [];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('You must be logged in to view your receipts.', 'lmb-core') . '</p></div>';
            return;
        }

        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        // --- FIX: A single, efficient query to get only ads with an accuse attachment ---
        $ads_query = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => [
                [
                    'key' => 'lmb_accuse_attachment_id',
                    'compare' => 'EXISTS',
                ],
                 [
                    'key' => 'lmb_accuse_attachment_id',
                    'value' => '0',
                    'compare' => '!=',
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        ?>
        <div class="lmb-legal-ads-receipts-widget lmb-user-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-receipt"></i> <?php esc_html_e('Legal Ads Receipts', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <?php if ($ads_query->have_posts()): ?>
                    <div class="lmb-table-container">
                        <table class="lmb-data-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Ad Title', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Publication Date', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Cost (Points)', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ads_query->have_posts()): $ads_query->the_post();
                                    $ad = get_post();
                                    $cost = get_post_meta($ad->ID, 'ad_cost_points', true) ?: LMB_Points::get_cost_per_ad($user_id);
                                    $accuse_id = get_post_meta($ad->ID, 'lmb_accuse_attachment_id', true);
                                    $accuse_url = wp_get_attachment_url($accuse_id);
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($ad->post_title); ?></strong></td>
                                        <td><?php echo esc_html(get_the_date('Y-m-d', $ad->ID)); ?></td>
                                        <td><strong><?php echo esc_html($cost); ?></strong></td>
                                        <td>
                                            <?php if ($accuse_url): ?>
                                                <a href="<?php echo esc_url($accuse_url); ?>" class="lmb-btn lmb-btn-sm lmb-btn-primary" target="_blank" download>
                                                    <i class="fas fa-download"></i> <?php esc_html_e('Download Accuse', 'lmb-core'); ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                     <?php if ($ads_query->max_num_pages > 1): ?>
                        <div class="lmb-pagination">
                            <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $paged, 'total' => $ads_query->max_num_pages]); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                     <div class="lmb-no-results-container">
                        <div class="lmb-empty-state">
                            <i class="fas fa-receipt fa-3x"></i>
                            <h4><?php esc_html_e('No Receipts Found', 'lmb-core'); ?></h4>
                            <p><?php esc_html_e('Once your ads are published and our team uploads the official documents, they will appear here.', 'lmb-core'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}