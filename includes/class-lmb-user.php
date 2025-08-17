<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class LMB_User {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu_pages' ) );
    }

    public static function create_custom_roles() {
        // Add the 'Client' role.
        add_role(
            'client',
            'Client',
            array(
                'read'                 => true,
                'edit_posts'           => true, // Can edit their own posts (ads).
                'delete_posts'         => true,
                'upload_files'         => true, // Can upload payment proof.
            )
        );

        // Add the 'Employee' role.
        add_role(
            'employee',
            'Employee',
            array(
                'read'                 => true,
                'edit_posts'           => true,
                'delete_posts'         => true,
                'publish_posts'        => true, // Can publish legal ads.
                'edit_others_posts'    => true, // Can edit all legal ads.
                'delete_others_posts'  => true,
                'manage_options'       => true, // To access admin dashboard.
                'upload_files'         => true,
                'unfiltered_html'      => true,
                'lmb_bypass_payment'   => true, // Custom capability to bypass payment checks.
            )
        );
    }

    /**
     * Add admin menu for managing user points.
     */
    public function add_admin_menu_pages() {
        add_submenu_page(
            'users.php', // Parent slug
            __( 'Manage User Points', 'lmb-core' ), // Page title
            __( 'Manage Points', 'lmb-core' ), // Menu title
            'manage_options', // Capability
            'lmb_manage_points', // Menu slug
            array( $this, 'render_points_page' ) // Callback function
        );
    }

    /**
     * Render the HTML for the points management page.
     */
    public function render_points_page() {
        // Check if the current user has the required capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Handle form submission.
        if ( isset( $_POST['lmb_manage_points_nonce'] ) && wp_verify_nonce( $_POST['lmb_manage_points_nonce'], 'lmb_add_points' ) ) {
            $user_id = absint( $_POST['user_id'] );
            $points_to_add = absint( $_POST['points_to_add'] );

            if ( $user_id && $points_to_add ) {
                $current_points = (int) get_user_meta( $user_id, 'lmb_user_points', true );
                $new_points = $current_points + $points_to_add;
                update_user_meta( $user_id, 'lmb_user_points', $new_points );

                // Send a notification to the user.
                require_once LMB_CORE_PATH . 'includes/class-lmb-notification-manager.php';
                LMB_Notification_Manager::notify_user_balance_update( $user_id, $new_points );
                
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $points_to_add ) . ' points successfully added to user ID ' . esc_html( $user_id ) . '. New balance is ' . esc_html( $new_points ) . '.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Please select a valid user and enter a valid number of points.</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="" method="post">
                <?php wp_nonce_field( 'lmb_add_points', 'lmb_manage_points_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="user_id">Select Client</label></th>
                            <td>
                                <select name="user_id" id="user_id" required>
                                    <option value="">-- Select a User --</option>
                                    <?php
                                    $clients = get_users( array( 'role' => 'client' ) );
                                    foreach ( $clients as $client ) {
                                        echo '<option value="' . esc_attr( $client->ID ) . '">' . esc_html( $client->display_name ) . ' (' . esc_html( $client->user_email ) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="points_to_add">Points to Add</label></th>
                            <td><input name="points_to_add" type="number" id="points_to_add" value="" class="regular-text" required min="1"></td>
                        </tr>
                    </tbody>
                </table>
                <?php submit_button( 'Add Points' ); ?>
            </form>
        </div>
        <?php
    }
}