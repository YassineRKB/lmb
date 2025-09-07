<?php
// FILE: elementor/widgets/class-lmb-final-newspapers-list-widget.php

use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Final_Newspapers_List_Widget extends Widget_Base {
    public function get_name() {
        return 'lmb_final_newspapers_list';
    }

    public function get_title() {
        return __('Liste des Journaux Finaux', 'lmb-core');
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['lmb-admin-widgets-v2'];
    }

    public function get_style_depends() {
        return ['lmb-final-newspapers-list'];
    }

    protected function render() {
        if (!current_user_can('manage_options')) {
            echo '<div class="lmb-notice lmb-notice-error"><p>Vous devez être administrateur pour voir cette section.</p></div>';
            return;
        }

        $newspapers_query = new WP_Query([
            'post_type' => 'lmb_newspaper',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        ?>
        <div class="lmb-final-newspapers-list lmb-admin-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-check-circle"></i> Journaux Final Publiés</h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-table-container">
                    <table class="lmb-data-table">
                        <thead>
                            <tr>
                                <th>Numéro du Journal</th>
                                <th>Date</th>
                                <th>Annonces Publier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($newspapers_query->have_posts()) : ?>
                                <?php 
                                global $wpdb;
                                while ($newspapers_query->have_posts()) : $newspapers_query->the_post();
                                    $newspaper_id = get_the_ID();
                                    $journal_no = get_post_meta($newspaper_id, 'journal_no', true);
                                    $pdf_id = get_post_meta($newspaper_id, 'newspaper_pdf', true);
                                    $pdf_url = wp_get_attachment_url($pdf_id);

                                    $ad_count = $wpdb->get_var($wpdb->prepare(
                                        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
                                        'lmb_final_journal_id',
                                        $newspaper_id
                                    ));
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($journal_no); ?></td>
                                        <td><?php echo get_the_date(); ?></td>
                                        <td><?php echo esc_html($ad_count); ?></td>
                                        <td class="lmb-actions-cell">
                                            <?php if ($pdf_url) : ?>
                                                <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" class="lmb-btn lmb-btn-sm lmb-btn-primary">
                                                    <i class="fas fa-download"></i> Télécharger
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="4" style="text-align:center;">Aucun journal final n'a encore été téléchargé.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }
}
