<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class LMB_Legal_Ads_Receipts_Widget extends Widget_Base {
    public function get_name() { return 'lmb_legal_ads_receipts'; }
    public function get_title() { return __('LMB Legal Ads Receipts', 'lmb-core'); }
    public function get_icon() { return 'eicon-document-file'; }
    public function get_categories() { return ['lmb-2']; }

    protected function render() {
        if (!is_user_logged_in()) {
            echo '<div class="lmb-notice lmb-notice-error"><p>' . esc_html__('You must be logged in to view your receipts.', 'lmb-core') . '</p></div>';
            return;
        }

        $user_id = get_current_user_id();
        $paged = max(1, (int)(get_query_var('paged') ? get_query_var('paged') : 1));

        // Get user's published legal ads
        $ads_query = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'post_status' => 'publish',
            'author' => $user_id,
            'posts_per_page' => 10,
            'paged' => $paged,
            'meta_query' => [
                [
                    'key' => 'lmb_status',
                    'value' => 'published',
                    'compare' => '='
                ]
            ],
            'orderby' => 'date',
            'order' => 'DESC'
        ]);

        $ads = $ads_query->posts;
        ?>
        <div class="lmb-legal-ads-receipts-widget">
            <div class="lmb-widget-header">
                <h3><i class="fas fa-receipt"></i> <?php esc_html_e('Legal Ads Receipts', 'lmb-core'); ?></h3>
            </div>

            <div class="lmb-widget-content">
                <?php if (!empty($ads)): ?>
                    <div class="lmb-receipts-table-container">
                        <table class="lmb-receipts-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Ad ID', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Publication Date', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Ad Type', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Company', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Cost', 'lmb-core'); ?></th>
                                    <th><?php esc_html_e('Actions', 'lmb-core'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ads as $ad): ?>
                                    <?php
                                    $ad_type = get_post_meta($ad->ID, 'ad_type', true);
                                    $company_name = get_post_meta($ad->ID, 'company_name', true);
                                    $cost = get_user_meta($user_id, 'lmb_cost_per_ad', true) ?: get_option('lmb_default_cost_per_ad', 1);
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo esc_html($ad->ID); ?></strong></td>
                                        <td><?php echo esc_html(get_the_date('Y-m-d H:i', $ad->ID)); ?></td>
                                        <td><?php echo esc_html($ad_type ?: '-'); ?></td>
                                        <td><?php echo esc_html($company_name ?: '-'); ?></td>
                                        <td>
                                            <strong><?php echo esc_html($cost); ?></strong> 
                                            <small><?php esc_html_e('points', 'lmb-core'); ?></small>
                                        </td>
                                        <td>
                                            <button class="lmb-btn lmb-btn-sm lmb-btn-primary lmb-download-receipt" 
                                                    data-ad-id="<?php echo esc_attr($ad->ID); ?>"
                                                    data-ad-type="<?php echo esc_attr($ad_type); ?>">
                                                <i class="fas fa-download"></i> <?php esc_html_e('Download Receipt', 'lmb-core'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($ads_query->max_num_pages > 1): ?>
                        <div class="lmb-pagination">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $paged,
                                'total' => $ads_query->max_num_pages,
                                'prev_text' => '&laquo; ' . esc_html__('Previous', 'lmb-core'),
                                'next_text' => esc_html__('Next', 'lmb-core') . ' &raquo;'
                            ]);
                            ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="lmb-no-receipts">
                        <div class="lmb-empty-state">
                            <i class="fas fa-receipt fa-3x"></i>
                            <h4><?php esc_html_e('No Receipts Found', 'lmb-core'); ?></h4>
                            <p><?php esc_html_e('You don\'t have any published legal ads yet. Once your ads are approved and published, their receipts will appear here.', 'lmb-core'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <style>
        .lmb-legal-ads-receipts-widget {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .lmb-widget-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .lmb-widget-header h3 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .lmb-widget-content {
            padding: 20px;
        }
        .lmb-receipts-table-container {
            overflow-x: auto;
        }
        .lmb-receipts-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        .lmb-receipts-table th,
        .lmb-receipts-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .lmb-receipts-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .lmb-receipts-table tbody tr:hover {
            background: #f8f9fa;
        }
        .lmb-no-receipts {
            text-align: center;
            padding: 40px 20px;
        }
        .lmb-empty-state {
            max-width: 400px;
            margin: 0 auto;
        }
        .lmb-empty-state i {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .lmb-empty-state h4 {
            color: #495057;
            margin-bottom: 10px;
        }
        .lmb-empty-state p {
            color: #6c757d;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .lmb-receipts-table {
                font-size: 14px;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.lmb-download-receipt').on('click', function() {
                const adId = $(this).data('ad-id');
                const adType = $(this).data('ad-type');
                const button = $(this);
                const originalText = button.html();
                
                button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> <?php esc_js_e('Generating...', 'lmb-core'); ?>');
                
                // Generate and download receipt PDF
                $.post(ajaxurl, {
                    action: 'lmb_generate_receipt_pdf',
                    nonce: '<?php echo wp_create_nonce('lmb_receipt_nonce'); ?>',
                    ad_id: adId,
                    ad_type: adType
                }, function(response) {
                    if (response.success && response.data.pdf_url) {
                        // Open PDF in new tab
                        window.open(response.data.pdf_url, '_blank');
                    } else {
                        alert('<?php esc_js_e('Error generating receipt:', 'lmb-core'); ?> ' + (response.data.message || '<?php esc_js_e('Unknown error', 'lmb-core'); ?>'));
                    }
                }).fail(function() {
                    alert('<?php esc_js_e('Failed to generate receipt. Please try again.', 'lmb-core'); ?>');
                }).always(function() {
                    button.prop('disabled', false).html(originalText);
                });
            });
        });
        </script>
        <?php
        wp_reset_postdata();
    }
}

