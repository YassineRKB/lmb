<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Admin_Actions_Widget extends Widget_Base {
    public function get_name() { return 'lmb_admin_actions'; }
    public function get_title(){ return __('LMB Admin Actions & Feeds','lmb-core'); }
    public function get_icon() { return 'eicon-dual-button'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!current_user_can('manage_options')) return;

        wp_enqueue_script('jquery');
        ?>
        <div class="lmb-admin-actions-widget">
            <div class="lmb-tabs-nav">
                <button class="lmb-tab-btn active" data-tab="feed">
                    <i class="fas fa-stream"></i> <?php _e('Activity Feed', 'lmb-core'); ?>
                </button>
                <button class="lmb-tab-btn" data-tab="actions">
                    <i class="fas fa-bolt"></i> <?php _e('Quick Actions', 'lmb-core'); ?>
                </button>
                <button class="lmb-tab-btn" data-tab="pending-ads">
                    <i class="fas fa-clock"></i> <?php _e('Pending Ads', 'lmb-core'); ?>
                    <span class="lmb-tab-badge" id="pending-ads-count">0</span>
                </button>
                <button class="lmb-tab-btn" data-tab="pending-payments">
                    <i class="fas fa-money-check-alt"></i> <?php _e('Pending Payments', 'lmb-core'); ?>
                    <span class="lmb-tab-badge" id="pending-payments-count">0</span>
                </button>
            </div>

            <div class="lmb-tab-content">
                <div id="lmb-tab-content-area">
                    </div>
            </div>
        </div>

        <style>
            /* All your existing styles remain unchanged */
            .lmb-admin-actions-widget {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.08);
                overflow: hidden;
            }
            .lmb-tabs-nav {
                display: flex;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-bottom: 1px solid #e9ecef;
            }
            .lmb-tab-btn {
                flex: 1;
                padding: 15px 20px;
                border: none;
                background: transparent;
                color: rgba(255,255,255,0.8);
                cursor: pointer;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                font-weight: 500;
                position: relative;
            }
            .lmb-tab-btn:hover {
                background: rgba(255,255,255,0.1);
                color: white;
            }
            .lmb-tab-btn.active {
                background: rgba(255,255,255,0.2);
                color: white;
                border-bottom: 3px solid white;
            }
            .lmb-tab-badge {
                background: #e53e3e;
                color: white;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 11px;
                font-weight: 600;
                margin-left: 5px;
            }
            .lmb-tab-content {
                padding: 20px;
                min-height: 400px;
            }
            .lmb-loading {
                text-align: center;
                padding: 40px;
                color: #6c757d;
            }
            .lmb-actions-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
            }
            .lmb-action-card {
                background: #f8f9fa;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                transition: all 0.3s ease;
            }
            .lmb-action-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
            .lmb-action-btn {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 12px 20px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 500;
                transition: all 0.3s ease;
                border: none;
                cursor: pointer;
                width: 100%;
                justify-content: center;
            }
            .lmb-action-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            }
            .lmb-feed-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                padding: 15px 0;
                border-bottom: 1px solid #f5f5f5;
                gap: 15px;
            }
            .lmb-feed-item:last-child {
                border-bottom: none;
            }
            .lmb-feed-content {
                flex: 1;
            }
            .lmb-feed-title {
                text-decoration: none;
                font-weight: 600;
                color: #2271b1;
                display: block;
                margin-bottom: 5px;
                font-size: 14px;
            }
            .lmb-feed-title:hover {
                color: #135e96;
            }
            .lmb-feed-meta {
                font-size: 12px;
                color: #787c82;
                display: flex;
                align-items: center;
                gap: 5px;
            }
            .lmb-feed-actions {
                display: flex;
                gap: 5px;
            }
            .lmb-feed-empty {
                text-align: center;
                padding: 40px 20px;
                color: #50575e;
            }
            .lmb-feed-empty i {
                color: #4ab866;
                font-size: 24px;
                margin-bottom: 10px;
                display: block;
            }
            @media (max-width: 768px) {
                .lmb-tabs-nav {
                    flex-wrap: wrap;
                }
                .lmb-tab-btn {
                    flex: 1 1 50%;
                    min-width: 120px;
                }
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Load initial content (feed)
            loadTabContent('feed');

            // Tab switching
            $('.lmb-tab-btn').on('click', function() {
                const tab = $(this).data('tab');
                $('.lmb-tab-btn').removeClass('active');
                $(this).addClass('active');
                loadTabContent(tab);
            });

            function loadTabContent(tab) {
                $('#lmb-tab-content-area').html('<div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
                
                // --- FIX APPLIED ---
                // Using lmbAdmin.ajaxurl ensures consistency with the admin-specific nonce.
                $.post(lmbAdmin.ajaxurl, {
                    action: 'lmb_load_admin_tab',
                    nonce: lmbAdmin.nonce,
                    tab: tab
                }, function(response) {
                    if (response.success) {
                        $('#lmb-tab-content-area').html(response.data.content);
                        if (response.data.pending_ads_count !== undefined) {
                            $('#pending-ads-count').text(response.data.pending_ads_count);
                        }
                        if (response.data.pending_payments_count !== undefined) {
                            $('#pending-payments-count').text(response.data.pending_payments_count);
                        }
                    } else {
                        $('#lmb-tab-content-area').html('<div class="lmb-notice lmb-notice-error"><p>Error loading content</p></div>');
                    }
                });
            }

            // Handle approve/deny actions
            $(document).on('click', '.lmb-ad-action', function(e) {
                e.preventDefault();
                const button = $(this);
                const adId = button.data('id');
                const ad_action = button.data('action'); // Use 'ad_action' to match PHP
                let reason = '';

                if (ad_action === 'deny') {
                    reason = prompt('Please provide a reason for denial (optional):', '');
                    if (reason === null) return;
                }

                button.closest('.lmb-feed-actions').html('Processing...');

                // --- FIX APPLIED ---
                // Using lmbAdmin object for both URL and nonce
                $.post(lmbAdmin.ajaxurl, {
                    action: 'lmb_ad_status_change',
                    nonce: lmbAdmin.nonce,
                    ad_id: adId,
                    ad_action: ad_action,
                    reason: reason
                }).done(function(response) {
                    if (response.success) {
                        loadTabContent($('.lmb-tab-btn.active').data('tab'));
                    } else {
                        alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                        loadTabContent($('.lmb-tab-btn.active').data('tab'));
                    }
                }).fail(function() {
                    alert('An unknown error occurred.');
                    loadTabContent($('.lmb-tab-btn.active').data('tab'));
                });
            });

            // Handle payment actions
            $(document).on('click', '.lmb-payment-action', function(e) {
                e.preventDefault();
                const button = $(this);
                const paymentId = button.data('id');
                const payment_action = button.data('action'); // Use 'payment_action' to match PHP
                let reason = '';

                if (payment_action === 'reject') {
                    reason = prompt('Please provide a reason for rejection:', '');
                    if (reason === null) return;
                }
                
                button.closest('.lmb-feed-actions').html('Processing...');

                // --- FIX APPLIED ---
                // Using lmbAdmin object for both URL and nonce
                $.post(lmbAdmin.ajaxurl, {
                    action: 'lmb_payment_action',
                    nonce: lmbAdmin.nonce,
                    payment_id: paymentId,
                    payment_action: payment_action,
                    reason: reason
                }).done(function(response) {
                    if (response.success) {
                        loadTabContent($('.lmb-tab-btn.active').data('tab'));
                    } else {
                        alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                        loadTabContent($('.lmb-tab-btn.active').data('tab'));
                    }
                }).fail(function() {
                    alert('An unknown error occurred.');
                    loadTabContent($('.lmb-tab-btn.active').data('tab'));
                });
            });
        });
        </script>
        <?php
    }
}



