<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMB_Access_Control {

    public function __construct() {
        add_action( 'init', array( $this, 'register_roles' ) );
    }

    /**
     * Register custom roles or capabilities for admins and users
     */
    public function register_roles() {
        // Client role
        add_role(
            'lmb_client',
            __( 'Client', 'lmb-core' ),
            array(
                'read' => true,
                'upload_files' => true,
            )
        );

        // Ensure admins have full capabilities
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( 'manage_lmb_ads' );
            $admin->add_cap( 'manage_lmb_newspapers' );
            $admin->add_cap( 'manage_lmb_payments' );
        }
    }

    /**
     * Check if current user is admin
     */
    public static function is_admin() {
        return current_user_can( 'administrator' );
    }

    /**
     * Check if current user is client
     */
    public static function is_client() {
        return current_user_can( 'lmb_client' );
    }
}