// Add AJAX handler for receipt generation
add_action('wp_ajax_lmb_generate_receipt_pdf', function() {
    check_ajax_referer('lmb_receipt_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Access denied']);
    }
    
    $ad_id = intval($_POST['ad_id']);
    $ad_type = sanitize_text_field($_POST['ad_type']);
    $user_id = get_current_user_id();
    
    $ad = get_post($ad_id);
    if (!$ad || $ad->post_type !== 'lmb_legal_ad' || $ad->post_author != $user_id) {
        wp_send_json_error(['message' => 'Ad not found or access denied']);
    }
    
    // Generate receipt PDF based on ad type
    try {
        $pdf_url = LMB_Receipt_Generator::create_receipt_pdf($ad_id, $ad_type);
        
        if ($pdf_url) {
            wp_send_json_success(['pdf_url' => $pdf_url]);
        } else {
            wp_send_json_error(['message' => 'Failed to generate PDF']);
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
});

// Receipt Generator Class
class LMB_Receipt_Generator {
    public static function create_receipt_pdf($ad_id, $ad_type) {
        $ad = get_post($ad_id);
        if (!$ad) return false;
        
        $user = get_userdata($ad->post_author);
        $company_name = get_post_meta($ad_id, 'company_name', true);
        $full_text = get_post_meta($ad_id, 'full_text', true);
        $cost = get_user_meta($ad->post_author, 'lmb_cost_per_ad', true) ?: get_option('lmb_default_cost_per_ad', 1);
        
        // Generate receipt HTML based on ad type
        $receipt_html = self::generate_receipt_html($ad, $user, $company_name, $full_text, $cost, $ad_type);
        
        // Generate PDF
        $filename = 'receipt-ad-' . $ad_id . '.pdf';
        return LMB_PDF_Generator::generate_html_pdf($filename, $receipt_html, 'Receipt for Ad #' . $ad_id);
    }
    
    private static function generate_receipt_html($ad, $user, $company_name, $full_text, $cost, $ad_type) {
        $publication_date = get_the_date('Y/m/d', $ad->ID);
        $current_year = date('Y');
        
        // Base template structure
        $html = '
        <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="margin: 0; font-size: 24px;">ACCUSE DE PUBLICATION</h1>
                <p style="margin: 5px 0;">Directeur de publication : MOHAMED ELBACHIR LANSAR</p>
                <p style="margin: 5px 0;">' . $publication_date . ' : License</p>
                <p style="margin: 5px 0;">RUE AHL LKHALIL OULD MHAMED N°08 ES-SEMARA</p>
                <p style="margin: 5px 0;">ICE :002924841000097-TP :77402556-IF :50611382-CNSS :4319969</p>
                <p style="margin: 5px 0;">RIB : 007260000899200000033587</p>
                <p style="margin: 5px 0; font-weight: bold;">lmbannonceslegales.com</p>
                <p style="margin: 5px 0;">ste.lmbgroup@gmail.com</p>
                <p style="margin: 5px 0;">87 04 61 08 08 / 04 00 98 28 05 / 11 82 83 61 06 / 97 61 40 74 06</p>
            </div>
            
            <hr style="border: 1px solid #000; margin: 20px 0;">
            
            <div style="margin-bottom: 20px;">
                ' . self::get_ad_type_content($ad_type, $company_name, $full_text, $ad) . '
            </div>
            
            <hr style="border: 1px solid #000; margin: 20px 0;">
            
            <div style="text-align: right; margin-top: 30px;">
                <p style="margin: 5px 0;">Pour extrait et mention</p>
                <p style="margin: 5px 0; font-weight: bold;">' . esc_html($company_name ?: 'Société') . '</p>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background-color: #f5f5f5; border-radius: 5px;">
                <p style="margin: 0; font-weight: bold;">Votre annonce est publiée en ligne via le lien suivant :</p>
                <p style="margin: 5px 0; color: #0066cc;">https://lmbannonceslegales.com/' . $current_year . '/' . date('m') . '/' . sanitize_title($company_name ?: 'annonce-' . $ad->ID) . '/</p>
            </div>
        </div>';
        
        return $html;
    }
    
