<?php
if (!defined('ABSPATH')) exit;

class LMB_User_Dashboard {
    public static function init() {
        // Main list for dashboard
        add_shortcode('lmb_user_ads_list', [__CLASS__, 'render_user_ads_list']);

        // Simple stat shortcodes
        add_shortcode('lmb_user_total_ads', [__CLASS__, 'get_total_ads_count']);
        add_shortcode('lmb_user_balance', [__CLASS__, 'get_user_balance']);

        // Chart shortcode
        add_shortcode('lmb_user_charts', [__CLASS__, 'render_user_charts']);
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

        // Fetch points usage data for the current year
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT MONTH(created_at) AS month, SUM(ABS(amount)) as total 
             FROM {$transactions_table} 
             WHERE user_id = %d AND transaction_type = 'debit' AND YEAR(created_at) = YEAR(CURDATE())
             GROUP BY MONTH(created_at) 
             ORDER BY MONTH(created_at) ASC",
            $user_id
        ));

        $months = [];
        $points_spent = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = date('M', mktime(0, 0, 0, $i, 1));
            $points_spent[$i] = 0;
        }
        foreach ($results as $row) {
            $points_spent[(int)$row->month] = (int)$row->total;
        }

        $chart_data = json_encode(array_values($points_spent));
        $chart_labels = json_encode($months);

        ob_start();
        ?>
        <div class="lmb-chart-container">
            <h4><?php _e('Your Points Usage This Year', 'lmb-core'); ?></h4>
            <canvas id="lmbUserPointsChart"></canvas>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof Chart !== 'undefined') {
                    var ctx = document.getElementById('lmbUserPointsChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: <?php echo $chart_labels; ?>,
                            datasets: [{
                                label: '<?php _e('Points Spent', 'lmb-core'); ?>',
                                data: <?php echo $chart_data; ?>,
                                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                                borderColor: 'rgba(0, 115, 170, 1)',
                                borderWidth: 2,
                                tension: 0.4
                            }]
                        },
                        options: {
                            scales: { y: { beginAtZero: true } },
                            responsive: true,
                            maintainAspectRatio: false
                        }
                    });
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    // Ad List Shortcode (no changes needed from previous version)
    public static function render_user_ads_list() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        
        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'author' => get_current_user_id(),
            'post_status' => ['draft', 'pending_review', 'publish', 'denied'],
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        ob_start();
        echo '<div class="lmb-user-ads-list">';
        echo '<h3>'.esc_html__('Your Recent Legal Ads','lmb-core').'</h3>';
        
        if (!$q->have_posts()) {
            echo '<p>'.esc_html__('You have not submitted any ads yet.','lmb-core').'</p>';
        } else {
            echo '<div class="lmb-ads-list">';
            while($q->have_posts()){ $q->the_post();
                $status = get_post_meta(get_the_ID(), 'lmb_status', true);
                $ad_type = get_post_meta(get_the_ID(), 'ad_type', true);
                
                echo '<div class="lmb-ad-item">';
                    echo '<div class="lmb-ad-header">';
                        echo '<h4>'.esc_html(get_the_title()).'</h4>';
                        echo '<span class="lmb-status-badge lmb-status-'.esc_attr(str_replace('_', '-', $status)).'">'.esc_html(ucwords(str_replace('_', ' ', $status))).'</span>';
                    echo '</div>';
                    echo '<div class="lmb-ad-meta">';
                        echo '<span>'.esc_html($ad_type).'</span> | <span>'.esc_html(get_the_date()).'</span>';
                    echo '</div>';
                     if ($status === 'draft') {
                        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
                            wp_nonce_field('lmb_user_publish_ad', '_wpnonce');
                            echo '<input type="hidden" name="action" value="lmb_user_publish_ad" />';
                            echo '<input type="hidden" name="ad_id" value="'.get_the_ID().'" />';
                            echo '<button type="submit" class="lmb-btn lmb-btn-primary">'.esc_html__('Submit for Review','lmb-core').'</button>';
                        echo '</form>';
                    }
                echo '</div>';
            }
            echo '</div>';
        }
        wp_reset_postdata();
        echo '</div>';
        
        return ob_get_clean();
    }
}