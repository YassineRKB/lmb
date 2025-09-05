<?php
// FILE: elementor/widgets/class-lmb-upload-newspaper-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Newspaper_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_newspaper'; }
    //public function get_title(){ return __('LMB Upload Final Newspaper','lmb-core'); }
    public function get_title(){ return __('LMB Télécharger Journal Final','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['lmb-admin-widgets-v2']; }
    public function get_script_depends() { return ['lmb-upload-newspaper']; }
    public function get_style_depends() { return ['lmb-admin-widgets-v2']; }
    
    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>Vous devez être administrateur pour télécharger des journaux.</p></div>';
            return;
        }
        ?>
        <div class="lmb-upload-newspaper-container lmb-admin-widget">
            <div class="lmb-widget-header"><h3><i class="fas fa-newspaper"></i> Télécharger Journal Final</h3></div>
            <div class="lmb-widget-content">
                <form id="lmb-upload-newspaper-form" class="lmb-form" enctype="multipart/form-data">
                    <div class="lmb-form-row">
                        <div class="lmb-form-group">
                            <label for="newspaper_title">Titre du Journal / Édition</label>
                            <input type="text" name="newspaper_title" id="newspaper_title" required class="lmb-input">
                        </div>
                        <div class="lmb-form-group">
                            <label for="journal_no">Journal N°</label>
                            <input type="text" name="journal_no" id="journal_no" required class="lmb-input">
                        </div>
                    </div>
                    <div class="lmb-form-row">
                        <div class="lmb-form-group">
                            <label for="start_date">Date de Début (Optionnel)</label>
                            <input type="date" name="start_date" id="start_date" class="lmb-input">
                        </div>
                        <div class="lmb-form-group">
                            <label for="end_date">Date de Fin (Optionnel)</label>
                            <input type="date" name="end_date" id="end_date" class="lmb-input">
                        </div>
                    </div>
                    <p class="description">Si aucune plage de dates n'est sélectionnée, toutes les annonces avec des journaux temporaires correspondant au Journal N° ci-dessus seront mises à jour.</p>
                    <div class="lmb-form-group">
                        <label for="newspaper_pdf">PDF du Journal</label>
                        <input type="file" name="newspaper_pdf" id="newspaper_pdf" accept="application/pdf" required class="lmb-input">
                    </div>
                    <div class="lmb-form-actions">
                        <button type="submit" class="lmb-btn lmb-btn-primary lmb-btn-large"><i class="fas fa-upload"></i> Télécharger et Associer le Journal</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}