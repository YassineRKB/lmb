<?php
if (!defined('ABSPATH')) exit;

class LMB_Admin {
    private static $settings_tabs = [];
    private static $settings_sub_tabs = [];

    public static function init() {
        self::$settings_tabs = [
            'general'        => __('Général', 'lmb-core'),
            'templates'      => __('Modèles', 'lmb-core'),
            'notifications'  => __('Notifications', 'lmb-core'),
            'security'       => __('Sécurité', 'lmb-core'),
            'roles'          => __('Rôles et Utilisateurs', 'lmb-core'),
        ];

        self::$settings_sub_tabs = [
            'templates' => [
                'legal_ads'        => __('Modèles d\'Annonces Légales', 'lmb-core'),
                'accuse_newspaper' => __('Accusé et Journal', 'lmb-core'),
            ]
        ];

        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_admin_menu() {
        add_menu_page(
            __('LMB Core', 'lmb-core'),
            'LMB Core',
            'manage_options',
            'lmb-core',
            [__CLASS__, 'render_dashboard_page'],
            'dashicons-analytics',
            25
        );

        add_submenu_page(
            'lmb-core',
            __('Tableau de Bord', 'lmb-core'),
            __('Tableau de Bord', 'lmb-core'),
            'manage_options',
            'lmb-core',
            [__CLASS__, 'render_dashboard_page']
        );

        add_submenu_page(
            'lmb-core',
            __('Paramètres', 'lmb-core'),
            __('Paramètres', 'lmb-core'),
            'manage_options',
            'lmb-core-settings',
            [__CLASS__, 'render_settings_page']
        );

        add_submenu_page(
            'lmb-core',
            __('Journaux d\'Erreurs', 'lmb-core'),
            __('Journaux d\'Erreurs', 'lmb-core'),
            'manage_options',
            'lmb-error-logs',
            ['LMB_Error_Handler', 'render_logs_page']
        );
    }

    public static function register_settings() {
        register_setting('lmb_general_settings', 'lmb_bank_name');
        register_setting('lmb_general_settings', 'lmb_bank_iban');
        register_setting('lmb_general_settings', 'lmb_bank_account_holder');
        register_setting('lmb_general_settings', 'lmb_default_cost_per_ad');
        register_setting('lmb_legal_ads_settings', 'lmb_legal_ad_templates');
        register_setting('lmb_accuse_newspaper_settings', 'lmb_accuse_template_html');
        register_setting('lmb_accuse_newspaper_settings', 'lmb_logo_url');
        register_setting('lmb_accuse_newspaper_settings', 'lmb_signature_url');
        register_setting('lmb_notifications_settings', 'lmb_enable_email_notifications');
        register_setting('lmb_security_settings', 'lmb_protected_pages');
    }

    public static function collect_stats() {
        global $wpdb;
        $stats = [];

        $ad_counts = (array) wp_count_posts('lmb_legal_ad');
        $stats['ads_draft'] = $ad_counts['draft'] ?? 0;
        $stats['ads_pending'] = $ad_counts['pending'] ?? 0;
        $stats['ads_published'] = $ad_counts['publish'] ?? 0;

        $stats['users_total'] = count_users()['total_users'];
        $stats['news_total'] = wp_count_posts('lmb_newspaper')->publish ?? 0;
        
        return $stats;
    }

    public static function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour accéder à cette page.', 'lmb-core'));
        }

        $stats = self::collect_stats();
        ?>
        <div class="wrap">
            <h1>Tableau de Bord LMB Core</h1>

            <div class="lmb-grid" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-top:16px;">
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;">Total Utilisateurs</h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['users_total']); ?></p>
                </div>
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;">Annonces Légales (Publiées)</h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['ads_published']); ?></p>
                </div>
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;">Annonces Légales (Brouillon/En Attente)</h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['ads_draft'] + $stats['ads_pending']); ?></p>
                </div>
                <div class="card" style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                    <h2 style="margin-top:0;">Journaux</h2>
                    <p style="font-size:20px;margin:0;"><?php echo esc_html($stats['news_total']); ?></p>
                </div>
            </div>

