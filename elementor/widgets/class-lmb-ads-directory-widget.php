<?php
// FILE: elementor/widgets/class-lmb-ads-directory-widget.php
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Ads_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_ads_directory'; }
    //public function get_title() { return __('LMB Ads Directory V2','lmb-core'); }
    public function get_title() { return __('Répertoire des Annonces LMB V2','lmb-core'); }
    public function get_icon() { return 'eicon-post-list'; }
    public function get_categories() { return ['lmb-user-widgets-v2']; }

    public function get_script_depends() {
        return ['lmb-ads-directory-v2'];
    }

    public function get_style_depends() {
        return ['lmb-ads-directory-v2'];
    }

    protected function render() {
        if (isset($_GET['legal-ad'])) {
            $ad_id_slug = sanitize_text_field($_GET['legal-ad']);
            $ad_id = intval(substr($ad_id_slug, 0, strpos($ad_id_slug, '-')));
            if ($ad_id > 0) {
                $this->render_single_ad($ad_id);
            } else {
                $this->render_directory_view();
            }
        } else {
            $this->render_directory_view();
        }
    }

    protected function render_single_ad($ad_id) {
        $ad = get_post($ad_id);
        $current_user_id = get_current_user_id();

        // Check if the post exists and is a legal ad
        if ($ad && $ad->post_type == 'lmb_legal_ad') {
            $lmb_status = get_post_meta($ad_id, 'lmb_status', true);
            
            // --- MODIFIED PER USER REQUEST: Allow logged-in client to see their own ads in any status ---
            $is_ad_author = ($current_user_id > 0 && $ad->post_author == $current_user_id);
            $is_published = ($lmb_status === 'published');
            $is_admin = current_user_can('manage_options');

            // The ad can be viewed if it's published OR the user is the author OR the user is an admin.
            $can_view = $is_published || $is_ad_author || $is_admin;
            
            if ($can_view) {
                $publication_date = get_post_meta($ad_id, 'approved_date', true);
                if(empty($publication_date)) {
                    // Fallback to post date if approved date isn't set
                    $publication_date = get_the_date('d F Y', $ad_id);
                } else {
                    // Format the date for French locale
                    $publication_date = date_i18n('d F Y', strtotime($publication_date));
                }
                ?>
                <div class="lmb-ads-directory-v2 lmb-single-ad-container">
                    <div class="lmb-single-ad-header">
                        <div>
                            <h1><?php echo esc_html($ad->post_title); ?></h1>
                            <?php if ($lmb_status === 'published'): ?>
                                <p class="lmb-ad-publication-date">Annonce Publiée le <?php echo esc_html($publication_date); ?></p>
                            <?php else: 
                                // Show the actual status if not published, which addresses the 'draft' requirement
                                ?>
                                 <p class="lmb-ad-publication-date">Statut: <?php echo esc_html(ucfirst(str_replace('_', ' ', $lmb_status))); ?></p>
                            <?php endif; ?>
                        </div>
                        </div>
                    <div class="lmb-single-ad-content">
                        <?php echo wp_kses_post(get_post_meta($ad_id, 'full_text', true)); ?>
                    </div>
                </div>
                <?php
            } else {
                // User does not have permission and the ad is not published
                // This message is only shown if the current user is NOT the author and NOT an admin, and the ad is NOT published.
                echo '<p>' . esc_html__('Annonce légale introuvable ou vous n\'avez pas la permission de la voir.', 'lmb-core') . '</p>';
            }
        } else {
            // Post doesn't exist or is not a legal ad
            echo '<p>' . esc_html__('Annonce légale introuvable.', 'lmb-core') . '</p>';
        }
    }

    protected function render_directory_view() {
        ?>
        <div class="lmb-ads-directory-v2">
            <div class="lmb-widget-header">
                <h3>Annonces Legales</h3>
                <p>Consultez vos annonces légales</p>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-box">
                    <form class="lmb-filters-form">
                        <div class="lmb-filter-grid">
                            <input type="search" placeholder="Rechercher par Réf..." name="filter_ref" class="lmb-filter-input">
                            <input type="search" placeholder="Rechercher par Société..." name="filter_company" class="lmb-filter-input">
                             <select name="filter_type" class="lmb-filter-select">
                                <option value="">Tous les Types d'Annonces</option>
                                <?php
                                global $wpdb;
                                $ad_types = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'ad_type' AND meta_value != '' ORDER BY meta_value ASC");
                                foreach ($ad_types as $type) {
                                    echo '<option value="'.esc_attr($type).'">'.esc_html($type).'</option>';
                                }
                                ?>
                            </select>
                            <input type="date" name="filter_date" class="lmb-filter-input">
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Réinitialiser</button>
                        </div>
                    </form>
                </div>

                <div class="lmb-table-container">
                    <table class="lmb-data-table">
                        <thead>
                            <tr>
                                <th>Réf</th>
                                <th>Société</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Journal</th>
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
