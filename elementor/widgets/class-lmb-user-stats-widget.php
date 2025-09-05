<?php
// FILE: elementor/widgets/class-lmb-user-stats-widget.php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_User_Stats_Widget extends Widget_Base {
    public function get_name() { return 'lmb_user_stats'; }
    //public function get_title(){ return __('LMB User Stats','lmb-core'); }
    public function get_title(){ return __('Statistiques Utilisateur LMB','lmb-core'); }
    public function get_icon() { return 'eicon-person'; }
    public function get_categories(){ return ['lmb-user-widgets-v2']; } // Changed category

    public function get_style_depends() {
        return ['lmb-admin-widgets-v2']; // Use V2 styles
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<p>Ce contenu n\'est disponible que pour les utilisateurs connectés.</p>';
            return;
        }

        $stats = LMB_User_Dashboard::collect_user_stats();
        ?>
        <div class="lmb-admin-stats-widget lmb-user-stats-widget">
            <div class="lmb-stats-grid">
                <div class="lmb-stat-card lmb-stat-points">
                    <div class="lmb-stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['points_balance']); ?></div>
                        <div class="lmb-stat-label">Votre Solde de Points</div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-ads">
                    <div class="lmb-stat-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['remaining_ads']); ?></div>
                        <div class="lmb-stat-label">Quota d'Annonces Restant</div>
                    </div>
                </div>
                
                <div class="lmb-stat-card lmb-stat-pending">
                    <div class="lmb-stat-icon"><i class="fas fa-clock"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_pending']); ?></div>
                        <div class="lmb-stat-label">Annonces en Attente de Révision</div>
                    </div>
                </div>
                <div class="lmb-stat-card lmb-stat-published">
                    <div class="lmb-stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="lmb-stat-content">
                        <div class="lmb-stat-number"><?php echo number_format($stats['ads_published']); ?></div>
                        <div class="lmb-stat-label">Annonces Publiées</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}