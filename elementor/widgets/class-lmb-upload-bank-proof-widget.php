<?php
// FILE: elementor/widgets/class-lmb-upload-bank-proof-widget.php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_bank_proof'; }
    //public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_title(){ return __('LMB Télécharger Preuve Bancaire','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-user-widgets-v2']; }

    public function get_script_depends() {
        return ['lmb-upload-bank-proof'];
    }

    public function get_style_depends() {
        return ['lmb-user-widgets-v2'];
    }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>Vous devez être connecté pour télécharger une preuve de paiement.</p></div>';
            return;
        }
        ?>
        <div class="lmb-upload-bank-proof-widget lmb-user-widget-v2">
            <div class="lmb-widget-header"><h3><i class="fas fa-file-invoice-dollar"></i> Télécharger Preuve de Paiement</h3></div>
            <div class="lmb-widget-content" id="lmb-upload-proof-container">
                <div class="lmb-loading"><i class="fas fa-spinner fa-spin"></i> Chargement des factures en attente...</div>
            </div>

            <div class="lmb-bank-details" style="padding: 25px; border-top: 1px solid #e9ecef; background-color: #f8f9fa;">
                <h4 style="margin-top: 0;">Détails Bancaires pour le Paiement</h4>
                <p><strong>Nom de la Banque:</strong> <?php echo esc_html(get_option('lmb_bank_name', 'Non Spécifié')); ?></p>
                <p><strong>IBAN:</strong> <?php echo esc_html(get_option('lmb_bank_iban', 'Non Spécifié')); ?></p>
                <p><strong>Titulaire du Compte:</strong> <?php echo esc_html(get_option('lmb_bank_account_holder', 'Non Spécifié')); ?></p>
            </div>
        </div>
        <?php
    }
}