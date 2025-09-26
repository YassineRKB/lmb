<?php
// FILE: elementor/widgets/class-lmb-newspaper-directory-widget.php

use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

if (!class_exists('LMB_Newspaper_Directory_Widget')) {
    class LMB_Newspaper_Directory_Widget extends Widget_Base {
        public function get_name() { return 'lmb_newspaper_directory'; }
        //public function get_title() { return __('LMB Newspaper Directory V2','lmb-core'); }
        public function get_title() { return __('Répertoire des Journaux LMB V2','lmb-core'); }
        public function get_icon()  { return 'eicon-library-upload'; }
        
        public function get_categories(){ return ['lmb-user-widgets-v2']; }
        
        public function get_script_depends() { return ['lmb-newspaper-directory-v2']; }

        public function get_style_depends() { return ['lmb-newspaper-directory-v2']; }


        protected function render() {
            ?>
            <div class="lmb-newspaper-directory-v2">
                <div class="lmb-widget-header">
                    <h3>LMB Journal</h3>
                    <p>Consulter votre Journal</p>
                </div>
                <div class="lmb-widget-content">
                    <div class="lmb-filters-box">
                        <form class="lmb-filters-form">
                            <div class="lmb-filter-grid">
                                <input type="search" name="filter_ref" placeholder="Rechercher par journal N..." class="lmb-filter-input" />
                                <input type="date" name="filter_date" class="lmb-filter-input" />
                                <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Réinitialiser</button>
                            </div>
                        </form>
                    </div>

                    <div class="lmb-table-container">
                        <table class="lmb-data-table">
                            <thead>
                                <tr>
                                    <th>Journal №</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
                    </div>

                    <div class="lmb-pagination-container">
                        </div>
                </div>
            </div>
            <?php
        }
    }
}
