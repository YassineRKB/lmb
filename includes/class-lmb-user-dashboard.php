<?php
if (!defined('ABSPATH')) exit;

class LMB_User_Dashboard {
    public static function init() {
        // Register all user-facing shortcodes here
        add_shortcode('lmb_user_stats', [__CLASS__, 'render_user_stats']);
        add_shortcode('lmb_user_ads_list', [__CLASS__, 'render_user_ads_list']);
        add_shortcode('lmb_user_total_ads', [__CLASS__, 'get_total_ads_count']);
        add_shortcode('lmb_user_balance', [__CLASS__, 'get_user_balance']);
        
        // NEW Chart Shortcodes
        add_shortcode('lmb_chart_points_consumption', [__CLASS__, 'render_points_consumption_chart']);
        add_shortcode('lmb_chart_published_ads', [__CLASS__, 'render_published_ads_chart']);
        add_shortcode('lmb_chart_draft_ads', [__CLASS__, 'render_draft_ads_chart']);

        // Register shortcodes for lmb-2 widgets
        add_shortcode('lmb_legal_ads_receipts', [__CLASS__, 'render_legal_ads_receipts']);
        add_shortcode('lmb_invoices', [__CLASS__, 'render_invoices']);
        add_shortcode('lmb_packages_editor', [__CLASS__, 'render_packages_editor']);
        add_shortcode('lmb_balance_manipulation', [__CLASS__, 'render_balance_manipulation']);
        add_shortcode('lmb_legal_ads_list', [__CLASS__, 'render_legal_ads_list']);
        add_shortcode('lmb_user_list', [__CLASS__, 'render_user_list']);
    }

