<?php
// FILE: elementor/widgets/class-lmb-ads-directory-widget.php
use Elementor\Widget_Base;
use WP_Query;

if (!defined('ABSPATH')) exit;

class LMB_Ads_Directory_Widget extends Widget_Base {
    public function get_name() { return 'lmb_ads_directory'; }
    public function get_title() { return __('LMB Ads Directory V2','lmb-core'); }
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
            $ad_id = intval(substr($_GET['legal-ad'], 0, strpos($_GET['legal-ad'], '-')));
            $this->render_single_ad($ad_id);
        } else {
            $this->render_directory_view();
        }
    }

    protected function render_single_ad($ad_id) {
        $ad = get_post($ad_id);
        if ($ad && $ad->post_type == 'lmb_legal_ad' && $ad->post_status == 'publish') {
            ?>
            <div class="lmb-ads-directory-v2 lmb-single-ad-container">
                <div class="lmb-single-ad-header">
                    <div>
                        <h1><?php echo esc_html($ad->post_title); ?></h1>
                    </div>
                    <div class="lmb-single-ad-actions">
                        <a href="<?php echo esc_url(remove_query_arg('legal-ad')); ?>" class="lmb-btn lmb-btn-view">
                            <i class="fas fa-arrow-left"></i> <?php esc_html_e('Back to Directory', 'lmb-core'); ?>
                        </a>
                    </div>
                </div>
                <div class="lmb-single-ad-content">
                    <?php echo wp_kses_post(get_post_meta($ad_id, 'full_text', true)); ?>
                </div>
            </div>
            <?php
        } else {
            echo '<p>' . esc_html__('Legal ad not found or is not published.', 'lmb-core') . '</p>';
        }
    }

    protected function render_directory_view() {
        ?>
        <div class="lmb-ads-directory-v2">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-search"></i> <?php esc_html_e('Legal Ads Directory', 'lmb-core'); ?></h3>
            </div>
            <div class="lmb-widget-content">
                <div class="lmb-filters-box">
                    <form class="lmb-filters-form">
                        <div class="lmb-filter-grid">
                            <input type="search" placeholder="<?php esc_attr_e('Search by Ref...', 'lmb-core'); ?>" name="filter_ref" class="lmb-filter-input">
                            <input type="search" placeholder="<?php esc_attr_e('Search by Company...', 'lmb-core'); ?>" name="filter_company" class="lmb-filter-input">
                             <select name="filter_type" class="lmb-filter-select">
                                <option value=""><?php esc_html_e('All Ad Types', 'lmb-core'); ?></option>
                                <?php
                                global $wpdb;
                                $ad_types = $wpdb->get_col("SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'ad_type' AND meta_value != '' ORDER BY meta_value ASC");
                                foreach ($ad_types as $type) {
                                    echo '<option value="'.esc_attr($type).'">'.esc_html($type).'</option>';
                                }
                                ?>
                            </select>
                            <input type="date" name="filter_date" class="lmb-filter-input">
                            <button type="reset" class="lmb-btn lmb-btn-view"><i class="fas fa-undo"></i> Reset</button>
                        </div>
                    </form>
                </div>

                <div class="lmb-table-container">
                    <table class="lmb-data-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Ref', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Company / Title', 'lmb-core'); ?></th>
                                <th><?php esc_html_e('Type', 'lmb-core'); ?></th>
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