<?php
if (!defined('ABSPATH')) { exit; }

class LMB_User_Dashboard {
    public static function init() {
        add_shortcode('lmb_user_ads', [__CLASS__, 'shortcode_ads']);
        add_action('admin_post_lmb_user_publish_ad', [__CLASS__, 'handle_publish']);
        add_action('admin_post_nopriv_lmb_user_publish_ad', function(){ wp_die('Not allowed'); });
    }

    public static function shortcode_ads($atts = []) {
        if (!is_user_logged_in()) return '<p>'.esc_html__('Vous devez être connecté.', 'lmb-core').'</p>';
        $user_id = get_current_user_id();
        $balance = LMB_Points::get($user_id);
        $cost    = (int) get_option('lmb_points_per_ad', 1);

        $q = new WP_Query([
            'post_type' => 'lmb_legal_ad',
            'post_status' => ['draft', 'pending', 'publish'],
            'author' => $user_id,
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        ob_start();
        ?>
        <div class="lmb-user-ads">
            <p><strong><?php echo esc_html__('Votre solde de points: ', 'lmb-core'); ?></strong><?php echo esc_html($balance); ?></p>
            <p><strong><?php echo esc_html__('Coût par annonce publiée: ', 'lmb-core'); ?></strong><?php echo esc_html($cost); ?></p>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('ID', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Titre', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Type', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Statut', 'lmb-core'); ?></th>
                        <th><?php esc_html_e('Action', 'lmb-core'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($q->have_posts()): while ($q->have_posts()): $q->the_post();
                        $pid = get_the_ID();
                        $status = get_post_status($pid);
                        $ad_type = get_post_meta($pid, 'ad_type', true);
                        $ad_status = get_post_meta($pid, 'ad_status', true) ?: $status;
                        ?>
                        <tr>
                            <td><?php echo (int)$pid; ?></td>
                            <td><?php echo esc_html(get_the_title()); ?></td>
                            <td><?php echo esc_html($ad_type); ?></td>
                            <td><?php echo esc_html($ad_status); ?></td>
                            <td>
                                <?php if ($status !== 'publish'): ?>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
                                        <?php wp_nonce_field('lmb_user_publish_'.$pid); ?>
                                        <input type="hidden" name="action" value="lmb_user_publish_ad" />
                                        <input type="hidden" name="post_id" value="<?php echo (int)$pid; ?>" />
                                        <button type="submit"><?php esc_html_e('Publier (déduire points)', 'lmb-core'); ?></button>
                                    </form>
                                <?php else: ?>
                                    <em><?php esc_html_e('Déjà publié', 'lmb-core'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; wp_reset_postdata(); else: ?>
                        <tr><td colspan="5"><?php esc_html_e('Aucune annonce.', 'lmb-core'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function handle_publish() {
        if (!is_user_logged_in()) wp_die('Not allowed');
        $user = wp_get_current_user();
        $user_id = $user->ID;

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        if (!$post_id || get_post_type($post_id) !== 'lmb_legal_ad') wp_die('Invalid');

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'lmb_user_publish_'.$post_id)) wp_die('Bad nonce');

        $post = get_post($post_id);
        if ((int)$post->post_author !== $user_id && !user_can($user, 'edit_others_posts')) wp_die('No permission');

        // Staff bypass
        $staff_roles = array_map('trim', explode(',', (string) get_option('lmb_staff_roles', 'administrator,editor')));
        $is_staff = (bool) array_intersect($staff_roles, (array) $user->roles);

        if (!$is_staff) {
            $cost = (int) get_option('lmb_points_per_ad', 1);
            $balance = LMB_Points::get($user_id);
            if ($balance < $cost) {
                wp_die(__('Solde insuffisant. Veuillez recharger vos points.', 'lmb-core'));
            }
            LMB_Points::deduct($user_id, $cost, 'Publish ad #'.$post_id);
        }

        // Publish the ad
        update_post_meta($post_id, 'ad_status', 'published');
        wp_update_post(['ID' => $post_id, 'post_status' => 'publish']);

        // Notify
        LMB_Notification_Manager::send_email($user->user_email, __('Annonce publiée', 'lmb-core'), sprintf(__('Votre annonce #%d a été publiée.', 'lmb-core'), $post_id));

        // Redirect back
        $redirect = wp_get_referer() ?: home_url('/dashboard');
        wp_safe_redirect($redirect);
        exit;
    }
}
LMB_User_Dashboard::init();
