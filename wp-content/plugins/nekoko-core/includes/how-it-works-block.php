<?php
/** Reusable how-it-works block. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_how_it_works_icon_choices() {
	return array( 'search' => 'Pretražite', 'account' => 'Napravite nalog', 'post' => 'Postavite oglas', 'agreement' => 'Dogovorite saradnju' );
}

function nekoko_core_register_how_it_works_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name' => 'nekoko-how-it-works', 'title' => 'Kako funkcioniše', 'description' => 'Sekcija sa četiri koraka za korišćenje NekoKO platforme.',
		'category' => 'design', 'icon' => 'editor-ol', 'keywords' => array( 'kako funkcioniše', 'koraci', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/how-it-works/render.php', 'mode' => 'edit',
		'supports' => array( 'align' => false, 'anchor' => true, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_how_it_works_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_how_it_works_block', 42 );

function nekoko_core_register_how_it_works_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_how_it_works', 'title' => 'Kako funkcioniše',
		'fields' => array(
			array( 'key' => 'field_nekoko_how_title', 'label' => 'Naslov', 'name' => 'how_title', 'type' => 'text', 'default_value' => 'Kako funkcioniše' ),
			array( 'key' => 'field_nekoko_how_lead', 'label' => 'Istaknuti uvod', 'name' => 'how_lead', 'type' => 'text', 'default_value' => 'Pronađite ono što vam treba ili ponudite ono što znate i umete.' ),
			array( 'key' => 'field_nekoko_how_text', 'label' => 'Uvodni tekst', 'name' => 'how_text', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'NekoKO povezuje ljude koji nude i traže specifične usluge, pomoć i neobične poslove. Ceo proces može da stane u četiri jednostavna koraka.' ),
			array( 'key' => 'field_nekoko_how_background', 'label' => 'Boja pozadine', 'name' => 'how_background', 'type' => 'color_picker', 'default_value' => '#eaf4ff', 'return_format' => 'string' ),
			array( 'key' => 'field_nekoko_how_steps', 'label' => 'Koraci', 'name' => 'how_steps', 'type' => 'repeater', 'layout' => 'block', 'min' => 4, 'max' => 4, 'button_label' => 'Dodajte korak', 'sub_fields' => array(
				array( 'key' => 'field_nekoko_how_step_image', 'label' => 'Slika ikonice', 'instructions' => 'Izaberite, otpremite, zamenite ili uklonite ikonicu kroz WordPress Media Library.', 'name' => 'image', 'type' => 'image', 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'svg,png,jpg,jpeg,webp', 'preview_size' => 'thumbnail' ),
				array( 'key' => 'field_nekoko_how_step_title', 'label' => 'Naslov koraka', 'name' => 'title', 'type' => 'text', 'required' => 1 ),
				array( 'key' => 'field_nekoko_how_step_text', 'label' => 'Opis koraka', 'name' => 'text', 'type' => 'textarea', 'rows' => 3, 'required' => 1 ),
				array( 'key' => 'field_nekoko_how_step_link', 'label' => 'Link koraka', 'name' => 'link', 'type' => 'link', 'return_format' => 'array', 'required' => 1 ),
			) ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-how-it-works' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_how_it_works_fields', 43 );

function nekoko_core_enqueue_how_it_works_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	$plugin_url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-how-it-works', $plugin_url . 'assets/how-it-works-block.css', array(), filemtime( $plugin_dir . '/assets/how-it-works-block.css' ) );
}

function nekoko_core_how_it_works_icon( $icon ) {
	$common = 'fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"';
	$icons = array(
		'search' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><circle cx="21" cy="21" r="11"/><path d="m30 30 10 10M17 21h8M21 17v8"/></svg>',
		'account' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><circle cx="24" cy="16" r="7"/><path d="M10 40c1-8 6-12 14-12s13 4 14 12M36 12v10M31 17h10"/></svg>',
		'post' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="M10 7h20l8 8v26H10V7Z"/><path d="M30 7v10h10M16 27h16M24 20v14M17 27h14"/></svg>',
		'agreement' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="m8 25 8-8 8 8 8-8 8 8-8 8-8-8-8 8-8-8Z"/><path d="m8 33 8 8 8-8 8 8 8-8"/></svg>',
	);
	return $icons[ $icon ] ?? $icons['search'];
}
