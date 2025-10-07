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
			// Group 1: Legal Ad Details (Your existing code)
			acf_add_local_field_group(
				array(
					'key'                   => 'group_legal_ad_data',
					'title'                 => 'Legal Ad Details',
					'fields'                => array(
						array(
							'key'      => 'field_lmb_ad_type',
							'label'    => 'Ad Type',
							'name'     => 'ad_type',
							'type'     => 'text',
							'required' => 1,
						),
						array(
							'key'      => 'field_lmb_full_text',
							'label'    => 'Full Ad Text',
							'name'     => 'full_text',
							'type'     => 'textarea',
							'required' => 1,
						),
						array(
							'key'           => 'field_lmb_status',
							'label'         => 'Publication Status',
							'name'          => 'lmb_status',
							'type'          => 'select',
							'choices'       => array(
								'draft'          => 'Draft',
								'pending_review' => 'Pending Review',
								'published'      => 'Published',
								'denied'         => 'Denied',
							),
							'default_value' => 'draft',
							'required'      => 1,
						),
						array(
							'key'      => 'field_lmb_client_id',
							'label'    => 'Client ID',
							'name'     => 'lmb_client_id',
							'type'     => 'number',
							'required' => 1,
						),
						array(
							'key'          => 'field_lmb_ad_pdf_url',
							'label'        => 'Ad PDF URL',
							'name'         => 'ad_pdf_url',
							'type'         => 'url',
							'instructions' => 'URL of the generated ad PDF.',
							'required'     => 0,
						),
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'lmb_legal_ad',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'normal',
					'style'                 => 'default',
				)
			);

			// Group 2: Newspaper Details (Your existing code)
			acf_add_local_field_group(
				array(
					'key'      => 'group_newspaper_data',
					'title'    => 'Newspaper Details',
					'fields'   => array(
						array(
							'key'           => 'field_lmb_newspaper_pdf',
							'label'         => 'Newspaper PDF',
							'name'          => 'newspaper_pdf',
							'type'          => 'file',
							'required'      => 1,
							'return_format' => 'id',
							'mime_types'    => 'pdf',
						),
					),
					'location' => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'lmb_newspaper',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'normal',
					'style'                 => 'default',
				)
			);

			// Group 3: Package Details (Original code + our new field)
			acf_add_local_field_group(
				array(
					'key'                   => 'group_60c7c3d5a2b8b',
					'title'                 => 'Package',
					'fields'                => array(
						array(
							'key'   => 'field_60c7c3e0a2b8c',
							'label' => 'Price',
							'name'  => 'price',
							'type'  => 'number',
							'required' => 1,
						),
						array(
							'key'   => 'field_60c7c3f3a2b8d',
							'label' => 'Points',
							'name'  => 'points',
							'type'  => 'number',
							'required' => 1,
						),
						// --- START: New Field Added ---
						array(
							'key'           => 'field_lmb_client_visible',
							'label'         => 'Client Visible',
							'name'          => 'client_visible',
							'type'          => 'true_false',
							'instructions'  => 'Si coche, ce package sera visible par les clients',
							'message'       => 'Visible pour Clients',
							'default_value' => 1,
							'ui'            => 1,
							'ui_on_text'    => 'Visible',
							'ui_off_text'   => 'Hidden',
						),
						// --- END: New Field Added ---
					),
					'location'              => array(
						array(
							array(
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'lmb_package',
							),
						),
					),
					'menu_order'            => 0,
					'position'              => 'normal',
					'style'                 => 'default',
					'label_placement'       => 'top',
					'instruction_placement' => 'label',
					'active'                => true,
				)
			);
		}
	}
}
LMB_ACF::init();