<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMB_User {
    public function __construct() {
        // Actions can be added here if needed in the future
    }

    public static function create_custom_roles() {
        // Client Role: Can submit ads and manage their own content.
        add_role(
            'client',
            'Client',
            [
                'read'         => true,
                'edit_posts'   => true,
                'delete_posts' => true,
                'upload_files' => true,
            ]
        );

        // Employee Role: Can manage ads and payments, but not plugin settings.
        add_role(
            'employee',
            'Employee',
            [
                'read'                  => true,
                'edit_posts'            => true,
                'delete_posts'          => true,
                'publish_posts'         => true,
                'edit_others_posts'     => true,
                'delete_others_posts'   => true,
                'upload_files'          => true,
                'manage_lmb_payments'   => true, // Custom capability
            ]
        );
        
        // Grant administrators all custom capabilities
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('manage_lmb_payments');
        }
    }
}