    // --- REVISED FUNCTION ---
    public static function collect_user_stats() {
        if (!is_user_logged_in()) return [];
        
        global $wpdb;
        $user_id = get_current_user_id();

        $stats = [];
        $stats['points_balance'] = LMB_Points::get_balance($user_id);
        
        // --- NEW: Calculate Remaining Ads Quota ---
        $cost_per_ad = LMB_Points::get_cost_per_ad($user_id);
        // Use default of 10 if cost is 0 to prevent division by zero error.
        $cost_per_ad = ($cost_per_ad > 0) ? $cost_per_ad : 10; 
        $stats['remaining_ads'] = floor($stats['points_balance'] / $cost_per_ad);

        $stats['ads_total'] = count_user_posts($user_id, 'lmb_legal_ad', true);
        
        // Count ads by custom status
        $stats['ads_pending'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_author = %d AND p.post_type = 'lmb_legal_ad' AND pm.meta_key = 'lmb_status' AND pm.meta_value = 'pending_review'", $user_id));
        $stats['ads_published'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_author = %d AND p.post_type = 'lmb_legal_ad' AND pm.meta_key = 'lmb_status' AND pm.meta_value = 'published'", $user_id));

        return $stats;
    }
    
    public static function render_user_stats() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        ob_start();
        the_widget('LMB_User_Stats_Widget');
        return ob_get_clean();
    }
    
    public static function get_total_ads_count() {
        if (!is_user_logged_in()) return '0';
        return count_user_posts(get_current_user_id(), 'lmb_legal_ad');
    }

    public static function get_user_balance() {
        if (!is_user_logged_in()) return '0';
        return number_format(LMB_Points::get_balance(get_current_user_id()));
    }

    // Chart Shortcode 1: Points Consumption
    public static function render_points_consumption_chart() {
        if (!is_user_logged_in()) return '';
        global $wpdb;
        $user_id = get_current_user_id();
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(created_at) AS month, SUM(ABS(amount)) as total 
             FROM {$wpdb->prefix}lmb_points_transactions 
             WHERE user_id = %d AND amount < 0 AND YEAR(created_at) = YEAR(CURDATE())
             GROUP BY MONTH(created_at) ORDER BY MONTH(created_at) ASC",
            $user_id
        ));
        return self::generate_chart_html('lmbPointsChart', 'Points Usage This Year', 'Points Spent', $results);
    }

    // Chart Shortcode 2: Published Ads
    public static function render_published_ads_chart() {
        if (!is_user_logged_in()) return '';
        global $wpdb;
        $user_id = get_current_user_id();
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(post_date) AS month, COUNT(ID) as total 
             FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'lmb_legal_ad' AND post_status = 'publish' AND YEAR(post_date) = YEAR(CURDATE())
             GROUP BY MONTH(post_date) ORDER BY MONTH(post_date) ASC",
            $user_id
        ));
        return self::generate_chart_html('lmbPublishedAdsChart', 'Published Ads This Year', 'Ads Published', $results, 'rgba(75, 192, 192, 1)', 'rgba(75, 192, 192, 0.1)');
    }

    // Chart Shortcode 3: Draft Ads
    public static function render_draft_ads_chart() {
        if (!is_user_logged_in()) return '';
        global $wpdb;
        $user_id = get_current_user_id();
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(post_date) AS month, COUNT(ID) as total 
             FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'lmb_legal_ad' AND post_status = 'draft' AND YEAR(post_date) = YEAR(CURDATE())
             GROUP BY MONTH(post_date) ORDER BY MONTH(post_date) ASC",
            $user_id
        ));
        return self::generate_chart_html('lmbDraftAdsChart', 'Draft Ads This Year', 'Ads Created', $results, 'rgba(255, 159, 64, 1)', 'rgba(255, 159, 64, 0.1)');
    }

    // Helper function to generate chart markup
    private static function generate_chart_html($canvas_id, $title, $label, $results, $border_color = 'rgba(102, 126, 234, 1)', $bg_color = 'rgba(102, 126, 234, 0.1)') {
        $months = array_map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)), range(1, 12));
        $data = array_fill(0, 12, 0);
        foreach ($results as $row) {
            $data[(int)$row->month - 1] = (int)$row->total;
        }

        ob_start();
        ?>
        <div class="lmb-chart-container" style="height:300px; margin-top: 30px; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
            <h4><?php echo esc_html($title); ?></h4>
            <canvas id="<?php echo esc_attr($canvas_id); ?>"></canvas>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Chart !== 'undefined' && document.getElementById('<?php echo esc_js($canvas_id); ?>')) {
                    var ctx = document.getElementById('<?php echo esc_js($canvas_id); ?>').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($months); ?>,
                            datasets: [{
                                label: '<?php echo esc_js($label); ?>',
                                data: <?php echo json_encode(array_values($data)); ?>,
                                backgroundColor: '<?php echo esc_js($bg_color); ?>',
                                borderColor: '<?php echo esc_js($border_color); ?>',
                                borderWidth: 2,
                                tension: 0.4
                            }]
                        },
                        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }, responsive: true, maintainAspectRatio: false }
                    });
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }


    public static function render_user_ads_list() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        
        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));
        $q = new WP_Query(['post_type' => 'lmb_legal_ad', 'author' => $user_id, 'post_status' => ['draft', 'publish'], 'posts_per_page' => 5, 'paged' => $paged, 'meta_query' => [['key' => 'lmb_status', 'compare' => 'IN', 'value' => ['draft', 'pending_review', 'published', 'denied']]] ]);
        
        ob_start();
        ?>
        <div class="lmb-user-ads-list-wrapper">
            <h3><?php esc_html_e('Your Recent Legal Ads', 'lmb-core'); ?></h3>
            <?php if (!$q->have_posts()): ?>
                <div class="lmb-notice"><p><?php esc_html_e('You have not submitted any ads yet.', 'lmb-core'); ?></p></div>
            <?php else: ?>
                <div class="lmb-user-ads-list">
                    <?php while($q->have_posts()): $q->the_post();
                        $status = get_post_meta(get_the_ID(), 'lmb_status', true);
                        ?>
                        <div class="lmb-user-ad-item status-<?php echo esc_attr($status); ?>">
                            <div class="lmb-ad-info">
                                <span class="lmb-ad-status"><?php echo esc_html(str_replace('_', ' ', $status)); ?></span>
                                <h4 class="lmb-ad-title"><?php the_title(); ?></h4>
                                <div class="lmb-ad-meta"><?php echo get_the_date(); ?></div>
                                <?php if($status === 'denied' && ($reason = get_post_meta(get_the_ID(), 'denial_reason', true))): ?>
                                    <div class="lmb-ad-reason"><strong><?php _e('Reason:', 'lmb-core'); ?></strong> <?php echo esc_html($reason); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="lmb-ad-actions">
                                <?php if (in_array($status, ['draft', 'denied'])): ?>
                                    <button class="lmb-btn lmb-btn-sm lmb-submit-for-review-btn" data-ad-id="<?php echo get_the_ID(); ?>">
                                        <i class="fas fa-paper-plane"></i> <?php _e('Submit for Review', 'lmb-core'); ?>
                                    </button>
                                <?php elseif ($status === 'pending_review'): ?>
                                    <span><?php _e('Awaiting Review', 'lmb-core'); ?></span>
                                <?php elseif ($status === 'published'): 
                                    $pdf_url = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                                    if ($pdf_url): ?>
                                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><i class="fas fa-download"></i> <?php _e('Download PDF', 'lmb-core'); ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                 <?php if ($q->max_num_pages > 1) {
                    echo '<div class="lmb-pagination">';
                    echo paginate_links(['base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))), 'format' => '?paged=%#%', 'current' => $paged, 'total' => $q->max_num_pages, 'prev_text' => '&laquo; ' . __('Previous'), 'next_text' => __('Next') . ' &raquo;']);
                    echo '</div>';
                } ?>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function render_legal_ads_receipts() {
        ob_start(); the_widget('LMB_Legal_Ads_Receipts_Widget'); return ob_get_clean();
    }

    public static function render_invoices() {
        ob_start(); the_widget('LMB_Invoices_Widget'); return ob_get_clean();
    }

    public static function render_packages_editor() {
        ob_start(); the_widget('LMB_Packages_Editor_Widget'); return ob_get_clean();
    }

    public static function render_balance_manipulation() {
        ob_start(); the_widget('LMB_Balance_Manipulation_Widget'); return ob_get_clean();
    }
    
    public static function render_legal_ads_list() {
        ob_start(); the_widget('LMB_Legal_Ads_List_Widget'); return ob_get_clean();
    }

    public static function render_user_list() {
        ob_start(); the_widget('LMB_User_List_Widget'); return ob_get_clean();
    }
}