<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class LMB_Ad_Manager {

    protected static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        // Add a new column to the Legal Ads list in the admin.
        add_filter( 'manage_lmb_legal_ad_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_lmb_legal_ad_posts_custom_column', array( $this, 'display_custom_columns' ), 10, 2 );

        // Admin-side hooks for PDF upload.
        add_action( 'admin_menu', array( $this, 'add_admin_menu_pages' ) );
        add_action( 'admin_post_lmb_upload_newspaper', array( $this, 'handle_newspaper_upload' ) );
    }

    // Add 'Status' and 'Client' columns to the admin list.
    public function add_custom_columns( $columns ) {
        $new_columns = array();
        $new_columns['ad_status'] = __( 'Status', 'lmb-core' );
        $new_columns['ad_client'] = __( 'Client', 'lmb-core' );
        return array_merge( $columns, $new_columns );
    }

    // Display data in the new custom columns.
    public function display_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'ad_status':
                $status = get_field( 'lmb_status', $post_id );
                echo esc_html( $status );
                break;
            case 'ad_client':
                $user_id = get_field( 'lmb_client_id', $post_id );
                if ( $user_id ) {
                    $user_info = get_userdata( $user_id );
                    echo '<a href="' . esc_url( get_edit_user_link( $user_id ) ) . '">' . esc_html( $user_info->display_name ) . '</a>';
                }
                break;
        }
    }

    // Add 'Newspaper Upload' to the admin menu.
    public function add_admin_menu_pages() {
        add_submenu_page(
            'edit.php?post_type=lmb_legal_ad',
            'Upload Newspaper',
            'Upload Newspaper',
            'edit_posts',
            'lmb-newspaper-upload',
            array( $this, 'render_newspaper_upload_page' )
        );
    }

    // Render the HTML for the newspaper upload page.
    public function render_newspaper_upload_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="lmb_upload_newspaper">
                <?php wp_nonce_field( 'lmb_upload_newspaper_nonce', 'lmb_upload_newspaper_nonce_field' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="newspaper_pdf">Newspaper PDF</label></th>
                        <td><input type="file" name="newspaper_pdf" id="newspaper_pdf" required></td>
                    </tr>
                </table>
                <?php submit_button( 'Upload PDF' ); ?>
            </form>
        </div>
        <?php
    }

    // Handle the form submission for newspaper upload.
    public function handle_newspaper_upload() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( 'You do not have permission to perform this action.' );
        }

        if ( ! isset( $_POST['lmb_upload_newspaper_nonce_field'] ) || ! wp_verify_nonce( $_POST['lmb_upload_newspaper_nonce_field'], 'lmb_upload_newspaper_nonce' ) ) {
            wp_die( 'Nonce verification failed.' );
        }
        
        if ( empty( $_FILES['newspaper_pdf'] ) ) {
            wp_die( 'No file was uploaded.' );
        }

        $upload_dir = wp_upload_dir();
        $file = $_FILES['newspaper_pdf'];

        $file_name = sanitize_file_name( $file['name'] );
        $file_path = $upload_dir['path'] . '/' . $file_name;

        if ( move_uploaded_file( $file['tmp_name'], $file_path ) ) {
            $attachment_id = wp_insert_attachment( array(
                'guid'           => $upload_dir['url'] . '/' . $file_name,
                'post_mime_type' => $file['type'],
                'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
                'post_content'   => '',
                'post_status'    => 'inherit',
            ), $file_path );

            if ( ! is_wp_error( $attachment_id ) ) {
                // Create a new post to represent the newspaper.
                $post_id = wp_insert_post( array(
                    'post_title'  => 'Newspaper: ' . $file_name,
                    'post_status' => 'publish',
                    'post_type'   => 'lmb_newspaper', // You will need to register this CPT in lmb-core.php.
                ) );

                if ( ! is_wp_error( $post_id ) ) {
                    update_field( 'newspaper_pdf', $attachment_id, $post_id );
                    wp_redirect( admin_url( 'edit.php?post_type=lmb_legal_ad&page=lmb-newspaper-upload&upload=success' ) );
                    exit;
                }
            }
        }
        wp_die( 'An error occurred during file upload.' );
    }

}
LMB_Ad_Manager::instance();