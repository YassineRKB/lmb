<?php
// FILE: includes/class-lmb-maintenance-utilities.php
if (!defined('ABSPATH')) exit;

class LMB_Maintenance_Utilities {

    public static function init() {
        // Add a new submenu page under "LMB Core"
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'lmb-core',                             // Parent slug
            __('LMB Utilities', 'lmb-core'),        // Page title
            __('Utilities', 'lmb-core'),            // Menu title
            'manage_options',                       // Capability
            'lmb-utilities',                        // Menu slug
            [__CLASS__, 'render_utilities_page']    // Callback function
        );
    }

    public static function render_utilities_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p>Utilisez ces outils pour corriger les problèmes de données courants dans le système.</p>

            <div class="card">
                <h2><span class="dashicons dashicons-editor-spellcheck" style="vertical-align: middle;"></span> Nettoyer les Numéros de Journal</h2>
                <p>
                    Cet outil recherche les annonces légales où le "Numéro de Journal" a été saisi avec du texte supplémentaire (par exemple, "Journal-148" au lieu de "148").<br>
                    Il supprimera tous les caractères non numériques pour ne laisser que le numéro, corrigeant ainsi les problèmes de recherche.
                </p>
                
                <?php
                // Handle the form submission to run the fix
                if (isset($_POST['lmb_run_journal_fix']) && check_admin_referer('lmb_journal_fix_nonce')) {
                    self::run_journal_number_fix();
                }
                
                // Find problematic ads to show the user
                $problematic_ads = self::find_problematic_journal_numbers();
                
                if (!empty($problematic_ads)) {
                    echo '<h3><strong style="color: #d63638;">' . count($problematic_ads) . ' annonce(s) affectée(s) trouvée(s) :</strong></h3>';
                    echo '<ul style="list-style-type: disc; padding-left: 20px;">';
                    foreach ($problematic_ads as $ad) {
                        echo '<li>Annonce ID: <strong>' . esc_html($ad->ID) . '</strong> | Numéro de Journal Actuel: <strong style="color: #d63638;">"' . esc_html($ad->journal_no) . '"</strong></li>';
                    }
                    echo '</ul>';
                    ?>
                    <form method="post">
                        <?php wp_nonce_field('lmb_journal_fix_nonce'); ?>
                        <input type="hidden" name="lmb_run_journal_fix" value="1">
                        <?php submit_button('Corriger tous les numéros de journal ci-dessus'); ?>
                    </form>
                    <?php
                } else {
                    echo '<h3><strong style="color: #00a32a;">Aucune annonce avec des numéros de journal mal formatés n\'a été trouvée. Tout est en ordre !</strong></h3>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Finds ads where the 'lmb_journal_no' meta value contains non-numeric characters.
     * @return array
     */
    private static function find_problematic_journal_numbers() {
        global $wpdb;
        $results = $wpdb->get_results("
            SELECT p.ID, pm.meta_value as journal_no
            FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'lmb_legal_ad'
            AND pm.meta_key = 'lmb_journal_no'
            AND pm.meta_value REGEXP '[^0-9]'
        ");
        return $results;
    }

    /**
     * Runs the process to sanitize all problematic journal numbers.
     */
    private static function run_journal_number_fix() {
        $problematic_ads = self::find_problematic_journal_numbers();
        $fixed_count = 0;

        if (!empty($problematic_ads)) {
            foreach ($problematic_ads as $ad) {
                // Sanitize by removing all non-digit characters
                $sanitized_number = preg_replace('/[^0-9]/', '', $ad->journal_no);
                
                // Update the post meta with the clean number
                if ($sanitized_number !== $ad->journal_no) {
                    update_post_meta($ad->ID, 'lmb_journal_no', $sanitized_number);
                    $fixed_count++;
                }
            }
        }

        if ($fixed_count > 0) {
            add_settings_error('lmb_utilities_notices', 'journal_fix_success', $fixed_count . ' numéro(s) de journal ont été corrigés avec succès.', 'success');
        } else {
            add_settings_error('lmb_utilities_notices', 'journal_fix_none', 'Aucun numéro de journal n\'a nécessité de correction.', 'info');
        }
        settings_errors('lmb_utilities_notices');
    }
}