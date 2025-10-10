<?php
// FILE: elementor/widgets/class-lmb-upload-newspaper-widget.php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Newspaper_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_newspaper'; }
    public function get_title() { return __('Télécharger le Journal Final', 'lmb-core'); }
    public function get_icon() { return 'eicon-upload-alt'; }
    public function get_categories() { return ['lmb-admin-widgets-v2']; }

    public function get_script_depends() {
        return ['lmb-upload-newspaper'];
    }
    public function get_style_depends() {
        return ['lmb-admin-widgets-v2'];
    }

    protected function render() {
        ?>
        <div class="lmb-widget-container lmb-upload-newspaper-widget">
            <div class="lmb-widget-header">
                <h3>Finaliser le Journal</h3>
                <p>Sélectionnez les annonces, téléchargez le PDF final et associez-les.</p>
            </div>

            <div id="lmb-un-step-1" class="lmb-un-step">
                <h4>Étape 1: Sélectionner les Critères</h4>
                <form id="lmb-fetch-eligible-ads-form" class="lmb-form">
                    <div class="lmb-form-grid">
                        <div class="lmb-form-group">
                            <label for="lmb-start-date">Date de Début</label>
                            <input type="date" id="lmb-start-date" name="start_date" required>
                        </div>
                        <div class="lmb-form-group">
                            <label for="lmb-end-date">Date de Fin</label>
                            <input type="date" id="lmb-end-date" name="end_date" required>
                        </div>
                        <div class="lmb-form-group">
                            <label for="lmb-journal-no">N° du Journal</label>
                            <input type="text" id="lmb-journal-no" name="journal_no" placeholder="Ex: 1234" required>
                        </div>
                    </div>
                    
                    <div class="lmb-form-group" style="margin-top: 20px; border-top: 1px solid #f0f0f1; padding-top: 20px;">
                        <input type="checkbox" id="lmb-replace-journal" name="replace_journal" value="1" style="width: auto; margin-right: 8px; vertical-align: middle;">
                        <label for="lmb-replace-journal" style="display: inline; font-weight: normal;">Inclure les annonces déjà associées (pour remplacer un journal)</label>
                    </div>
                    <div class="lmb-form-actions">
                        <button type="submit" class="lmb-btn lmb-btn-primary">
                            <i class="fas fa-search"></i> Rechercher les Annonces
                        </button>
                    </div>
                </form>
            </div>

            <div id="lmb-un-step-2" class="lmb-un-step" style="display:none;">
                <h4>Étape 2: Vérifier les Annonces et Télécharger</h4>
                <form id="lmb-final-upload-form" class="lmb-form" enctype="multipart/form-data">
                    <div class="lmb-ads-selection-container">
                        <p><strong id="lmb-ads-count">0</strong> annonce(s) trouvée(s). Veuillez vérifier et sélectionner celles à inclure.</p>
                        <table class="lmb-data-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%;"><input type="checkbox" id="lmb-select-all-ads"></th>
                                    <th>Réf</th>
                                    <th>Société</th>
                                    <th>Date d'Approbation</th>
                                    <th>Statut Actuel</th>
                                    </tr>
                            </thead>
                            <tbody id="lmb-eligible-ads-tbody">
                                </tbody>
                        </table>
                    </div>
                    <div class="lmb-form-group">
                        <label for="lmb-final-pdf-upload">Fichier PDF du Journal Final</label>
                        <input type="file" id="lmb-final-pdf-upload" name="newspaper_pdf" accept=".pdf" required>
                    </div>
                    <div class="lmb-form-actions">
                         <button type="button" id="lmb-un-back-btn" class="lmb-btn lmb-btn-secondary"><i class="fas fa-arrow-left"></i> Retour</button>
                        <button type="submit" class="lmb-btn lmb-btn-success">
                            <i class="fas fa-check-circle"></i> Télécharger et Associer
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="lmb-un-progress-container" style="display:none;">
                <h4>Progression...</h4>
                <div class="lmb-progress-bar">
                    <div id="lmb-progress-bar-inner" class="lmb-progress-bar-inner" style="width: 0%;"></div>
                </div>
                <div id="lmb-progress-text">Initialisation...</div>
            </div>

        </div>
        <?php
    }
}