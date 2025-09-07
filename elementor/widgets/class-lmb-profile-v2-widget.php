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
        return __('Profil V2', 'lmb-core');
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
            echo '<p>Vous devez être connecté pour voir cette page.</p>';
            return;
        }

        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $user_to_display = null;
        
        // --- FIX START: More robust way to get user ID from URL ---
        global $wp;
        $path_parts = explode('/', rtrim(trailingslashit($wp->request), '/'));
        $user_id_from_url = is_numeric(end($path_parts)) ? intval(end($path_parts)) : 0;
        // --- FIX END ---

        if ($is_admin && !empty($user_id_from_url)) {
            $user_to_display = get_user_by('ID', $user_id_from_url);
        }
        
        if (!$user_to_display) {
            $user_to_display = get_user_by('ID', $current_user_id);
        }

        if (!$user_to_display) {
            echo '<p>Utilisateur introuvable.</p>';
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
                <i class="fas fa-exclamation-triangle"></i> Vous modifiez le profil de <strong><?php echo esc_html($user_to_display->display_name); ?></strong>.
            </div>
            <?php endif; ?>

            <form id="lmb-profile-details-form" class="lmb-profile-main-form">
                <div class="lmb-profile-top-row">
                    <div class="lmb-profile-card">
                        <div class="lmb-widget-header">
                            <h3><i class="fas fa-user-edit"></i> Détails du Profil</h3>
                        </div>
                        <div class="lmb-card-content">
                            <div class="lmb-form-response" id="profile-response"></div>

                            <?php if ($is_editing_other): ?>
                                <div class="lmb-admin-controls">
                                    <div class="lmb-form-group">
                                        <label for="lmb_client_type">Type de Client</label>
                                        <select name="lmb_client_type" id="lmb_client_type" class="lmb-input">
                                            <option value="regular" <?php selected($client_type, 'regular'); ?>>Individuel</option>
                                            <option value="professional" <?php selected($client_type, 'professional'); ?>>Professional</option>
                                        </select>
                                    </div>
                                    <div class="lmb-form-group">
                                        <label for="lmb_user_role">Rôle Utilisateur</label>
                                        <select name="lmb_user_role" id="lmb_user_role" class="lmb-input">
                                            <option value="client" <?php selected($user_role, 'client'); ?>>Client</option>
                                            <option value="administrator" <?php selected($user_role, 'administrator'); ?>>Administrateur</option>
                                        </select>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div id="lmb-profile-regular-fields" style="<?php echo ($client_type !== 'regular') ? 'display: none;' : ''; ?>">
                                <div class="lmb-form-grid">
                                    <div class="lmb-form-group"><label for="first_name">Prénom</label><input type="text" name="first_name" id="first_name" class="lmb-input" value="<?php echo esc_attr($user_to_display->first_name); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                    <div class="lmb-form-group"><label for="last_name">Nom</label><input type="text" name="last_name" id="last_name" class="lmb-input" value="<?php echo esc_attr($user_to_display->last_name); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                </div>
                            </div>
                            
                            <div id="lmb-profile-professional-fields" style="<?php echo ($client_type !== 'professional') ? 'display: none;' : ''; ?>">
                                <div class="lmb-form-grid">
                                    <div class="lmb-form-group full-width"><label for="company_name">Nom de la Société</label><input type="text" name="company_name" id="company_name" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_name', true)); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                    <div class="lmb-form-group"><label for="company_rc">RC</label><input type="text" name="company_rc" id="company_rc" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_rc', true)); ?>" <?php echo $is_admin ? '' : 'readonly'; ?>></div>
                                    <div class="lmb-form-group"><label for="company_hq">Adresse du Siège Social</label><input type="text" name="company_hq" id="company_hq" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'company_hq', true)); ?>"></div>
                                </div>
                            </div>

                            <div class="lmb-form-grid">
                                <div class="lmb-form-group"><label for="city">Ville</label><input type="text" name="city" id="city" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'city', true)); ?>"></div>
                                <div class="lmb-form-group"><label for="phone">Téléphone</label><input type="tel" name="phone_number" id="phone" class="lmb-input" value="<?php echo esc_attr(get_user_meta($user_id, 'phone_number', true)); ?>"></div>
                            </div>
                            <div class="lmb-form-group full-width">
                                <label for="email">Adresse Email</label>
                                <input type="email" id="email" class="lmb-input" value="<?php echo esc_attr($user_to_display->user_email); ?>" disabled>
                            </div>
                            <button type="submit" class="lmb-btn">Enregistrer les Modifications</button>
                        </div>
                    </div>

                    <div class="lmb-profile-sidebar-grid">
                        <div class="lmb-profile-card">
                            <div class="lmb-widget-header">
                                <h3><i class="fas fa-chart-bar"></i> Statut du Compte</h3>
                            </div>
                            <div class="lmb-card-content">
                                <div class="lmb-user-stats">
                                    <div class="stat-item"><span class="stat-label">Solde Actuel</span><span class="stat-value"><?php echo esc_html($balance); ?> PTS</span></div>
                                    <div class="stat-item"><span class="stat-label">Coût Par Annonce</span><span class="stat-value-small"><?php echo esc_html($cost_per_ad); ?> PTS</span></div>
                                    <div class="stat-item"><span class="stat-label">Quota d'Annonces Restant</span><span class="stat-value"><?php echo esc_html($remaining_ads); ?></span></div>
                                </div>
                            </div>
                        </div>
                        <div class="lmb-profile-card">
                            <div class="lmb-widget-header">
                                <h3><i class="fas fa-history"></i> Historique du Solde</h3>
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
                                    <p class="no-history">Aucune transaction récente.</p>
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
                        <div class="lmb-widget-header"><h3><i class="fas fa-key"></i> Changer le Mot de Passe</h3></div>
                        <div class="lmb-card-content">
                            <div class="lmb-form-response" id="password-response"></div>
                            <?php if (!$is_editing_other): ?>
                            <div class="lmb-form-group"><label for="current-password">Mot de Passe Actuel</label><input type="password" name="current_password" id="current-password" class="lmb-input" required></div>
                            <?php endif; ?>
                            <div class="lmb-form-grid">
                                <div class="lmb-form-group"><label for="new-password">Nouveau Mot de Passe</label><input type="password" name="new_password" id="new-password" class="lmb-input" required></div>
                                <div class="lmb-form-group"><label for="confirm-password">Confirmer le Nouveau Mot de Passe</label><input type="password" name="confirm_password" id="confirm-password" class="lmb-input" required></div>
                            </div>
                            <button type="submit" class="lmb-btn lmb-btn-secondary">Mettre à Jour le Mot de Passe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
