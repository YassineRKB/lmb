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
    
}