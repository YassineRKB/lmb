<?php
if (!defined('ABSPATH')) exit;

class LMB_User_Dashboard {
    public static function init() {
        // Register all user-facing shortcodes here
        add_shortcode('lmb_user_stats', [__CLASS__, 'render_user_stats']);
        add_shortcode('lmb_user_charts', [__CLASS__, 'render_user_charts']);
        add_shortcode('lmb_user_ads_list', [__CLASS__, 'render_user_ads_list']);
        add_shortcode('lmb_user_total_ads', [__CLASS__, 'get_total_ads_count']);
        add_shortcode('lmb_user_balance', [__CLASS__, 'get_user_balance']);
    }

    // Function to render the stats block
    public static function render_user_stats() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Please log in to see your stats.','lmb-core').'</p>';

        $user_id = get_current_user_id();
        $user = get_userdata($user_id);
        
        ob_start();
        ?>
        <div class="lmb-user-stats-widget">
            <div class="lmb-user-welcome">
                <h2><?php printf(__('Welcome back, %s!', 'lmb-core'), esc_html($user->display_name)); ?></h2>
            </div>
            <div class="lmb-stats-grid">
                <div class="lmb-stat-card">
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo do_shortcode('[lmb_user_balance]'); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Current Points Balance','lmb-core'); ?></div>
                    </div>
                </div>
                 <div class="lmb-stat-card">
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo do_shortcode('[lmb_user_total_ads]'); ?></div>
                        <div class="lmb-stat-label"><?php esc_html_e('Total Ads Submitted','lmb-core'); ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Simple stat: Total Ads
    public static function get_total_ads_count() {
        if (!is_user_logged_in()) return '0';
        return count_user_posts(get_current_user_id(), 'lmb_legal_ad');
    }

    // Simple stat: Points Balance
    public static function get_user_balance() {
        if (!is_user_logged_in()) return '0';
        return number_format(LMB_Points::get_balance(get_current_user_id()));
    }

    // Chart Shortcode
    public static function render_user_charts() {
        if (!is_user_logged_in()) return '';
        
        global $wpdb;
        $user_id = get_current_user_id();
        $transactions_table = $wpdb->prefix . 'lmb_points_transactions';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(created_at) AS month, SUM(ABS(amount)) as total 
             FROM {$transactions_table} 
             WHERE user_id = %d AND transaction_type = 'debit' AND YEAR(created_at) = YEAR(CURDATE())
             GROUP BY MONTH(created_at) ORDER BY MONTH(created_at) ASC",
            $user_id
        ));

        $months = array_map(fn($m) => date('M', mktime(0, 0, 0, $m, 1)), range(1, 12));
        $points_spent = array_fill(1, 12, 0);
        foreach ($results as $row) {
            $points_spent[(int)$row->month] = (int)$row->total;
        }

        ob_start();
        ?>
        <div class="lmb-chart-container" style="height:300px; margin-top: 30px;">
            <h4><?php _e('Your Points Usage This Year', 'lmb-core'); ?></h4>
            <canvas id="lmbUserPointsChart"></canvas>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Chart !== 'undefined' && document.getElementById('lmbUserPointsChart')) {
                    var ctx = document.getElementById('lmbUserPointsChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo json_encode($months); ?>,
                            datasets: [{
                                label: '<?php _e('Points Spent', 'lmb-core'); ?>',
                                data: <?php echo json_encode(array_values($points_spent)); ?>,
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                borderColor: 'rgba(0, 115, 170, 1)',
                                borderWidth: 2,
                                tension: 0.4
                            }]
                        },
                        options: { scales: { y: { beginAtZero: true } }, responsive: true, maintainAspectRatio: false }
                    });
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    // Ad List Shortcode (Redesigned)
    public static function render_user_ads_list() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        
        $user_id = get_current_user_id();
        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'author' => $user_id,
            'post_status' => ['draft', 'pending_review', 'publish', 'denied'],
            'posts_per_page' => 10,
        ]);
        
        ob_start();
        ?>
        <div class="lmb-user-ads-list-wrapper">
            <h3><?php esc_html_e('Your Recent Legal Ads', 'lmb-core'); ?></h3>
            <?php if (!$q->have_posts()): ?>
                <p><?php esc_html_e('You have not submitted any ads yet.', 'lmb-core'); ?></p>
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
                                <?php if($status === 'denied'): ?>
                                    <div class="lmb-ad-reason"><strong><?php _e('Reason:', 'lmb-core'); ?></strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'denial_reason', true)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="lmb-ad-actions">
                                <?php if ($status === 'draft'): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <input type="hidden" name="action" value="lmb_user_publish_ad" />
                                        <input type="hidden" name="ad_id" value="<?php echo get_the_ID(); ?>" />
                                        <?php wp_nonce_field('lmb_user_publish_ad'); ?>
                                        <button type="submit" class="lmb-btn-sm"><?php _e('Submit for Review', 'lmb-core'); ?></button>
                                    </form>
                                <?php elseif ($status === 'published'): 
                                    $pdf_url = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                                    if ($pdf_url): ?>
                                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="lmb-btn-sm lmb-btn-secondary"><?php _e('Download PDF', 'lmb-core'); ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}