// All helper functions (lmb_render_activity_feed, lmb_render_quick_actions, etc.) remain the same.

function lmb_render_activity_feed() {
    $activity_log = get_option('lmb_activity_log', []);
    
    if (empty($activity_log)) {
        return '<div class="lmb-feed-empty"><i class="fas fa-stream"></i><p>' . __('No recent activity.', 'lmb-core') . '</p></div>';
    }

    $content = '<div class="lmb-activity-feed">';
    foreach (array_slice($activity_log, 0, 10) as $entry) {
        $user = get_userdata($entry['user']);
        $user_name = $user ? $user->display_name : 'Unknown User';
        
        $content .= '<div class="lmb-feed-item">';
        $content .= '<div class="lmb-feed-content">';
        $content .= '<div class="lmb-feed-title">' . esc_html($entry['msg']) . '</div>';
        $content .= '<div class="lmb-feed-meta">';
        $content .= '<i class="fas fa-user"></i> ' . esc_html($user_name);
        $content .= ' • <i class="fas fa-clock"></i> ' . human_time_diff(strtotime($entry['time'])) . ' ago';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';
    }
    $content .= '</div>';
    
    return $content;
}

function lmb_render_quick_actions() {
    $content = '<div class="lmb-actions-grid">';
    
    $actions = [
        [
            'title' => __('Upload New Newspaper', 'lmb-core'),
            'icon' => 'fas fa-plus-circle',
            'url' => admin_url('post-new.php?post_type=lmb_newspaper'),
            'description' => __('Add a new newspaper edition', 'lmb-core')
        ],
        [
            'title' => __('Manage Legal Ads', 'lmb-core'),
            'icon' => 'fas fa-gavel',
            'url' => admin_url('edit.php?post_type=lmb_legal_ad'),
            'description' => __('Review and manage legal ads', 'lmb-core')
        ],
        [
            'title' => __('Review Payments', 'lmb-core'),
            'icon' => 'fas fa-credit-card',
            'url' => admin_url('edit.php?post_type=lmb_payment'),
            'description' => __('Verify payment proofs', 'lmb-core')
        ],
        [
            'title' => __('Manage Packages', 'lmb-core'),
            'icon' => 'fas fa-box-open',
            'url' => admin_url('edit.php?post_type=lmb_package'),
            'description' => __('Edit subscription packages', 'lmb-core')
        ]
    ];
    
    foreach ($actions as $action) {
        $content .= '<div class="lmb-action-card">';
        $content .= '<h4><i class="' . $action['icon'] . '"></i> ' . $action['title'] . '</h4>';
        $content .= '<p>' . $action['description'] . '</p>';
        $content .= '<a href="' . $action['url'] . '" class="lmb-action-btn" target="_blank">';
        $content .= '<i class="' . $action['icon'] . '"></i> ' . $action['title'];
        $content .= '</a>';
        $content .= '</div>';
    }
    
    $content .= '</div>';
    return $content;
}

