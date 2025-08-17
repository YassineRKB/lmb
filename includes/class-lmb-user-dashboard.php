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
            'author'    => get_current_user_id(),
            'post_status'=> ['draft','pending_review','publish','lmb_denied'],
            'posts_per_page' => -1
        ]);
        ob_start();
        echo '<table class="lmb-table"><thead><tr><th>'.esc_html__('Title','lmb-core').'</th><th>'.esc_html__('Status','lmb-core').'</th><th>'.esc_html__('Actions','lmb-core').'</th></tr></thead><tbody>';
        while($q->have_posts()){ $q->the_post();
            $status = get_post_status();
            echo '<tr>';
            echo '<td>'.esc_html(get_the_title()).'</td>';
            echo '<td>'.esc_html(get_post_status_object($status)->label ?? $status).'</td>';
            echo '<td>';
            if ($status==='draft') {
                echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
                wp_nonce_field('lmb_user_publish_ad');
                echo '<input type="hidden" name="action" value="lmb_user_publish_ad" />';
                echo '<input type="hidden" name="ad_id" value="'.get_the_ID().'" />';
                echo '<button type="submit" class="button button-primary">'.esc_html__('Request Publish','lmb-core').'</button>';
                echo '</form>';
            } else {
                $pdf = get_post_meta(get_the_ID(), 'ad_pdf_url', true);
                if ($pdf) echo '<a class="button" target="_blank" href="'.esc_url($pdf).'">'.esc_html__('Download Ad PDF','lmb-core').'</a> ';
                $inv = get_post_meta(get_the_ID(), 'ad_invoice_pdf_url', true);
                if ($inv) echo '<a class="button" target="_blank" href="'.esc_url($inv).'">'.esc_html__('Download Invoice','lmb-core').'</a>';
            }
            echo '</td></tr>';
        }
        wp_reset_postdata();
        echo '</tbody></table>';
        return ob_get_clean();
    }

    public static function user_points() {
        if (!is_user_logged_in()) return '';
        $uid = get_current_user_id();
        $bal = LMB_Points::get_balance($uid);
        $cost= LMB_Points::get_cost_per_ad($uid);
        return '<p>'.sprintf(__('Points: %d | Cost per Ad: %d', 'lmb-core'), $bal, $cost).'</p>';
    }

    public static function user_publish_button() {/* kept in user_ads_list */ }
}
