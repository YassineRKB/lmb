<?php
// FILE: elementor/widgets/class-lmb-balance-manipulation-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

if (!class_exists('LMB_Balance_Manipulation_Widget')) {
    class LMB_Balance_Manipulation_Widget extends Widget_Base {

        public function get_name() {
            return 'lmb_balance_manipulation';
        }

        public function get_title() {
            return __('Manipulation du Solde', 'lmb-core');
        }

        public function get_icon() {
            return 'eicon-coins';
        }

        public function get_categories() {
            return ['lmb-admin-widgets-v2']; // Use the admin category
        }

        public function get_script_depends() {
            return ['lmb-balance-manipulation'];
        }

        protected function render() {
            // This widget should ONLY ever render for admins viewing a specific user.
            if (!current_user_can('manage_options') || !isset($_GET['user_id'])) {
                return; // Render nothing if conditions are not met.
            }

            $user_id = intval($_GET['user_id']);
            $user_to_display = get_user_by('ID', $user_id);

            if (!$user_to_display) {
                echo '<p>Utilisateur introuvable.</p>';
                return;
            }
            ?>
            <div class="lmb-balance-box">
                <div class="lmb-profile-card">
                    <form id="lmb-balance-manipulation-form">
                        <div class="lmb-widget-header"><h3><i class="fas fa-coins"></i> Manipuler le Solde du Client</h3></div>
                        <div class="lmb-card-content">
                            <div class="lmb-form-response" id="balance-response"></div>
                            <div class="lmb-form-group">
                                <label for="lmb-balance-amount">Montant (utiliser un - pour débiter)</label>
                                <input type="number" name="amount" id="lmb-balance-amount" class="lmb-input" required>
                            </div>
                            <div class="lmb-form-group">
                                <label for="lmb-balance-reason">Raison</label>
                                <textarea name="reason" id="lmb-balance-reason" class="lmb-input" rows="3" required></textarea>
                            </div>
                            <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                            <button type="submit" class="lmb-btn">Appliquer la Modification</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php
        }
    }
}