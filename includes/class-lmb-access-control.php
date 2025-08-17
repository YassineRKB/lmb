<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMB_Access_Control {

    /**
     * Bootstrap
     */
    public static function init() {
        $instance = new self();

        // Protect front-end routes
        add_action( 'template_redirect', [ $instance, 'protect_routes' ] );

        // Register roles & caps
        add_action( 'init', [ $instance, 'register_roles' ] );
    }

    /**
     * Register custom roles or capabilities
     */
    public function register_roles() {
        // Client role
        add_role(
            'lmb_client',
            __( 'Client', 'lmb-core' ),
            [
                'read'         => true,
                'upload_files' => true,
            ]
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
     * Restrict access to /dashboard and /administration
     */
    public function protect_routes() {
        $protected_slugs = explode( "\n", get_option( 'lmb_protected_slugs', "/dashboard\n/administration" ) );
        $current_path    = trim( $_SERVER['REQUEST_URI'], '/' );

        foreach ( $protected_slugs as $slug ) {
            $slug = trim( $slug, '/' );
            if ( stripos( $current_path, $slug ) === 0 ) {
                // /administration → only admins
                if ( $slug === 'administration' && ! current_user_can( 'administrator' ) ) {
                    wp_redirect( home_url() );
                    exit;
                }

                // /dashboard → only logged-in clients
                if ( $slug === 'dashboard' && ! is_user_logged_in() ) {
                    wp_redirect( wp_login_url() );
                    exit;
                }
            }
        }
    }

    /**
     * Helpers
     */
    public static function is_admin() {
        return current_user_can( 'administrator' );
    }

    public static function is_client() {
        return current_user_can( 'lmb_client' );
    }
}