    private static function get_ad_type_content($ad_type, $company_name, $full_text, $ad) {
        // Extract key information from the full text or meta fields
        $siege_social = get_post_meta($ad->ID, 'siege_social', true) ?: 'ADRESSE NON SPECIFIEE';
        $capital = get_post_meta($ad->ID, 'capital', true) ?: '100.000,00';
        $objet = get_post_meta($ad->ID, 'objet', true) ?: 'Activité commerciale générale';
        $duree = get_post_meta($ad->ID, 'duree', true) ?: '99';
        $gerant = get_post_meta($ad->ID, 'gerant', true) ?: 'NON SPECIFIE';
        $rc_number = get_post_meta($ad->ID, 'rc_number', true) ?: 'EN COURS';
        $tribunal = get_post_meta($ad->ID, 'tribunal', true) ?: 'LAAYOUNE';
        
        switch (strtolower($ad_type)) {
            case 'constitution - sarl':
            case 'constitution sarl':
                return '
                <h2 style="text-align: center; margin-bottom: 20px;">AVIS DE CONSTITUTION</h2>
                <h3 style="text-align: center; margin-bottom: 20px;">« ' . esc_html($company_name) . ' » SARL</h3>
                <p style="margin-bottom: 10px;"><strong>Constitution</strong></p>
                <p style="margin-bottom: 15px;">Aux termes d\'un acte sous seing privé en date du ' . get_the_date('d/m/Y', $ad->ID) . ' il a été établi les statuts d\'une SARL dont les caractéristiques sont les suivantes :</p>
                <ul style="list-style: none; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">• <strong>Dénomination :</strong> « ' . esc_html($company_name) . ' » SARL</li>
                    <li style="margin-bottom: 8px;">• <strong>Siège social :</strong> ' . esc_html($siege_social) . '</li>
                    <li style="margin-bottom: 8px;">• <strong>Objet :</strong> ' . esc_html($objet) . '</li>
                    <li style="margin-bottom: 8px;">• <strong>Durée :</strong> ' . esc_html($duree) . ' ans</li>
                    <li style="margin-bottom: 8px;">• <strong>Capital social et apports :</strong> le capital social est fixé à ' . esc_html($capital) . ' dhs</li>
                    <li style="margin-bottom: 8px;">• <strong>Gérance de la société :</strong> ' . esc_html($gerant) . '</li>
                    <li style="margin-bottom: 8px;">• <strong>Année sociale :</strong> du 1er janvier au 31 décembre</li>
                    <li style="margin-bottom: 8px;">• <strong>RC :</strong> le dépôt légal a été effectué au Tribunal de 1ER instance de ' . esc_html($tribunal) . ' sous le n° RC ' . esc_html($rc_number) . '.</li>
                </ul>';
                
            case 'constitution - sarl au':
            case 'constitution sarl au':
                return '
                <h2 style="text-align: center; margin-bottom: 20px;">AVIS DE CONSTITUTION</h2>
                <h3 style="text-align: center; margin-bottom: 20px;">« ' . esc_html($company_name) . ' » SARL AU</h3>
                <p style="margin-bottom: 10px;"><strong>Constitution</strong></p>
                <p style="margin-bottom: 15px;">Aux termes d\'un acte sous seing privé en date du ' . get_the_date('d/m/Y', $ad->ID) . ' il a été établi les statuts d\'une SARL à associé unique dont les caractéristiques sont les suivantes :</p>
                <ul style="list-style: none; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">• <strong>Dénomination :</strong> « ' . esc_html($company_name) . ' » SARL AU</li>
                    <li style="margin-bottom: 8px;">• <strong>Siège social :</strong> ' . esc_html($siege_social) . '</li>
                    <li style="margin-bottom: 8px;">• <strong>Objet :</strong> ' . esc_html($objet) . '</li>
                    <li style="margin-bottom: 8px;">• <strong>Durée :</strong> ' . esc_html($duree) . ' ans</li>
                    <li style="margin-bottom: 8px;">• <strong>Capital social :</strong> ' . esc_html($capital) . ' dhs</li>
                    <li style="margin-bottom: 8px;">• <strong>Associé unique et gérant :</strong> ' . esc_html($gerant) . '</li>
                    <li style="margin-bottom: 8px;">• <strong>Année sociale :</strong> du 1er janvier au 31 décembre</li>
                    <li style="margin-bottom: 8px;">• <strong>RC :</strong> le dépôt légal a été effectué au Tribunal de 1ER instance de ' . esc_html($tribunal) . ' sous le n° RC ' . esc_html($rc_number) . '.</li>
                </ul>';
                
            default:
                // Generic template for other ad types
                return '
                <h2 style="text-align: center; margin-bottom: 20px;">' . esc_html(strtoupper($ad_type)) . '</h2>
                <h3 style="text-align: center; margin-bottom: 20px;">« ' . esc_html($company_name) . ' »</h3>
                <div style="line-height: 1.6;">
                    ' . nl2br(esc_html($full_text)) . '
                </div>';
        }
    }
}