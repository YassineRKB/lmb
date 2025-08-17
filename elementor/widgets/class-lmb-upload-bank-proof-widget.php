<?php
use Elementor\Widget_Base;

if (!defined('ABSPATH')) exit;

class LMB_Upload_Bank_Proof_Widget extends Widget_Base {
    public function get_name() { return 'lmb_upload_bank_proof'; }
    public function get_title(){ return __('LMB Upload Bank Proof','lmb-core'); }
    public function get_icon() { return 'eicon-upload'; }
    public function get_categories(){ return ['general']; }

    protected function render() {
        if (!is_user_logged_in()) { echo '<p>'.esc_html__('Login required.','lmb-core').'</p>'; return; }

        $packages = get_posts(['post_type'=>'lmb_package','post_status'=>'publish','numberposts'=>-1]);
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" enctype="multipart/form-data" class="lmb-form">';
        echo '<input type="hidden" name="action" value="lmb_upload_bank_proof">';
        wp_nonce_field('lmb_upload_bank_proof');
        echo '<p><label>'.esc_html__('Package','lmb-core').'</label> <select name="package_id">';
        foreach ($packages as $p) echo '<option value="'.$p->ID.'">'.esc_html($p->post_title).'</option>';
        echo '</select></p>';
        echo '<p><label>'.esc_html__('Proof file','lmb-core').'</label> <input type="file" name="proof_file" required></p>';
        echo '<p><label>'.esc_html__('Notes','lmb-core').'</label> <input type="text" name="notes"></p>';
        echo '<p><button class="button button-primary" type="submit">'.esc_html__('Upload','lmb-core').'</button></p>';
        echo '</form>';
    }
}
