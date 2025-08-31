<?php
// FILE: elementor/widgets/class-lmb-profile-v2-widget.php

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Profile_V2_Widget extends Widget_Base {

    public function get_name() {
        return 'lmb_profile_v2';
    }

    public function get_title() {
        return __('Profile V2', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-user-circle-o';
    }

    public function get_categories() {
        return ['lmb-user-widgets-v2'];
    }

    public function get_script_depends() {
        return ['lmb-profile-v2'];
    }

    public function get_style_depends() {
        return ['lmb-profile-v2'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>You must be logged in to view this page.</p>';
            // Optionally, render the Auth V2 widget here as a fallback
            return;
        }

        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $user_to_display = null;

        // Logic to determine which user to display
        $user_id_from_url = get_query_var('userid', 0); // Get 'userid' from URL: /profile/{userid}
        if ($is_admin && !empty($user_id_from_url)) {
            $user_to_display = get_user_by('ID', intval($user_id_from_url));
        }
        
        // If no user from URL or not an admin, default to the current user
        if (!$user_to_display) {
            $user_to_display = get_user_by('ID', $current_user_id);
        }

        if (!$user_to_display) {
            echo '<p>User not found.</p>';
            return;
        }
        
        $user_id = $user_to_display->ID;
        $can_edit_all = ($is_admin && ($current_user_id !== $user_id));
        $client_type = get_user_meta($user_id, 'lmb_client_type', true);

        $this->add_render_attribute('wrapper', [
            'class' => 'lmb-profile-v2-widget',
            'data-user-id' => $user_id
        ]);
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <div class="lmb-profile-main">
                
                <?php if ($is_admin && $current_user_id != $user_id) : ?>
                <div class="lmb-admin-editing-notice">
                    <i class="fas fa-exclamation-triangle"></i> You are editing the profile for <strong><?php echo esc_html($user_to_display->display_name); ?></strong>. All fields are unlocked.
                </div>
                <?php endif; ?>
                
                <form id="lmb-profile-details-form">
                    <div class="lmb-widget-header">
                        <h3><i class="fas fa-user-edit"></i> Profile Details</h3>
                    </div>
                    <div class="lmb-form-response" id="profile-response"></div>

                    <?php if ($client_type === 'professional') : ?>
                        <div class="lmb-form-grid">
                            <div class="lmb-form-group full-width"><label for="company_name">Company Name</label><input type="text" name="company_name" id="company_name" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_name', true)); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                            <div class="lmb-form-group"><label for="company_rc">RC</label><input type="text" name="company_rc" id="company_rc" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_rc', true)); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                            <div class="lmb-form-group"><label for="company_hq">Company HQ Address</label><input type="text" name="company_hq" id="company_hq" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_hq', true)); ?>"></div>
                            <div class="lmb-form-group"><label for="city">City</label><input type="text" name="city" id="city" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'city', true)); ?>"></div>
                            <div class="lmb-form-group"><label for="phone">Phone</label><input type="tel" name="phone_number" id="phone" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>"></div>
                        </div>
                    <?php else : // Regular Client or other ?>
                        <div class="lmb-form-grid">
                            <div class="lmb-form-group"><label for="first_name">First Name</label><input type="text" name="first_name" id="first_name" class="lmb-input" value="<?php echo esc_attr($user_to_display->first_name); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                            <div class="lmb-form-group"><label for="last_name">Last Name</label><input type="text" name="last_name" id="last_name" class="lmb-input" value="<?php echo esc_attr($user_to_display->last_name); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                            <div class="lmb-form-group"><label for="city">City</label><input type="text" name="city" id="city" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'city', true)); ?>"></div>
                            <div class="lmb-form-group"><label for="phone">Phone</label><input type="tel" name="phone_number" id="phone" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>"></div>
                        </div>
                    <?php endif; ?>
                     <div class="lmb-form-group full-width">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" class="lmb-input" value="<?php echo esc_attr($user_to_display->user_email); ?>" disabled>
                    </div>
                    
                    <button type="submit" class="lmb-btn">Save Changes</button>
                </form>
            </div>

            <div class="lmb-profile-sidebar">
                <div class="lmb-widget-header">
                    <h3><i class="fas fa-chart-bar"></i> User Stats</h3>
                </div>
                <div class="lmb-user-stats">
                    <div class="stat-item">
                        <span class="stat-label">Current Balance</span>
                        <span class="stat-value"><?php echo esc_html(LMB_Points::get_balance($user_id)); ?> PTS</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Published Ads</span>
                        <span class="stat-value"><?php echo esc_html(count_user_posts($user_id, 'lmb_legal_ad', true)); ?></span>
                    </div>
                </div>

                <div style="height:30px;"></div>

                <form id="lmb-password-change-form">
                    <div class="lmb-widget-header">
                        <h3><i class="fas fa-key"></i> Change Password</h3>
                    </div>
                    <div class="lmb-form-response" id="password-response"></div>
                    <?php if ($is_admin && $current_user_id != $user_id) : else: ?>
                    <div class="lmb-form-group">
                        <label for="current-password">Current Password</label>
                        <input type="password" name="current_password" id="current-password" class="lmb-input">
                    </div>
                    <?php endif; ?>
                    <div class="lmb-form-group">
                        <label for="new-password">New Password</label>
                        <input type="password" name="new_password" id="new-password" class="lmb-input">
                    </div>
                    <div class="lmb-form-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm-password" class="lmb-input">
                    </div>
                    <button type="submit" class="lmb-btn lmb-btn-secondary">Update Password</button>
                </form>
            </div>
        </div>
        <?php
    }
}