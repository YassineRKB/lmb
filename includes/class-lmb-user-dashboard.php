<?php
if (!defined('ABSPATH')) exit;

class LMB_User_Dashboard {
    public static function init() {
        add_shortcode('lmb_user_ads_list', [__CLASS__, 'user_ads_list']);
        add_shortcode('lmb_user_points', [__CLASS__, 'user_points']);
        add_shortcode('lmb_user_publish_button', [__CLASS__, 'user_publish_button']);
    }

    public static function user_ads_list() {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Login required.', 'lmb-core').'</p>';
        
        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'meta_query' => [
                ['key' => 'lmb_client_id', 'value' => get_current_user_id(), 'compare' => '=']
            ],
            'post_status' => 'any',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        ob_start();
        echo '<div class="lmb-user-ads">';
        echo '<h3>'.esc_html__('Your Legal Ads','lmb-core').'</h3>';
        
        if (!$q->have_posts()) {
            echo '<p>'.esc_html__('You have not submitted any legal ads yet.','lmb-core').'</p>';
            echo '</div>';
            return ob_get_clean();
        }
        
        echo '<div class="lmb-ads-list">';
        while($q->have_posts()){ $q->the_post();
            $status = get_field('lmb_status', get_the_ID());
            $ad_type = get_field('ad_type', get_the_ID());
            
            echo '<div class="lmb-ad-item">';
            echo '<div class="lmb-ad-header">';
            echo '<h4>'.esc_html(get_the_title()).'</h4>';
            echo '<span class="lmb-status-badge lmb-status-'.esc_attr(str_replace('_', '-', $status)).'">'.esc_html(ucwords(str_replace('_', ' ', $status))).'</span>';
            echo '</div>';
            
            echo '<div class="lmb-ad-meta">';
            echo '<span class="lmb-ad-type">'.esc_html($ad_type).'</span>';
            echo '<span class="lmb-ad-date">'.esc_html(get_the_date()).'</span>';
            echo '</div>';
            
            echo '<div class="lmb-ad-actions">';
            if ($status === 'draft') {
                echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="lmb-inline-form">';
                wp_nonce_field('lmb_user_publish_ad');
                echo '<input type="hidden" name="action" value="lmb_user_publish_ad" />';
                echo '<input type="hidden" name="ad_id" value="'.get_the_ID().'" />';
                echo '<button type="submit" class="lmb-btn lmb-btn-primary">'.esc_html__('Submit for Review','lmb-core').'</button>';
                echo '</form>';
            } else {
                $pdf = get_field('ad_pdf_url', get_the_ID());
                if ($pdf) {
                    echo '<a class="lmb-btn lmb-btn-secondary" target="_blank" href="'.esc_url($pdf).'">'.esc_html__('Download PDF','lmb-core').'</a>';
                }
                
                $inv = get_post_meta(get_the_ID(), 'ad_invoice_pdf_url', true);
                if ($inv) {
                    echo '<a class="lmb-btn lmb-btn-secondary" target="_blank" href="'.esc_url($inv).'">'.esc_html__('Download Invoice','lmb-core').'</a>';
                }
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        wp_reset_postdata();
        echo '</div>';
        
        // Add styles
        echo '<style>
        .lmb-user-ads { margin: 20px 0; }
        .lmb-ads-list { display: flex; flex-direction: column; gap: 15px; }
        .lmb-ad-item { border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: white; }
        .lmb-ad-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .lmb-ad-header h4 { margin: 0; }
        .lmb-status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .lmb-status-draft { background: #f0f0f1; color: #646970; }
        .lmb-status-pending-review { background: #fcf9e8; color: #996800; }
        .lmb-status-published { background: #edfaef; color: #00a32a; }
        .lmb-status-denied { background: #fcf0f1; color: #d63638; }
        .lmb-ad-meta { display: flex; gap: 15px; margin-bottom: 15px; color: #666; font-size: 14px; }
        .lmb-ad-actions { display: flex; gap: 10px; }
        .lmb-btn { padding: 8px 16px; border: none; border-radius: 4px; text-decoration: none; cursor: pointer; font-size: 14px; }
        .lmb-btn-primary { background: #0073aa; color: white; }
        .lmb-btn-secondary { background: #f0f0f1; color: #646970; }
        .lmb-btn:hover { opacity: 0.8; }
        .lmb-inline-form { display: inline; }
        </style>';
        
        return ob_get_clean();
    }

    public static function user_points() {
        if (!is_user_logged_in()) return '';
        $uid = get_current_user_id();
        $bal = LMB_Points::get($uid);
        $cost= LMB_Points::get_cost_per_ad($uid);
        
        ob_start();
        echo '<div class="lmb-user-points">';
        echo '<h3>'.esc_html__('Your Account','lmb-core').'</h3>';
        echo '<div class="lmb-points-info">';
        echo '<div class="lmb-points-balance">';
        echo '<span class="lmb-points-label">'.esc_html__('Points Balance','lmb-core').'</span>';
        echo '<span class="lmb-points-value">'.number_format($bal).'</span>';
        echo '</div>';
        echo '<div class="lmb-points-cost">';
        echo '<span class="lmb-points-label">'.esc_html__('Cost per Ad','lmb-core').'</span>';
        echo '<span class="lmb-points-value">'.number_format($cost).'</span>';
        echo '</div>';
        echo '</div>';
        
        // Show recent transactions
        $transactions = LMB_Points::get_transactions($uid, 5);
        if ($transactions) {
            echo '<div class="lmb-recent-transactions">';
            echo '<h4>'.esc_html__('Recent Transactions','lmb-core').'</h4>';
            echo '<div class="lmb-transactions-list">';
            foreach ($transactions as $transaction) {
                $amount = (int) $transaction->amount;
                $type_class = $amount >= 0 ? 'credit' : 'debit';
                $amount_display = ($amount >= 0 ? '+' : '') . number_format($amount);
                
                echo '<div class="lmb-transaction-item">';
                echo '<div class="lmb-transaction-info">';
                echo '<span class="lmb-transaction-reason">'.esc_html($transaction->reason).'</span>';
                echo '<span class="lmb-transaction-date">'.esc_html(human_time_diff(strtotime($transaction->created_at), current_time('timestamp')) . ' ago').'</span>';
                echo '</div>';
                echo '<span class="lmb-transaction-amount lmb-amount-'.$type_class.'">'.$amount_display.'</span>';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        
        // Add styles
        echo '<style>
        .lmb-user-points { margin: 20px 0; }
        .lmb-points-info { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .lmb-points-balance, .lmb-points-cost { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background: white; }
        .lmb-points-label { display: block; color: #666; font-size: 14px; margin-bottom: 5px; }
        .lmb-points-value { display: block; font-size: 24px; font-weight: bold; color: #0073aa; }
        .lmb-recent-transactions h4 { margin-bottom: 15px; }
        .lmb-transactions-list { background: white; border: 1px solid #ddd; border-radius: 8px; }
        .lmb-transaction-item { display: flex; justify-content: space-between; align-items: center; padding: 15px; border-bottom: 1px solid #f0f0f1; }
        .lmb-transaction-item:last-child { border-bottom: none; }
        .lmb-transaction-reason { font-weight: 500; }
        .lmb-transaction-date { display: block; color: #666; font-size: 12px; margin-top: 2px; }
        .lmb-transaction-amount { font-weight: bold; }
        .lmb-amount-credit { color: #00a32a; }
        .lmb-amount-debit { color: #d63638; }
        </style>';
        
        return ob_get_clean();
    }

    public static function user_publish_button() {/* kept in user_ads_list */ }
}
