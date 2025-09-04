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
            return;
        }

        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $user_to_display = null;
        
        $user_id_from_url = get_query_var('userid', 0);
        if ($is_admin && !empty($user_id_from_url)) {
            $user_to_display = get_user_by('ID', intval($user_id_from_url));
        }
        
        if (!$user_to_display) {
            $user_to_display = get_user_by('ID', $current_user_id);
        }

        if (!$user_to_display) {
            echo '<p>User not found.</p>';
            return;
        }
        
        $user_id = $user_to_display->ID;
        $user_roles = (array) $user_to_display->roles;
        $user_role = !empty($user_roles) ? $user_roles[0] : 'client';
        $is_editing_other = ($is_admin && ($current_user_id !== $user_id));
        $client_type = get_user_meta($user_id, 'lmb_client_type', true);
        
        // Data for sidebar
        $balance = LMB_Points::get_balance($user_id);
        $cost_per_ad = LMB_Points::get_cost_per_ad($user_id);
        $cost_per_ad = ($cost_per_ad > 0) ? $cost_per_ad : 10;
        $remaining_ads = floor($balance / $cost_per_ad);
        $balance_history = LMB_Points::get_transactions($user_id, 5);

        $this->add_render_attribute('wrapper', [
            'class' => 'lmb-profile-v2-widget',
            'data-user-id' => $user_id
        ]);
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            
            <?php if ($is_editing_other) : ?>
            <div class="lmb-admin-editing-notice">
                <i class="fas fa-exclamation-triangle"></i> You are editing the profile for <strong><?php echo esc_html($user_to_display->display_name); ?></strong>.
            </div>
            <?php endif; ?>

            <form id="lmb-profile-details-form" class="lmb-profile-main-form">
                <div class="lmb-profile-top-row">
                    <div class="lmb-profile-card">
                        <div class="lmb-widget-header">
                            <h3><i class="fas fa-user-edit"></i> Profile Details</h3>
                        </div>
                        <div class="lmb-card-content">
                            <div class="lmb-form-response" id="profile-response"></div>

                            <?php if ($is_editing_other): ?>
                                <div class="lmb-admin-controls">
                                    <div class="lmb-form-group">
                                        <label for="lmb_client_type">Client Type</label>
                                        <select name="lmb_client_type" id="lmb_client_type" class="lmb-input">
                                            <option value="regular" <?php selected($client_type, 'regular'); ?>>Regular</option>
                                            <option value="professional" <?php selected($client_type, 'professional'); ?>>Professional</option>
                                        </select>
                                    </div>
                                    <div class="lmb-form-group">
                                        <label for="lmb_user_role">User Role</label>
                                        <select name="lmb_user_role" id="lmb_user_role" class="lmb-input">
                                            <option value="client" <?php selected($user_role, 'client'); ?>>Client</option>
                                            <option value="administrator" <?php selected($user_role, 'administrator'); ?>>Administrator</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div id="lmb-profile-regular-fields" style="<?php echo ($client_type !== 'regular') ? 'display: none;' : ''; ?>">
                                <div class="lmb-form-grid">
                                    <div class="lmb-form-group"><label for="first_name">First Name</label><input type="text" name="first_name" id="first_name" class="lmb-input" value="<?php echo esc_attr($user_to_display->first_name); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                    <div class="lmb-form-group"><label for="last_name">Last Name</label><input type="text" name="last_name" id="last_name" class="lmb-input" value="<?php echo esc_attr($user_to_display->last_name); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                </div>
                            </div>
                            
                            <div id="lmb-profile-professional-fields" style="<?php echo ($client_type !== 'professional') ? 'display: none;' : ''; ?>">
                                <div class="lmb-form-grid">
                                    <div class="lmb-form-group full-width"><label for="company_name">Company Name</label><input type="text" name="company_name" id="company_name" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_name', true)); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                    <div class="lmb-form-group"><label for="company_rc">RC</label><input type="text" name="company_rc" id="company_rc" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_rc', true)); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                    <div class="lmb-form-group"><label for="company_hq">Company HQ Address</label><input type="text" name="company_hq" id="company_hq" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_hq', true)); ?>"></div>
                                </div>
                            </div>

                            <div class="lmb-form-grid">
                                <div class="lmb-form-group"><label for="city">City</label><input type="text" name="city" id="city" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'city', true)); ?>"></div>
                                <div class="lmb-form-group"><label for="phone">Phone</label><input type="tel" name="phone_number" id="phone" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>"></div>
                            </div>
                            <div class="lmb-form-group full-width">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" class="lmb-input" value="<?php echo esc_attr($user_to_display->user_email); ?>" disabled>
                            </div>
                            <button type="submit" class="lmb-btn">Save Changes</button>
                        </div>
                    </div>

                    <div class="lmb-profile-sidebar-grid">
                        <div class="lmb-profile-card">
                            <div class="lmb-widget-header">
                                <h3><i class="fas fa-chart-bar"></i> Account Status</h3>
                            </div>
                            <div class="lmb-card-content">
                                <div class="lmb-user-stats">
                                    <div class="stat-item"><span class="stat-label">Current Balance</span><span class="stat-value"><?php echo esc_html($balance); ?> PTS</span></div>
                                    <div class="stat-item"><span class="stat-label">Cost Per Ad</span><span class="stat-value-small"><?php echo esc_html($cost_per_ad); ?> PTS</span></div>
                                    <div class="stat-item"><span class="stat-label">Remaining Ads Quota</span><span class="stat-value"><?php echo esc_html($remaining_ads); ?></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="lmb-profile-card">
                            <div class="lmb-widget-header">
                                <h3><i class="fas fa-history"></i> Balance History</h3>
                            </div>
                            <div class="lmb-card-content">
                                <div class="lmb-balance-history">
                                    <?php if (!empty($balance_history)) : foreach ($balance_history as $item) : $is_credit = $item->amount >= 0; ?>
                                    <div class="history-item">
                                        <div class="history-icon <?php echo $is_credit ? 'credit' : 'debit'; ?>"><i class="fas <?php echo $is_credit ? 'fa-plus' : 'fa-minus'; ?>"></i></div>
                                        <div class="history-details">
                                            <span class="history-reason"><?php echo esc_html($item->reason); ?></span>
                                            <span class="history-time"><?php echo esc_html(human_time_diff(strtotime($item->created_at))) . ' ago'; ?></span>
                                        </div>
                                        <div class="history-amount <?php echo $is_credit ? 'credit' : 'debit'; ?>"><?php echo ($is_credit ? '+' : '') . esc_html($item->amount); ?></div>
                                    </div>
                                    <?php endforeach; else: ?>
                                    <p class="no-history">No recent transactions.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="lmb-profile-bottom-row">
                 <div class="lmb-profile-card">
                    <form id="lmb-password-change-form">
                        <div class="lmb-widget-header"><h3><i class="fas fa-key"></i> Change Password</h3></div>
                        <div class="lmb-card-content">
                            <div class="lmb-form-response" id="password-response"></div>
                            <?php if (!$is_editing_other): ?>
                            <div class="lmb-form-group"><label for="current-password">Current Password</label><input type="password" name="current_password" id="current-password" class="lmb-input" required></div>
                            <?php endif; ?>
                            <div class="lmb-form-grid">
                                <div class="lmb-form-group"><label for="new-password">New Password</label><input type="password" name="new_password" id="new-password" class="lmb-input" required></div>
                                <div class="lmb-form-group"><label for="confirm-password">Confirm New Password</label><input type="password" name="confirm_password" id="confirm-password" class="lmb-input" required></div>
                            </div>
                            <button type="submit" class="lmb-btn lmb-btn-secondary">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}