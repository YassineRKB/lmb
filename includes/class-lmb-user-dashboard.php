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
            $status = get_post_meta(get_the_ID(), 'lmb_status', true);
            $ad_type = get_post_meta(get_the_ID(), 'ad_type', true);
            
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
                wp_nonce_field('lmb_user_publish_ad', '_wpnonce');
                echo '<input type="hidden" name="action" value="lmb_user_publish_ad" />';
                echo '<input type="hidden" name="ad_id" value="'.get_the_ID().'" />';
                echo '<button type="submit" class="lmb-btn lmb-btn-primary">'.esc_html__('Submit for Review','lmb-core').'</button>';
                echo '</form>';
            } else {
                $pdf = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
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
        
        return ob_get_clean();
    }

    public static function user_publish_button() {/* kept in user_ads_list */ }
}