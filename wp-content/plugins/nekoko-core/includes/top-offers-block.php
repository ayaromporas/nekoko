<?php
/** Reusable top offer categories block. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_top_offers_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name'            => 'nekoko-top-offers',
		'title'           => 'Top ponuda',
		'description'     => 'Šest istaknutih kategorija iz Ponude sa ikonicama.',
		'category'        => 'design',
		'icon'            => 'grid-view',
		'keywords'        => array( 'ponuda', 'kategorije', 'ikonice', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/top-offers/render.php',
		'mode'            => 'edit',
		'supports'        => array( 'align' => false, 'anchor' => false, 'jsx' => true ),
		'enqueue_assets'  => 'nekoko_core_enqueue_top_offers_block_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_top_offers_block', 46 );

function nekoko_core_register_top_offers_block_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_top_offers_block', 'title' => 'Top ponuda',
		'fields' => array(
			array( 'key' => 'field_nekoko_top_offers_title', 'label' => 'Naslov', 'name' => 'top_offers_title', 'type' => 'text', 'default_value' => 'Top kategorije u ponudi' ),
			array( 'key' => 'field_nekoko_top_offers_text', 'label' => 'Tekst', 'name' => 'top_offers_text', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Izaberite jednu od najtraženijih kategorija i pogledajte dostupne ponude.' ),
			array( 'key' => 'field_nekoko_top_offers_items', 'label' => 'Kategorije sa ikonicama', 'instructions' => 'Opciono. Ako ne dodate stavke, prikazuje se šest podrazumevanih kategorija Ponude.', 'name' => 'top_offers_items', 'type' => 'repeater', 'layout' => 'table', 'max' => 6, 'button_label' => 'Dodajte kategoriju', 'sub_fields' => array(
				array( 'key' => 'field_nekoko_top_offers_item_category', 'label' => 'Kategorija', 'name' => 'category', 'type' => 'taxonomy', 'taxonomy' => array( 'kategorija_oglasa' ), 'field_type' => 'select', 'return_format' => 'id', 'allow_null' => 0, 'required' => 1 ),
				array( 'key' => 'field_nekoko_top_offers_item_icon', 'label' => 'Ikonica', 'name' => 'icon', 'type' => 'select', 'choices' => array( 'animal' => 'Životinja', 'craft' => 'Izrada', 'home' => 'Kuća i bašta', 'education' => 'Podučavanje', 'event' => 'Događaji', 'repair' => 'Popravke' ), 'default_value' => 'craft', 'return_format' => 'value', 'required' => 1 ),
			) ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-top-offers' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_top_offers_block_fields', 47 );

function nekoko_core_enqueue_top_offers_block_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	$plugin_url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-top-offers-block', $plugin_url . 'assets/top-offers-block.css', array(), filemtime( $plugin_dir . '/assets/top-offers-block.css' ) );
}

function nekoko_core_top_offer_items( $custom_items = array() ) {
	$items = array();
	foreach ( (array) $custom_items as $item ) {
		$term = get_term( absint( $item['category'] ?? 0 ), 'kategorija_oglasa' );
		$root = $term && ! is_wp_error( $term ) ? nekoko_core_category_root( $term->term_id ) : false;
		if ( $term && ! is_wp_error( $term ) && $root && 'ponuda' === $root->slug ) { $items[] = array( 'term' => $term, 'icon' => sanitize_key( $item['icon'] ?? 'craft' ) ); }
	}
	if ( $items ) { return array_slice( $items, 0, 6 ); }

	$defaults = array(
		'ponuda-cuvanje-zivotinja' => 'animal', 'ponuda-izrada-po-meri' => 'craft', 'ponuda-kuca-i-basta' => 'home', 'ponuda-poducavanje' => 'education', 'ponuda-pomoc-i-dogadjaji' => 'event', 'ponuda-kuca-i-basta-popravke-po-kuci' => 'repair',
	);
	foreach ( $defaults as $slug => $icon ) { $term = get_term_by( 'slug', $slug, 'kategorija_oglasa' ); if ( $term ) { $items[] = array( 'term' => $term, 'icon' => $icon ); } }
	return $items;
}

function nekoko_core_top_offer_icon( $icon ) {
	$common = 'fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"';
	$icons = array(
		'animal' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="M16 17c-2-7-7-11-7-3 0 5 2 9 4 11-2 3-2 8 1 12 5 7 16 7 21 0 3-4 3-9 1-12 2-2 4-6 4-11 0-8-5-4-7 3"/><path d="M18 30h.01M30 30h.01M21 36c2 2 4 2 6 0M24 31v2"/></svg>',
		'craft' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="m27 8 13 13-7 7-13-13 7-7Z"/><path d="m16 19-9 9 13 13 9-9M11 36l-4 4M24 10l14 14"/></svg>',
		'home' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="m6 23 18-15 18 15"/><path d="M10 21v20h28V21M19 41V29h10v12M15 25h.01M33 25h.01"/></svg>',
		'education' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="M7 12c6-3 11-2 17 2v25c-6-4-11-5-17-2V12ZM41 12c-6-3-11-2-17 2v25c6-4 11-5 17-2V12Z"/><path d="M14 20h5M29 20h5M14 27h5M29 27h5"/></svg>',
		'event' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="M8 19h18l9-7v24l-9-7H8v-10Z"/><path d="M13 29v8M20 29v8M39 18c3 3 3 9 0 12"/></svg>',
		'repair' => '<svg viewBox="0 0 48 48" aria-hidden="true" ' . $common . '><path d="M29 8a10 10 0 0 0-11 13L7 32l9 9 11-11A10 10 0 0 0 40 19l-7 7-5-5 7-7c-2-3-4-5-6-6Z"/></svg>',
	);
	return $icons[ $icon ] ?? $icons['craft'];
}
