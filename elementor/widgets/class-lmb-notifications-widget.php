<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Notifications_Widget extends Widget_Base {
    public function get_name() { return 'lmb_notifications'; }
    public function get_title(){ return __('LMB Notifications','lmb-core'); }
    public function get_icon() { return 'eicon-bell'; }
    public function get_categories(){ return ['lmb-widgets']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>'.esc_html__('You must be logged in to view notifications.', 'lmb-core').'</p></div>';
            return;
        }

        $user_id = get_current_user_id();
        $notifications = $this->get_user_notifications($user_id);
        $unread_count = count(array_filter($notifications, function($n) { return !$n['read']; }));
        ?>
        <div class="lmb-notifications-widget">
            <div class="lmb-notifications-trigger" id="lmb-notifications-trigger">
                <i class="fas fa-bell"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="lmb-notification-badge"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </div>
            
            <div class="lmb-notifications-dropdown" id="lmb-notifications-dropdown">
                <div class="lmb-notifications-header">
                    <h3>
                        <i class="fas fa-bell"></i>
                        <?php esc_html_e('Notifications', 'lmb-core'); ?>
                    </h3>
                    <?php if ($unread_count > 0): ?>
                        <button class="lmb-mark-all-read" id="lmb-mark-all-read">
                            <?php esc_html_e('Mark all as read', 'lmb-core'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="lmb-notifications-list">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach($notifications as $notification): ?>
                            <div class="lmb-notification-item <?php echo !$notification['read'] ? 'lmb-notification-unread' : ''; ?>" 
                                 data-id="<?php echo esc_attr($notification['id']); ?>">
                                <div class="lmb-notification-icon">
                                    <i class="<?php echo esc_attr($notification['icon']); ?>"></i>
                                </div>
                                <div class="lmb-notification-content">
                                    <div class="lmb-notification-title">
                                        <?php echo esc_html($notification['title']); ?>
                                    </div>
                                    <div class="lmb-notification-message">
                                        <?php echo esc_html($notification['message']); ?>
                                    </div>
                                    <div class="lmb-notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo esc_html($notification['time_ago']); ?>
                                    </div>
                                </div>
                                <?php if (!$notification['read']): ?>
                                    <div class="lmb-notification-unread-dot"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="lmb-notifications-empty">
                            <i class="fas fa-bell-slash"></i>
                            <p><?php esc_html_e('No notifications yet.', 'lmb-core'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($notifications)): ?>
                    <div class="lmb-notifications-footer">
                        <a href="#" class="lmb-view-all-notifications">
                            <?php esc_html_e('View all notifications', 'lmb-core'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle notifications dropdown
            $('#lmb-notifications-trigger').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#lmb-notifications-dropdown').toggleClass('lmb-show');
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.lmb-notifications-widget').length) {
                    $('#lmb-notifications-dropdown').removeClass('lmb-show');
                }
            });
            
            // Mark notification as read when clicked
            $('.lmb-notification-item').on('click', function() {
                var $item = $(this);
                var notificationId = $item.data('id');
                
                if ($item.hasClass('lmb-notification-unread')) {
                    $.post(lmbAjax.ajaxurl, {
                        action: 'lmb_mark_notification_read',
                        nonce: lmbAjax.nonce,
                        notification_id: notificationId
                    }).done(function() {
                        $item.removeClass('lmb-notification-unread');
                        $item.find('.lmb-notification-unread-dot').remove();
                        
                        // Update badge count
                        var $badge = $('.lmb-notification-badge');
                        var currentCount = parseInt($badge.text()) || 0;
                        var newCount = Math.max(0, currentCount - 1);
                        
                        if (newCount === 0) {
                            $badge.remove();
                            $('#lmb-mark-all-read').remove();
                        } else {
                            $badge.text(newCount);
                        }
                    });
                }
            });
            
            // Mark all as read
            $('#lmb-mark-all-read').on('click', function(e) {
                e.preventDefault();
                
                $.post(lmbAjax.ajaxurl, {
                    action: 'lmb_mark_all_notifications_read',
                    nonce: lmbAjax.nonce
                }).done(function() {
                    $('.lmb-notification-item').removeClass('lmb-notification-unread');
                    $('.lmb-notification-unread-dot').remove();
                    $('.lmb-notification-badge').remove();
                    $('#lmb-mark-all-read').remove();
                });
            });
        });
        </script>
        <?php
    }
    
    private function get_user_notifications($user_id) {
        // Get notifications from user meta or custom table
        $notifications = get_user_meta($user_id, 'lmb_notifications', true);
        if (!is_array($notifications)) {
            $notifications = [];
        }
        
        // Add some sample notifications for demonstration
        if (empty($notifications)) {
            $notifications = [
                [
                    'id' => 1,
                    'title' => __('Ad Approved', 'lmb-core'),
                    'message' => __('Your legal ad "Company Formation" has been approved and published.', 'lmb-core'),
                    'icon' => 'fas fa-check-circle',
                    'time' => time() - 3600,
                    'time_ago' => '1 hour ago',
                    'read' => false,
                    'type' => 'success'
                ],
                [
                    'id' => 2,
                    'title' => __('Payment Verified', 'lmb-core'),
                    'message' => __('Your payment for Premium Package has been verified. 100 points added.', 'lmb-core'),
                    'icon' => 'fas fa-credit-card',
                    'time' => time() - 7200,
                    'time_ago' => '2 hours ago',
                    'read' => false,
                    'type' => 'info'
                ],
                [
                    'id' => 3,
                    'title' => __('New Newspaper Available', 'lmb-core'),
                    'message' => __('The latest edition of Legal Gazette is now available for download.', 'lmb-core'),
                    'icon' => 'fas fa-newspaper',
                    'time' => time() - 86400,
                    'time_ago' => '1 day ago',
                    'read' => true,
                    'type' => 'info'
                ]
            ];
        }
        
        return $notifications;
    }
}