function lmb_render_pending_ads() {
    $pending_ads = get_posts([
        'post_type' => 'lmb_legal_ad',
        'post_status' => 'any',
        'posts_per_page' => 10,
        'meta_query' => [
            [
                'key' => 'lmb_status',
                'value' => 'pending_review',
                'compare' => '='
            ]
        ]
    ]);
    
    $count = count($pending_ads);
    
    if (empty($pending_ads)) {
        return [
            'content' => '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No legal ads are pending approval.', 'lmb-core') . '</p></div>',
            'count' => 0
        ];
    }

    $content = '<div class="lmb-pending-ads-feed">';
    foreach ($pending_ads as $ad) {
        $client_id = get_post_meta($ad->ID, 'lmb_client_id', true);
        $user = get_userdata($client_id);
        
        $content .= '<div class="lmb-feed-item">';
        $content .= '<div class="lmb-feed-content">';
        $content .= '<a href="' . get_edit_post_link($ad->ID) . '" class="lmb-feed-title">';
        $content .= esc_html($ad->post_title);
        $content .= '</a>';
        $content .= '<div class="lmb-feed-meta">';
        $content .= '<i class="fas fa-user"></i> ' . ($user ? esc_html($user->display_name) : 'Unknown');
        $content .= ' • <i class="fas fa-clock"></i> ' . human_time_diff(get_the_time('U', $ad->ID)) . ' ago';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '<div class="lmb-feed-actions">';
        $content .= '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-ad-action" data-action="approve" data-id="' . $ad->ID . '">';
        $content .= '<i class="fas fa-check"></i>';
        $content .= '</button>';
        $content .= '<button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-ad-action" data-action="deny" data-id="' . $ad->ID . '">';
        $content .= '<i class="fas fa-times"></i>';
        $content .= '</button>';
        $content .= '</div>';
        $content .= '</div>';
    }
    $content .= '</div>';
    
    return [
        'content' => $content,
        'count' => $count
    ];
}

function lmb_render_pending_payments() {
    $pending_payments = get_posts([
        'post_type' => 'lmb_payment',
        'posts_per_page' => 10,
        'meta_query' => [
            [
                'key' => 'payment_status',
                'value' => 'pending',
                'compare' => '='
            ]
        ]
    ]);
    
    $count = count($pending_payments);
    
    if (empty($pending_payments)) {
        return [
            'content' => '<div class="lmb-feed-empty"><i class="fas fa-check-circle"></i><p>' . __('No x payments are pending verification.', 'lmb-core') . '</p></div>',
            'count' => 0
        ];
    }

    $content = '<div class="lmb-pending-payments-feed">';
    foreach ($pending_payments as $payment) {
        $user_id = get_post_meta($payment->ID, 'user_id', true);
        $user = get_userdata($user_id);
        $reference = get_post_meta($payment->ID, 'payment_reference', true);
        
        $content .= '<div class="lmb-feed-item">';
        $content .= '<div class="lmb-feed-content">';
        $content .= '<a href="' . get_edit_post_link($payment->ID) . '" class="lmb-feed-title">';
        $content .= esc_html($payment->post_title);
        $content .= '</a>';
        $content .= '<div class="lmb-feed-meta">';
        $content .= '<i class="fas fa-receipt"></i> ' . esc_html($reference);
        $content .= ' • <i class="fas fa-user"></i> ' . ($user ? esc_html($user->display_name) : 'Unknown');
        $content .= ' • <i class="fas fa-clock"></i> ' . human_time_diff(get_the_time('U', $payment->ID)) . ' ago';
        $content .= '</div>';
        $content .= '</div>';
        $content .= '<div class="lmb-feed-actions">';
        $content .= '<button class="lmb-btn lmb-btn-sm lmb-btn-success lmb-payment-action" data-action="approve" data-id="' . $payment->ID . '">';
        $content .= '<i class="fas fa-check"></i>';
        $content .= '</button>';
        $content .= '<button class="lmb-btn lmb-btn-sm lmb-btn-danger lmb-payment-action" data-action="reject" data-id="' . $payment->ID . '">';
        $content .= '<i class="fas fa-times"></i>';
        $content .= '</button>';
        $content .= '</div>';
        $content .= '</div>';
    }
    $content .= '</div>';
    
    return [
        'content' => $content,
        'count' => $count
    ];
}