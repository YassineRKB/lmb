<?php
// FILE: elementor/widgets/class-lmb-legal-ads-management-v2-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_Management_V2_Widget extends Widget_Base {
    public function get_name() { return 'lmb_legal_ads_management_v2'; }
    //public function get_title() { return __('LMB Legal Ads Management V2', 'lmb-core'); }
    public function get_title() { return __('Gestion des Annonces Légales LMB V2', 'lmb-core'); }
    public function get_icon() { return 'eicon-table'; }
    public function get_categories() { return ['lmb-admin-widgets-v2']; }
    
    // Correctly point to the dedicated JS and CSS files for this widget
    public function get_script_depends() { return ['lmb-legal-ads-management-v2']; }
    public function get_style_depends() { return ['lmb-legal-ads-management-v2']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lamv2-widget"><p style="padding: 20px;">Vous n\'avez pas la permission de voir ce contenu.</p></div>';
            return;
        }
        ?>
        <div class="lamv2-widget">
            <div class="lamv2-widget-header">
                <h3><i class="fas fa-gavel"></i> Gestion des Annonces Légales V2</h3>
            </div>
            <div class="lamv2-widget-content">
                <div class="lamv2-filters-box">
                    <form id="lamv2-ads-filters-form">
                        <div class="lamv2-filter-grid">
                            <input type="text" name="filter_ref" placeholder="Réf (ID)" class="lamv2-filter-input">
                            <input type="text" name="filter_company" placeholder="Société" class="lamv2-filter-input">
                            <input type="text" name="filter_type" placeholder="Type" class="lamv2-filter-input">
                            <input type="date" name="filter_date" class="lamv2-filter-input">
                            <input type="text" name="filter_client" placeholder="Client" class="lamv2-filter-input">
                            <select name="filter_status" class="lamv2-filter-select">
                                <option value="">Tous les Statuts</option>
                                <option value="published">Publié</option>
                                <option value="pending_review">En Attente de Révision</option>
                                <option value="draft">Brouillon</option>
                                <option value="denied">Refusé</option>
                            </select>
                            <input type="text" name="filter_approved_by" placeholder="Approuvé Par" class="lamv2-filter-input">
                            <button type="reset" class="lamv2-btn lamv2-btn-view"><i class="fas fa-undo"></i> Réinitialiser</button>
                        </div>
                    </form>
                </div>

                <div class="lamv2-table-container">
                    <table class="lamv2-data-table">
                        <thead>
                            <tr>
                                <th>Réf</th>
                                <th>Société</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Statut</th>
                                <th>Approuvé Par</th>
                                <th>Accusé</th>
                                <th>Journal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            </tbody>
                    </table>
                </div>
                
                <div class="lamv2-pagination-container"></div>
            </div>

            <div id="lamv2-upload-journal-modal" class="lamv2-modal-overlay hidden">
                <div class="lamv2-modal-content">
                    <div class="lamv2-modal-header">
                         <h4>Télécharger Journal Temporaire</h4>
                         <button class="lamv2-modal-close">&times;</button>
                    </div>
                     <form id="lamv2-upload-journal-form" class="lamv2-upload-journal-form">
                        <input type="hidden" name="ad_id" id="lamv2-journal-ad-id">
                         <div class="lamv2-form-grid">
                            <div class="lamv2-form-group">
                                <label for="lamv2-journal-no">Journal N°</label>
                                <input type="text" id="lamv2-journal-no" name="journal_no" class="lamv2-filter-input" placeholder="Entrer le Numéro de Journal" required>
                            </div>
                            <div class="lamv2-form-group">
                                <label for="lamv2-journal-file">Fichier PDF du Journal</label>
                                <input type="file" id="lamv2-journal-file" name="journal_file" class="lamv2-filter-input" required accept="application/pdf">
                            </div>
                         </div>
                         <div class="lamv2-form-actions">
                             <button type="button" class="lamv2-btn lamv2-btn-view lamv2-modal-close">Annuler</button>
                             <button type="submit" class="lamv2-btn lamv2-btn-primary">Télécharger</button>
                         </div>
                     </form>
                </div>
            </div>
        </div>
        <?php
    }
}