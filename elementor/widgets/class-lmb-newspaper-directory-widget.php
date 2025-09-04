<?php
// FILE: elementor/widgets/class-lmb-newspaper-directory-widget.php

use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Newspaper_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_newspaper_directory'; }
    public function get_title() { return __('LMB Newspaper Directory V2','lmb-core'); }
    public function get_icon()  { return 'eicon-library-upload'; }
    
    public function get_categories(){ return ['lmb-user-widgets-v2']; }
    
    public function get_script_depends() { return ['lmb-newspaper-directory-v2']; }

    public function get_style_depends() { return ['lmb-newspaper-directory-v2']; }


    protected function render() {
        ?>
        <div class="lmb-newspaper-directory-v2">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-newspaper"></i> <?php esc_html_e('Newspaper Archives', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-box">
                    <form class="lmb-filters-form">
                        <div class="lmb-filter-grid">
                            <input type="search" name="filter_ref" placeholder="<?php esc_attr_e('Search by Ref (ID)...','lmb-core'); ?>" class="lmb-filter-input" />
                            <input type="search" name="s" placeholder="<?php esc_attr_e('Search by Name...','lmb-core'); ?>" class="lmb-filter-input" />
                            <input type="date" name="filter_date" class="lmb-filter-input" />
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </form>
                </div>

                <div class="lmb-table-container">
                    <table class="lmb-data-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ref', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Name', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Date', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
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