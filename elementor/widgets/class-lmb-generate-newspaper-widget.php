<?php
// FILE: elementor/widgets/class-lmb-generate-newspaper-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Generate_Newspaper_Widget extends Widget_Base {
    public function get_name() { return 'lmb_generate_newspaper'; }
    public function get_title() { return __('Génération de Journaux', 'lmb-core'); }
    public function get_icon() { return 'eicon-document-file'; }
    public function get_categories() { return ['lmb-admin-widgets-v2']; }
    
    public function get_script_depends() { return ['lmb-generate-newspaper']; }
    public function get_style_depends() { return ['lmb-generate-newspaper']; }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>Accès refusé. Cette fonctionnalité est réservée aux administrateurs.</p></div>';
            return;
        }
        ?>
        <div class="lmb-generate-newspaper-widget lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-magic"></i> Génération de Journaux</h3>
            </div>
            <div class="lmb-widget-content">
                
                <div id="step-1-filters" class="lmb-newspaper-step active">
                    <h4>Étape 1: Filtrer les Annonces Légales Publiées</h4>
                    <div class="lmb-filters-box">
                        <form id="lmb-newspaper-filters-form">
                            <div class="lmb-form-row four-columns">
                                <div class="lmb-form-group">
                                    <label for="journal_no">Numéro du Journal <span class="required">*</span></label>
                                    <input type="text" name="journal_no" id="journal_no" class="lmb-input" required placeholder="Ex: 2025-01-1">
                                </div>
                                <div class="lmb-form-group">
                                    <label for="date_start">Date de Début (Approuvée Après) <span class="required">*</span></label>
                                    <input type="date" name="date_start" id="date_start" class="lmb-input" required>
                                </div>
                                <div class="lmb-form-group">
                                    <label for="date_end">Date de Fin (Approuvée Avant) <span class="required">*</span></label>
                                    <input type="date" name="date_end" id="date_end" class="lmb-input" required>
                                </div>
                                <div class="lmb-form-group" style="align-self: flex-end;">
                                    <!-- NEW FILTER BUTTON -->
                                    <button type="button" id="lmb-filter-ads-btn" class="lmb-btn lmb-btn-secondary lmb-btn-full" style="height: 44px;"><i class="fas fa-filter"></i> Filtrer</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="lmb-ads-selection-table">
                        <div class="lmb-table-container">
                            <table class="lmb-data-table" id="lmb-ads-to-include-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"><input type="checkbox" id="select-all-ads"></th>
                                        <th>Réf</th>
                                        <th>Société</th>
                                        <th>Type</th>
                                        <th>Date d'Approbation</th>
                                        <th>Statut Journal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" style="text-align:center;">Veuillez définir la période et cliquer sur 'Filtrer' pour charger les annonces.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="lmb-form-actions">
                         <!-- Visualize button is now disabled by default and only enabled after a successful filter and selection -->
                         <button type="button" id="lmb-visualize-btn" class="lmb-btn lmb-btn-primary lmb-btn-large" disabled><i class="fas fa-eye"></i> Visualiser le Journal</button>
                    </div>
                </div>

                <div id="step-2-preview" class="lmb-newspaper-step" style="display: none;">
                    <h4>Étape 2: Prévisualisation du Journal</h4>
                    
                    <div id="lmb-newspaper-preview-area" class="newspaper-preview-area">
                        <!-- PDF link and message displayed here -->
                    </div>
                    
                    <div class="lmb-form-actions three-columns" id="lmb-preview-controls" style="margin-top: 20px; justify-content: center;">
                        <!-- Publish/Discard buttons injected here by JS -->
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
