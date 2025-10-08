<?php
// FILE: includes/class-lmb-cpt.php

if (!defined('ABSPATH')) exit;

class LMB_CPT {
    public static function init() {
        add_action('init', [__CLASS__, 'register_post_types']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_lmb_package', [__CLASS__, 'save_package_meta']);
        add_action('save_post_lmb_newspaper', [__CLASS__, 'save_newspaper_meta']);
        add_filter('post_type_link', [__CLASS__, 'custom_post_type_link'], 10, 2);
        add_filter('post_row_actions', [__CLASS__, 'add_regenerate_row_action'], 10, 2);
        add_action('admin_init', [__CLASS__, 'handle_regenerate_action']);
        add_action('admin_notices', [__CLASS__, 'show_regenerated_notice']);

        // --- New Hooks for Legal Ad Date Quick Edit ---
        add_filter('manage_lmb_legal_ad_posts_columns', [__CLASS__, 'add_date_column']);
        add_action('manage_lmb_legal_ad_posts_custom_column', [__CLASS__, 'render_date_column'], 10, 2);
        add_action('quick_edit_custom_box', [__CLASS__, 'quick_edit_date_field'], 10, 2);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        // --- End New Hooks ---
    }

    public static function register_post_types() {
        register_post_type('lmb_legal_ad', [
            'labels' => ['name' => __('Annonces Légales', 'lmb-core'), 'singular_name' => __('Annonce Légale', 'lmb-core'), 'add_new_item' => __('Ajouter Nouvelle Annonce Légale', 'lmb-core')],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'has_archive' => false, // This is the fix
            'rewrite' => ['slug' => 'lmb_legal_ad', 'with_front' => false],
            'supports' => ['title', 'author', 'editor'],
            'menu_icon' => 'dashicons-media-text',
            'publicly_queryable' => true,
            'query_var' => true,
            'exclude_from_search' => false,
        ]);

        register_post_type('lmb_newspaper', [
            'labels' => ['name' => __('Journaux', 'lmb-core'), 'singular_name' => __('Journal', 'lmb-core'), 'add_new_item' => __('Télécharger Nouveau Journal', 'lmb-core')],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'has_archive' => 'journaux',
            'rewrite' => ['slug' => 'journaux', 'with_front' => false],
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-media-document',
        ]);

        register_post_type('lmb_payment', [
            'labels' => ['name' => __('Paiements', 'lmb-core'), 'singular_name' => __('Paiement', 'lmb-core')],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'supports' => ['title'],
            'menu_icon' => 'dashicons-money-alt',
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
        ]);

        register_post_type('lmb_package', [
            'labels' => ['name' => __('Packages', 'lmb-core'), 'singular_name' => __('Package', 'lmb-core'), 'add_new_item' => __('Ajouter Nouveau Package', 'lmb-core')],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'lmb-core',
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-products',
        ]);
    }
    
    public static function add_meta_boxes() {
        add_meta_box('lmb_package_details', __('Détails du Package', 'lmb-core'), [__CLASS__, 'render_package_metabox'], 'lmb_package', 'normal', 'high');
        add_meta_box('lmb_newspaper_pdf', __('PDF du Journal', 'lmb-core'), [__CLASS__, 'render_newspaper_metabox'], 'lmb_newspaper', 'normal', 'high');
        self::add_generation_meta_box();
    }

    public static function render_package_metabox($post) {
        wp_nonce_field('lmb_save_package_meta', 'lmb_package_nonce');
        ?>
        <table class="form-table">
            <tr>
                <th><label for="lmb_price">Prix (MAD)</label></th>
                <td><input type="number" step="0.01" id="lmb_price" name="price" value="<?php echo esc_attr(get_post_meta($post->ID, 'price', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lmb_points">Points Attribués</label></th>
                <td><input type="number" id="lmb_points" name="points" value="<?php echo esc_attr(get_post_meta($post->ID, 'points', true)); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="lmb_cost_per_ad">Nouveau Coût Par Annonce</label></th>
                <td><input type="number" id="lmb_cost_per_ad" name="cost_per_ad" value="<?php echo esc_attr(get_post_meta($post->ID, 'cost_per_ad', true)); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }

    public static function save_package_meta($post_id) {
        if (!isset($_POST['lmb_package_nonce']) || !wp_verify_nonce($_POST['lmb_package_nonce'], 'lmb_save_package_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        foreach (['price', 'points', 'cost_per_ad'] as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
    }

    public static function render_newspaper_metabox($post) {
        wp_nonce_field('lmb_save_newspaper_meta', 'lmb_newspaper_nonce');
        $pdf_id = get_post_meta($post->ID, 'newspaper_pdf', true);
        ?>
        <p>
            <input type="hidden" name="newspaper_pdf" id="lmb_newspaper_pdf_id" value="<?php echo esc_attr($pdf_id); ?>" />
            <button type="button" class="button" id="lmb_upload_pdf_button">Télécharger PDF</button>
            <span id="lmb_pdf_filename" style="margin-left: 10px;"><?php echo $pdf_id ? basename(get_attached_file($pdf_id)) : 'Aucun fichier sélectionné.'; ?></span>
        </p>
        <script>
        jQuery(document).ready(function($){
            var frame;
            $('#lmb_upload_pdf_button').on('click', function(e){
                e.preventDefault();
                if(frame){ frame.open(); return; }
                frame = wp.media({ title: 'Sélectionner PDF du Journal', button: { text: 'Utiliser ce PDF' }, multiple: false, library: { type: 'application/pdf' } });
                frame.on('select', function(){
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#lmb_newspaper_pdf_id').val(attachment.id);
                    $('#lmb_pdf_filename').text(attachment.filename);
                });
                frame.open();
            });
        });
        </script>
        <?php
    }

    public static function save_newspaper_meta($post_id) {
        if (!isset($_POST['lmb_newspaper_nonce']) || !wp_verify_nonce($_POST['lmb_newspaper_nonce'], 'lmb_save_newspaper_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (isset($_POST['newspaper_pdf'])) {
            update_post_meta($post_id, 'newspaper_pdf', intval($_POST['newspaper_pdf']));
        }
    }
    public static function add_generation_meta_box() {
        add_meta_box(
            'lmb_generator_actions',
            __('Actions', 'lmb-core'),
            [__CLASS__, 'render_generator_metabox'],
            'lmb_legal_ad', // Show on Legal Ad CPT
            'side',         // Position on the side
            'high'
        );
    }

    // Add this function inside the LMB_CPT class
    public static function render_generator_metabox($post) {
        ?>
        <p>
            <button type="button" id="lmb-regenerate-text-btn" class="button">
                <i class="fas fa-sync-alt"></i> Régénérer le Texte à partir du Modèle
            </button>
        </p>
        <p>
            <button type="button" id="lmb-generate-pdf-btn" class="button button-primary">
                <i class="fas fa-file-pdf"></i> Générer PDF
            </button>
        </p>
        <div id="lmb-generator-feedback" style="margin-top:10px;"></div>
        <p class="description">
            Utilisez 'Régénérer le Texte' pour mettre à jour le contenu de l'annonce à partir du dernier modèle. 'Générer PDF' sauvegardera le contenu actuel en PDF.
        </p>
        <?php
    }

    public static function custom_post_type_link($post_link, $post) {
        if ($post->post_type == 'lmb_legal_ad') {
            $announces_page = get_page_by_path('announces');
            if ($announces_page) {
                return add_query_arg('legal-ad', $post->ID . '-' . $post->post_name, get_permalink($announces_page));
            }
        }
        return $post_link;
    }
    
    /**
     * Adds a "Regenerate" link to the quick actions on the Legal Ads list page.
     */
    public static function add_regenerate_row_action($actions, $post) {
        if ($post->post_type === 'lmb_legal_ad') {
            $nonce = wp_create_nonce('lmb_regenerate_ad_' . $post->ID);
            $url = admin_url('edit.php?post_type=lmb_legal_ad&lmb_action=regenerate&post_id=' . $post->ID . '&_wpnonce=' . $nonce);
            $actions['lmb_regenerate'] = '<a href="' . esc_url($url) . '">' . __('Régénérer', 'lmb-core') . '</a>';
        }
        return $actions;
    }

    /**
     * Handles the regeneration logic when the quick action link is clicked.
     */
    public static function handle_regenerate_action() {
        // Check if our action, post_id, and nonce are set
        if (
            isset($_GET['lmb_action']) &&
            $_GET['lmb_action'] === 'regenerate' &&
            isset($_GET['post_id']) &&
            isset($_GET['_wpnonce'])
        ) {
            $post_id = intval($_GET['post_id']);

            // Verify the nonce to make sure the request is legitimate
            if (wp_verify_nonce($_GET['_wpnonce'], 'lmb_regenerate_ad_' . $post_id)) {
                // Call the existing function to regenerate the ad content
                LMB_Form_Handler::generate_and_save_formatted_text($post_id);

                // Redirect back to the ads list with a success message
                wp_redirect(admin_url('edit.php?post_type=lmb_legal_ad&lmb_regenerated=1'));
                exit;
            } else {
                // Nonce is invalid, show an error
                wp_die(__('Invalid security token.', 'lmb-core'));
            }
        }
    }

    /**
     * Shows an admin notice when an ad has been successfully regenerated.
     */
    public static function show_regenerated_notice() {
        if (isset($_GET['lmb_regenerated']) && $_GET['lmb_regenerated'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Annonce régénérée avec succès.', 'lmb-core') . '</p></div>';
        }
    }

    /**
     * Adds the Date column to the lmb_legal_ad list table for Quick Edit access.
     */
    public static function add_date_column($columns) {
        // Only run for the lmb_legal_ad post type list table
        global $post_type;
        if ($post_type !== 'lmb_legal_ad') {
            return $columns;
        }

        $new_columns = [];
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            // Insert our custom column right after the 'Title' column for better visibility
            if ($key === 'title') {
                $new_columns['lmb_date'] = __('Date Pub.', 'lmb-core');
            }
        }
        
        // This handles cases where 'title' might not be the right insertion point, 
        // ensuring the new column is still added (e.g., before the default 'date' column).
        if (!isset($new_columns['lmb_date']) && isset($new_columns['date'])) {
            $date_column = $new_columns['date'];
            unset($new_columns['date']);
            $new_columns['lmb_date'] = __('Date Pub.', 'lmb-core');
            $new_columns['date'] = $date_column;
        }

        return $new_columns;
    }

    /**
     * Renders the content for the custom 'lmb_date' column.
     * It displays the current post date and the data required for Quick Edit.
     */
    public static function render_date_column($column, $post_id) {
        if ($column === 'lmb_date') {
            $post = get_post($post_id);
            
            // Format the date for the HTML datetime-local input (YYYY-MM-DDTHH:MM)
            $datetime_input_format = get_the_date('Y-m-d\TH:i:s', $post_id);
            // Get the display format (Date and Time)
            $display_format = get_option('date_format') . ' ' . get_option('time_format');

            // The link acts as the Quick Edit launcher when clicked (handled by JS)
            echo '<a href="#" class="lmb-date-quick-edit" data-post-id="' . $post_id . '" data-post-date="' . esc_attr($datetime_input_format) . '">' . date_i18n($display_format, strtotime($post->post_date)) . '</a>';

            // Hidden element for the JS to easily identify the row if needed, though data-post-id on the link is primary
            echo '<div class="hidden" id="lmb_ad_post_id_' . $post_id . '"></div>';
        }
    }

    /**
     * Outputs the custom date field in the Quick Edit section for lmb_legal_ad posts.
     */
    public static function quick_edit_date_field($column_name, $post_type) {
        if ($post_type !== 'lmb_legal_ad' || $column_name !== 'lmb_date') {
            return;
        }

        // We use WordPress's inline-edit-col-right and inline-edit-date classes for native look and feel
        ?>
        <fieldset class="inline-edit-col-right inline-edit-date">
            <div class="inline-edit-col inline-edit-lmb-date">
                <label class="alignleft">
                    <span class="title"><?php _e('Date & Heure de Publication', 'lmb-core'); ?></span>
                    <input type="datetime-local" name="lmb_post_date" value="" class="lmb_post_date_input" />
                    <span class="lmb-date-notice notice-alt notice-error" style="display:none; margin-top:5px;"></span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Enqueues the necessary admin scripts for Quick Edit functionality.
     */
    public static function enqueue_admin_assets($hook) {
        global $post_type;
        // Only load on the legal ads list page
        if ($hook !== 'edit.php' || $post_type !== 'lmb_legal_ad') {
            return;
        }
        
        // The script that handles the quick edit interaction and AJAX
        $plugin_url = plugin_dir_url(__FILE__);
        
        wp_enqueue_script(
            'lmb-quick-edit',
            $plugin_url . '../assets/js/lmb-quick-edit.js',
            ['jquery', 'inline-edit-post'], // Depend on jQuery and WordPress's inline-edit script
            false, // Placeholder for version
            true
        );
        
        // Localize script for AJAX parameters
        wp_localize_script(
            'lmb-quick-edit',
            'lmb_quick_edit_vars',
            [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('lmb_quick_edit_date_nonce'),
                'error_message' => __('Erreur lors de la mise à jour de la date.', 'lmb-core'),
                'date_error_message' => __('Veuillez saisir une date et heure valides.', 'lmb-core'),
            ]
        );
    }
}