            <div style="margin-top:24px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px;">
                <h2 style="margin-top:0;">Liens Rapides</h2>
                <ul>
                    <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=lmb_legal_ad')); ?>">Gérer les Annonces Légales</a></li>
                    <li><a href="<?php echo esc_url(admin_url('edit.php?post_type=lmb_newspaper')); ?>">Gérer les Journaux</a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=lmb-error-logs')); ?>">Voir les Journaux d'Erreurs</a></li>
                    <li><a href="<?php echo esc_url(admin_url('admin.php?page=lmb-core-settings')); ?>">Paramètres</a></li>
                </ul>
            </div>
        </div>
        <?php
    }

    public static function render_settings_page() {
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h2>Paramètres LMB Core</h2>
            <h2 class="nav-tab-wrapper">
                <?php
                foreach (self::$settings_tabs as $tab_key => $tab_name) {
                    $active_class = ($current_tab === $tab_key) ? 'nav-tab-active' : '';
                    echo '<a href="?page=lmb-core-settings&tab=' . esc_attr($tab_key) . '" class="nav-tab ' . esc_attr($active_class) . '">' . esc_html($tab_name) . '</a>';
                }
                ?>
            </h2>

            <?php
            if (isset(self::$settings_sub_tabs[$current_tab])) {
                $current_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : key(self::$settings_sub_tabs[$current_tab]);
                echo '<ul class="subsubsub">';
                $sub_tab_count = count(self::$settings_sub_tabs[$current_tab]);
                $i = 0;
                foreach (self::$settings_sub_tabs[$current_tab] as $sub_tab_key => $sub_tab_name) {
                    $i++;
                    $active_class = ($current_sub_tab === $sub_tab_key) ? 'current' : '';
                    $separator = ($i < $sub_tab_count) ? ' |' : '';
                    $url = '?page=lmb-core-settings&tab=' . esc_attr($current_tab) . '&sub_tab=' . esc_attr($sub_tab_key);
                    echo '<li><a href="' . esc_url($url) . '" class="' . esc_attr($active_class) . '">' . esc_html($sub_tab_name) . '</a>' . $separator . '</li>';
                }
                 echo '</ul><br class="clear">';
            }
            ?>

            <form method="post" action="options.php">
                <?php
                switch ($current_tab) {
                    case 'general':
                        self::render_general_tab();
                        break;
                    case 'templates':
                        $current_sub_tab = isset($_GET['sub_tab']) ? sanitize_key($_GET['sub_tab']) : 'legal_ads';
                        if ($current_sub_tab === 'accuse_newspaper') {
                            self::render_accuse_newspaper_tab();
                        } else {
                            self::render_legal_ads_sub_tab();
                        }
                        break;
                    case 'notifications':
                        self::render_notifications_tab();
                        break;
                    case 'security':
                        self::render_security_tab();
                        break;
                    case 'roles':
                        self::render_roles_tab();
                        break;
                    default:
                        self::render_general_tab();
                        break;
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private static function render_general_tab() {
        settings_fields('lmb_general_settings');
        ?>
        <h3>Détails Bancaires</h3>
        <p>Ces informations seront affichées aux utilisateurs lorsqu'ils devront effectuer un paiement.</p>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="lmb_bank_name">Nom de la Banque</label></th>
                <td><input type="text" id="lmb_bank_name" name="lmb_bank_name" value="<?php echo esc_attr(get_option('lmb_bank_name')); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="lmb_bank_iban">IBAN</label></th>
                <td><input type="text" id="lmb_bank_iban" name="lmb_bank_iban" value="<?php echo esc_attr(get_option('lmb_bank_iban')); ?>" class="regular-text" /></td>
            </tr>
             <tr valign="top">
                <th scope="row"><label for="lmb_bank_account_holder">Nom du Titulaire du Compte</label></th>
                <td><input type="text" id="lmb_bank_account_holder" name="lmb_bank_account_holder" value="<?php echo esc_attr(get_option('lmb_bank_account_holder')); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }

    private static function render_accuse_newspaper_tab() {
        settings_fields('lmb_accuse_newspaper_settings');
        ?>
        <h3>Paramètres du Modèle d'Accusé (Reçu)</h3>
        <p class="description">
            Configurez le modèle et les ressources pour le PDF d'accusé/reçu généré automatiquement.
        </p>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><label for="lmb_logo_url">URL de l'Image du Logo</label></th>
                <td>
                    <input type="text" id="lmb_logo_url" name="lmb_logo_url" value="<?php echo esc_attr(get_option('lmb_logo_url')); ?>" class="regular-text" />
                    <p class="description">Entrez l'URL complète du logo à afficher en haut de l'accusé.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="lmb_signature_url">URL de l'Image de Signature</label></th>
                <td>
                    <input type="text" id="lmb_signature_url" name="lmb_signature_url" value="<?php echo esc_attr(get_option('lmb_signature_url')); ?>" class="regular-text" />
                    <p class="description">Entrez l'URL complète de l'image de signature.</p>
                </td>
            </tr>
        </table>

        <h3>Modèle HTML d'Accusé</h3>
        <textarea name="lmb_accuse_template_html" rows="20" style="width:100%; font-family: monospace;"><?php echo esc_textarea(get_option('lmb_accuse_template_html', self::get_default_accuse_template())); ?></textarea>
        
        <h4>Espaces Réservés Disponibles:</h4>
        <ul style="list-style: inside; margin-left: 20px;">
            <li><code>{{lmb_logo_url}}</code></li>
            <li><code>{{journal_no}}</code></li>
            <li><code>{{ad_object}}</code></li>
            <li><code>{{legal_ad_link}}</code></li>
            <li><code>{{signature_url}}</code></li>
        </ul>
        <?php
    }

    private static function render_legal_ads_sub_tab() {
        settings_fields('lmb_legal_ads_settings');
        $all_ad_types = self::get_all_ad_types();
        $current_ad_type = isset($_GET['ad_type']) ? sanitize_text_field($_GET['ad_type']) : ($all_ad_types[0] ?? '');
        $all_templates = get_option('lmb_legal_ad_templates', []);
        
        ?>
        <p class="description">
            Sélectionnez un type d'annonce pour modifier son modèle. Utilisez des espaces réservés comme {{field_id}}.
        </p>
        
        <select onchange="if (this.value) window.location.href = this.value;">
            <?php foreach ($all_ad_types as $type): ?>
                <option value="?page=lmb-core-settings&tab=templates&sub_tab=legal_ads&ad_type=<?php echo urlencode($type); ?>" <?php selected($current_ad_type, $type); ?>>
                    <?php echo esc_html($type); ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <hr style="margin-top: 20px;">
        
        <?php
        if (!empty($current_ad_type)) {
            $current_ad_type_key = sanitize_key($current_ad_type);
            $field_name = 'lmb_legal_ad_templates[' . $current_ad_type_key . ']';
            $template_content = isset($all_templates[$current_ad_type_key]) ? $all_templates[$current_ad_type_key] : '';
            ?>
            <h3>Modèle pour: <strong><?php echo esc_html($current_ad_type); ?></strong></h3>
            <textarea name="<?php echo esc_attr($field_name); ?>" rows="20" style="width:100%;"><?php echo esc_textarea($template_content); ?></textarea>
            
            <?php
            // Data-loss prevention: Add hidden fields for all other templates
            foreach ($all_templates as $key => $value) {
                if ($key === $current_ad_type_key) continue;
                echo '<input type="hidden" name="lmb_legal_ad_templates[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
            }
        } else {
            echo '<p>Aucun type d\'annonce trouvé. Veuillez d\'abord créer une annonce.</p>';
        }
        ?>
        <?php
    }
    
    private static function render_notifications_tab() {
        settings_fields('lmb_notifications_settings');
        ?>
        <label>
            <input type="checkbox" name="lmb_enable_email_notifications" value="1" <?php checked(get_option('lmb_enable_email_notifications', 0), 1); ?>>
            Activer les notifications par email
        </label>
        <?php
    }

    private static function render_security_tab() { 
        settings_fields('lmb_security_settings');
        $protected_pages = get_option('lmb_protected_pages', []);
        $pages = get_pages();
        ?>
        <h3>Contrôle d'Accès aux Pages</h3>
        <p class="description">Configurez le contrôle d'accès pour des pages spécifiques basé sur les rôles utilisateur.</p>
        
        <table class="form-table" role="presentation">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Niveau d'Accès</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $page): ?>
                    <?php $page_protection = isset($protected_pages[$page->ID]) ? $protected_pages[$page->ID] : 'public'; ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($page->post_title); ?></strong>
                            <br><small><?php echo esc_html($page->post_name); ?></small>
                        </td>
                        <td>
                            <select name="lmb_protected_pages[<?php echo $page->ID; ?>]">
                                <option value="public" <?php selected($page_protection, 'public'); ?>>Accès Public</option>
                                <option value="logged_in" <?php selected($page_protection, 'logged_in'); ?>>Utilisateurs Connectés Uniquement</option>
                                <option value="admin_only" <?php selected($page_protection, 'admin_only'); ?>>Administrateurs Uniquement</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php }

    private static function render_roles_tab() { 
        ?>
        <p>La gestion des rôles est gérée ailleurs dans le plugin.</p>
        <?php
    }

    private static function get_all_ad_types() {
        // In a real-world scenario, you might get this from another source.
        return [
            'Constitution - SARL', 'Constitution - SARL AU', 'Liquidation - anticipee',
            'Liquidation - definitive', 'Modification - Capital', 'Modification - denomination',
            'Modification - gerant', 'Modification - objects', 'Modification - parts', 'Modification - seige'
        ];
    }

    public static function get_default_accuse_template() {
        // --- REPLACED: The entire return statement is now the new HTML design ---
        return '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Accuse de Publication</title>
        <style>
            /* Basic styles for PDF rendering */
            body { font-family: \'Roboto\', sans-serif; font-size: 12px; color: #333; }
            .container { max-width: 800px; margin: auto; background: #fff; }
            .header { display: table; width: 100%; border-bottom: 1px solid #eee; padding-bottom: 20px; }
            .header-left, .header-right { display: table-cell; vertical-align: top; }
            .header-right { text-align: right; }
            .main-content { margin-top: 40px; }
            .details-grid { display: table; width: 100%; }
            .details-left, .details-right { display: table-cell; width: 50%; vertical-align: top; }
            .details-right { text-align: right; }
            .footer { margin-top: 80px; text-align: center; font-size: 9px; color: #555; position: relative; }
            .signature-img { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); opacity: 0.9; }
            p { margin: 0 0 10px 0; }
            .detail-item { margin-bottom: 15px; }
            .detail-label { font-size: 10px; font-weight: bold; color: #777; text-transform: uppercase; }
            .detail-value { font-size: 14px; }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <img src="{{lmb_logo_url}}" alt="Logo" width="150">
                </div>
                <div class="header-right">
                    <h1 style="font-size: 28px; margin: 0;">ACCUSE DE PUBLICATION</h1>
                    <p>Date: {{publication_date}}</p>
                </div>
            </header>

            <!-- Main Content -->
            <main class="main-content">
                <div class="details-grid">
                    <!-- Left Column: Ad Details -->
                    <div class="details-left">
                        {{client_specific_info}}
                        <div class="detail-item">
                            <p class="detail-label">Société / Nom</p>
                            <p class="detail-value">{{companyName}}</p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Objet</p>
                            <p class="detail-value">Avis de {{ad_object}}</p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Réf. Annonce</p>
                            <p class="detail-value">{{ad_id}}</p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Journal N°</p>
                            <p class="detail-value">{{journal_no}}</p>
                        </div>
                        <div class="detail-item">
                            <p class="detail-label">Consulter Votre Annonce</p>
                            <a href="{{legal_ad_link}}">{{legal_ad_link}}</a>
                        </div>
                    </div>

                    <!-- Right Column: QR Code -->
                    <div class="details-right">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{legal_ad_link}}" alt="QR Code">
                        <p style="font-size: 10px; font-weight: bold;">SCAN ME TO READ</p>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="footer">
                <img src="{{signature_url}}" alt="Signature" width="200" class="signature-img">
                <br><br><br>
                <p>Directeur de publication : MOHAMED ELBACHIR LANSAR | License : 2022/23/01ص</p>
                <p>Adresse : RUE AHL LKHALIL OULD MHAMED N°08 ES-SEMARA</p>
                <p>ICE : 002924841000097 | TP : 77402556 | IF : 50611382 | CNSS : 4319969</p>
                <p>06 61 83 82 11 | 06 74 40 61 97 | 06 05 28 98 04 | 08 08 61 04 87</p>
                <p>lmbannonceslegales.com | ste.lmbgroup@gmail.com</p>
            </footer>
        </div>
    </body>
    </html>';
        }
}
