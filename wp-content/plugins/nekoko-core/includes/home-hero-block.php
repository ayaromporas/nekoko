<?php
/** Home-page hero block registration and its editable ACF fields. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_home_hero_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }

	acf_register_block_type( array(
		'name'            => 'nekoko-home-hero',
		'title'           => 'Hero početna',
		'description'     => 'Uvodna sekcija početne stranice sa naslovom, pozivima na akciju i slikom.',
		'category'        => 'design',
		'icon'            => 'cover-image',
		'keywords'        => array( 'hero', 'početna', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/home-hero/render.php',
		// Display the ACF controls immediately instead of hiding them behind the
		// preview/edit toggle in the block toolbar.
		'mode'            => 'edit',
		'supports'        => array( 'align' => false, 'anchor' => false, 'jsx' => true ),
	) );
}
add_action( 'acf/init', 'nekoko_core_register_home_hero_block', 40 );

function nekoko_core_register_home_hero_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }

	acf_add_local_field_group( array(
		'key'      => 'group_nekoko_home_hero',
		'title'    => 'Hero početna',
		'fields'   => array(
			array(
				'key'           => 'field_nekoko_home_hero_title',
				'label'         => 'Naslov',
				'name'          => 'hero_title',
				'type'          => 'text',
				'default_value' => 'Neko traži. Neko zna. NekoKO ih povezuje.',
				'required'      => 1,
			),
			array(
				'key'           => 'field_nekoko_home_hero_text',
				'label'         => 'Paragraf',
				'name'          => 'hero_text',
				'type'          => 'textarea',
				'rows'          => 4,
				'default_value' => 'NekoKo povezuje ljude kojima je potrebna pomoć sa onima koji imaju znanje i veštine da je pruže. Pronađite pravu osobu za posao ili ponudite ono što umete i zaradite dodatno — brzo, jednostavno, na jednom mestu.',
				'required'      => 1,
			),
			array(
				'key'           => 'field_nekoko_home_hero_primary_label',
				'label'         => 'Prvo dugme — labela',
				'name'          => 'hero_primary_label',
				'type'          => 'text',
				'default_value' => 'Pretražite oglase',
				'required'      => 1,
			),
			array(
				'key'           => 'field_nekoko_home_hero_primary_link',
				'label'         => 'Prvo dugme — link',
				'name'          => 'hero_primary_link',
				'type'          => 'url',
				'placeholder'   => 'https://',
				'default_value' => home_url( '/napredna-pretraga/' ),
				'required'      => 1,
			),
			array(
				'key'           => 'field_nekoko_home_hero_secondary_label',
				'label'         => 'Drugo dugme — labela',
				'name'          => 'hero_secondary_label',
				'type'          => 'text',
				'default_value' => 'Kako funkcioniše',
				'required'      => 1,
			),
			array(
				'key'           => 'field_nekoko_home_hero_secondary_link',
				'label'         => 'Drugo dugme — link',
				'name'          => 'hero_secondary_link',
				'type'          => 'url',
				'placeholder'   => 'https://',
				'default_value' => home_url( '/#kako-funkcionise' ),
				'required'      => 1,
			),
			array(
				'key'           => 'field_nekoko_home_hero_image',
				'label'         => 'Slika kolaža',
				'name'          => 'hero_image',
				'type'          => 'image',
				'return_format' => 'id',
				'library'       => 'all',
				'mime_types'    => 'jpg,jpeg,png,webp',
			),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-home-hero' ) ) ),
		'active'   => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_home_hero_fields', 41 );
