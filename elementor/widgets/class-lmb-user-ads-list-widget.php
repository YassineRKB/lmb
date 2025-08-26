<?php
// FILE: elementor/widgets/class-lmb-user-ads-list-widget.php

use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_User_Ads_List_Widget extends Widget_Base {
    // ... (get_name, get_title, etc. remain the same) ...
    public function get_name() { return 'lmb_user_ads_list'; }
    public function get_title() { return __('LMB User Ads List', 'lmb-core'); }
    public function get_icon() { return 'eicon-posts-grid'; }
    public function get_categories() { return ['lmb-user-widgets']; }

    public function get_script_depends() {
        return ['lmb-user-ads-list'];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('Please log in to see your ads.', 'lmb-core') . '</p></div>';
            return;
        }
        ?>
        <div class="lmb-user-ads-list-widget">
            <div class="lmb-tabs-nav">
                <button class="lmb-tab-btn active" data-status="draft"><?php _e('Drafts', 'lmb-core'); ?></button>
                <button class="lmb-tab-btn" data-status="pending_review"><?php _e('Pending Review', 'lmb-core'); ?></button>
                <button class="lmb-tab-btn" data-status="published"><?php _e('Published', 'lmb-core'); ?></button>
            </div>
            <div id="lmb-user-ads-list-container">
                <?php $this->render_ads_for_status('draft'); ?>
            </div>
            <div id="lmb-user-ads-pagination">
                <?php
                // Render initial pagination for the default tab
                $args = [ 'author' => get_current_user_id(), 'post_type' => 'lmb_legal_ad', 'posts_per_page' => 5, 'meta_query' => [['key' => 'lmb_status', 'value' => 'draft']] ];
                $initial_query = new WP_Query($args);
                if ($initial_query->max_num_pages > 1) {
                    for ($i = 1; $i <= $initial_query->max_num_pages; $i++) {
                        echo '<button class="page-btn ' . ($i == 1 ? 'active' : '') . '" data-page="' . $i . '">' . $i . '</button>';
                    }
                }
                wp_reset_postdata();
                ?>
            </div>
        </div>
        <?php
    }

    // --- MADE THIS METHOD PUBLIC ---
    public function render_ads_for_status($status, $paged = 1) {
        $args = [
            'author' => get_current_user_id(),
            'post_type' => 'lmb_legal_ad',
            'posts_per_page' => 5,
            'paged' => $paged,
            'post_status' => ['publish', 'draft', 'pending'],
            'meta_query' => [
                [
                    'key' => 'lmb_status',
                    'value' => $status,
                    'compare' => '=',
                ],
            ]
        ];

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            echo '<div class="lmb-ads-list">';
            while ($query->have_posts()) {
                $query->the_post();
                $ad_status = get_post_meta(get_the_ID(), 'lmb_status', true);
                ?>
                <div class="lmb-ad-item status-<?php echo esc_attr($ad_status); ?>">
                    <div class="lmb-ad-info">
                        <h4 class="lmb-ad-title"><?php the_title(); ?></h4>
                        <div class="lmb-ad-meta">
                            <span class="lmb-ad-status-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $ad_status))); ?></span> | 
                            <span>Submitted: <?php echo get_the_date(); ?></span>
                        </div>
                    </div>
                    <div class="lmb-ad-actions">
                        <?php if ($ad_status === 'draft') : ?>
                            <button class="lmb-submit-review lmb-btn lmb-btn-sm lmb-btn-primary" data-id="<?php echo get_the_ID(); ?>">
                                <i class="fas fa-paper-plane"></i> Submit for Review
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
            }
            echo '</div>';
        } else {
            echo '<div class="lmb-empty-state"><i class="fas fa-file-alt fa-3x"></i><h4>No Ads Found</h4><p>There are no ads with this status.</p></div>';
        }
        wp_reset_postdata();
    }
}