<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class LMB_User {
    public function __construct() {
        // Create custom roles on plugin activation
        add_action('user_register', [$this, 'set_default_status'], 10, 1);
    }

    // Get display name based on client type
    public static function get_client_display_name($user) {
        if (!$user instanceof WP_User) {
            $user = get_userdata($user);
        }

        if (!$user) {
            return 'Utilisateur Inconnu';
        }

        $client_type = get_user_meta($user->ID, 'lmb_client_type', true);
        $display_name = '';

        if ($client_type === 'professional') {
            $display_name = get_user_meta($user->ID, 'company_name', true);
        }

        // Fallback for regular clients or if professional name is empty
        if (empty($display_name)) {
            $display_name = $user->display_name;
        }
        
        // Final fallback to username if display_name is still empty
        if (empty(trim($display_name))) {
            $display_name = $user->user_login;
        }

        return esc_html($display_name);
    }
    
    // Set default user status to 'inactive' upon registration
    public function set_default_status($user_id) {
        update_user_meta($user_id, 'lmb_user_status', 'inactive');
    }

    // Create custom roles: Client and Employee
    public static function create_custom_roles() {
        $client_caps = [
            'read' => true,
            'upload_files' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'edit_published_posts' => false,
            'delete_published_posts' => false,
            'manage_categories' => false,
            'moderate_comments' => false,
        ];

        $employee_caps = array_merge($client_caps, [
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'edit_published_posts' => true,
            'delete_published_posts' => true,
            'edit_others_posts' => true,
            'delete_others_posts' => true,
        ]);

        add_role('client', 'Client', $client_caps);
        add_role('employee', 'Employee', $employee_caps);
    }
}