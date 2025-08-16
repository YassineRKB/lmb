<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMB_ACF {
    public static function init() {
        add_action( 'acf/init', array( self::class, 'register_field_groups' ) );
    }

    public static function register_field_groups() {
        if ( function_exists( 'acf_add_local_field_group' ) ) {
            // Legal Ad Details
            acf_add_local_field_group( array(
                'key' => 'group_legal_ad_data',
                'title' => 'Legal Ad Details',
                'fields' => array(
                    array(
                        'key' => 'field_lmb_ad_type',
                        'label' => 'Ad Type',
                        'name' => 'ad_type',
                        'type' => 'text',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_lmb_full_text',
                        'label' => 'Full Ad Text',
                        'name' => 'full_text',
                        'type' => 'textarea',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_lmb_status',
                        'label' => 'Publication Status',
                        'name' => 'lmb_status',
                        'type' => 'select',
                        'choices' => array(
                            'draft' => 'Draft',
                            'pending_review' => 'Pending Review',
                            'published' => 'Published',
                            'denied' => 'Denied',
                        ),
                        'default_value' => 'draft',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_lmb_client_id',
                        'label' => 'Client ID',
                        'name' => 'lmb_client_id',
                        'type' => 'number',
                        'required' => 1,
                    ),
                    array(
                        'key' => 'field_lmb_ad_pdf_url',
                        'label' => 'Ad PDF URL',
                        'name' => 'ad_pdf_url',
                        'type' => 'url',
                        'instructions' => 'URL of the generated ad PDF.',
                        'required' => 0,
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'lmb_legal_ad',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
            ));

            // Newspaper Details
            acf_add_local_field_group( array(
                'key' => 'group_newspaper_data',
                'title' => 'Newspaper Details',
                'fields' => array(
                    array(
                        'key' => 'field_lmb_newspaper_pdf',
                        'label' => 'Newspaper PDF',
                        'name' => 'newspaper_pdf',
                        'type' => 'file',
                        'required' => 1,
                        'return_format' => 'id',
                        'mime_types' => 'pdf',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'lmb_newspaper',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
            ));
        }
    }
}
LMB_ACF::init();