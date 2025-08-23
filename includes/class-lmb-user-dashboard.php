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
        
        // Add AJAX handlers for notifications
        add_action('wp_ajax_lmb_mark_notification_read', [__CLASS__, 'ajax_mark_notification_read']);
        add_action('wp_ajax_lmb_mark_all_notifications_read', [__CLASS__, 'ajax_mark_all_notifications_read']);

        // Register shortcodes for lmb-2 widgets
        add_shortcode('lmb_legal_ads_receipts', [__CLASS__, 'render_legal_ads_receipts']);
        add_shortcode('lmb_invoices', [__CLASS__, 'render_invoices']);
        add_shortcode('lmb_packages_editor', [__CLASS__, 'render_packages_editor']);
        add_shortcode('lmb_balance_manipulation', [__CLASS__, 'render_balance_manipulation']);
        add_shortcode('lmb_legal_ads_list', [__CLASS__, 'render_legal_ads_list']);
        add_shortcode('lmb_user_list', [__CLASS__, 'render_user_list']);
    }

    public static function collect_user_stats() {
        if (!is_user_logged_in()) {
            return [
                'points_balance' => 0,
                'ads_total' => 0,
                'ads_pending' => 0,
                'ads_published' => 0,
            ];
        }

        $user_id = get_current_user_id();
        
        // Count posts with specific meta query for status
        $pending_query = new WP_Query([
            'author' => $user_id,
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => [['key' => 'lmb_status', 'value' => 'pending_review']]
        ]);
        
        $published_query = new WP_Query([
            'author' => $user_id,
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [['key' => 'lmb_status', 'value' => 'published']]
        ]);

        return [
            'points_balance' => LMB_Points::get_balance($user_id),
            'ads_total'      => count_user_posts($user_id, 'lmb_legal_ad', true),
            'ads_pending'    => $pending_query->found_posts,
            'ads_published'  => $published_query->found_posts,
        ];
    }
    
    public static function ajax_mark_notification_read() {
        check_ajax_referer('lmb_frontend_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in() || !isset($_POST['notification_id'])) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        $notification_id = sanitize_text_field($_POST['notification_id']);
        
        $notifications = get_user_meta($user_id, 'lmb_notifications', true);
        if (is_array($notifications)) {
            foreach ($notifications as &$notification) {
                if ($notification['id'] == $notification_id) {
                    $notification['read'] = true;
                    break;
                }
            }
            update_user_meta($user_id, 'lmb_notifications', $notifications);
        }
        
        wp_send_json_success();
    }
    
    public static function ajax_mark_all_notifications_read() {
        check_ajax_referer('lmb_frontend_ajax_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error();
        }
        
        $user_id = get_current_user_id();
        $notifications = get_user_meta($user_id, 'lmb_notifications', true);
        
        if (is_array($notifications)) {
            foreach ($notifications as &$notification) {
                $notification['read'] = true;
            }
            update_user_meta($user_id, 'lmb_notifications', $notifications);
        }
        
        wp_send_json_success();
    }

    // This shortcode is now a fallback, the primary display is handled by the Elementor widget.
    public static function render_user_stats() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        ob_start();
        the_widget('LMB_User_Stats_Widget');
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
        <div class="lmb-chart-container" style="height:300px; margin-top: 30px; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">
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
                                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                borderColor: 'rgba(102, 126, 234, 1)',
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

    // Ad List Shortcode
    public static function render_user_ads_list() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        
        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));
        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'author' => $user_id,
            'post_status' => ['draft', 'pending_review', 'publish', 'denied'],
            'posts_per_page' => 4,
            'paged' => $paged,
        ]);
        
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
                                <?php if($status === 'denied'): ?>
                                    <div class="lmb-ad-reason"><strong><?php _e('Reason:', 'lmb-core'); ?></strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'denial_reason', true)); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="lmb-ad-actions">
                                <?php if ($status === 'draft'): ?>
                                    <button class="lmb-btn lmb-btn-sm lmb-submit-for-review-btn" data-ad-id="<?php echo get_the_ID(); ?>">
                                        <?php _e('Submit for Review', 'lmb-core'); ?>
                                    </button>
                                <?php elseif ($status === 'published'): 
                                    $pdf_url = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                                    if ($pdf_url): ?>
                                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-secondary"><?php _e('Download PDF', 'lmb-core'); ?></a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                 <?php if ($q->max_num_pages > 1) {
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
                } ?>
            <?php endif; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
    // Renders the LMB_Legal_Ads_Receipts_Widget via shortcode.
    public static function render_legal_ads_receipts() {
        if (class_exists('LMB_Legal_Ads_Receipts_Widget')) {
            ob_start();
            the_widget('LMB_Legal_Ads_Receipts_Widget');
            return ob_get_clean();
        }
        return 'LMB Legal Ads Receipts Widget not found.';
    }

    // Renders the LMB_Invoices_Widget via shortcode.
    public static function render_invoices() {
        if (class_exists('LMB_Invoices_Widget')) {
            ob_start();
            the_widget('LMB_Invoices_Widget');
            return ob_get_clean();
        }
        return 'LMB Invoices Widget not found.';
    }

    // Renders the LMB_Packages_Editor_Widget via shortcode.
    public static function render_packages_editor() {
        if (class_exists('LMB_Packages_Editor_Widget')) {
            ob_start();
            the_widget('LMB_Packages_Editor_Widget');
            return ob_get_clean();
        }
        return 'LMB Packages Editor Widget not found.';
    }

    // Renders the LMB_Balance_Manipulation_Widget via shortcode.
    public static function render_balance_manipulation() {
        if (class_exists('LMB_Balance_Manipulation_Widget')) {
            ob_start();
            the_widget('LMB_Balance_Manipulation_Widget');
            return ob_get_clean();
        }
        return 'LMB Balance Manipulation Widget not found.';
    }

    
    // Renders the LMB_Legal_Ads_List_Widget via shortcode.
    public static function render_legal_ads_list() {
        if (class_exists('LMB_Legal_Ads_List_Widget')) {
            ob_start();
            the_widget('LMB_Legal_Ads_List_Widget');
            return ob_get_clean();
        }
        return 'LMB Legal Ads List Widget not found.';
    }

    // Renders the LMB_User_List_Widget via shortcode.
    public static function render_user_list() {
        if (class_exists('LMB_User_List_Widget')) {
            ob_start();
            the_widget('LMB_User_List_Widget');
            return ob_get_clean();
        }
        return 'LMB User List Widget not found.';
    }
}
// i am cooked my young padawan, this project is rushed and messy, but it works, for now.