<?php
/** Reusable top request categories block. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_outline_icon_choices() {
	return array( 'animal' => 'Životinja', 'craft' => 'Izrada', 'home' => 'Kuća i bašta', 'education' => 'Podučavanje', 'event' => 'Pomoć i događaji', 'repair' => 'Popravke' );
}

function nekoko_core_register_top_requests_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name' => 'nekoko-top-requests', 'title' => 'Top potražnja', 'description' => 'Istaknute kategorije Potražnje sa outline ikonicama.',
		'category' => 'design', 'icon' => 'grid-view', 'keywords' => array( 'potražnja', 'kategorije', 'ikonice', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/top-requests/render.php', 'mode' => 'edit',
		'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_top_offers_block_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_top_requests_block', 48 );

function nekoko_core_register_top_requests_block_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_top_requests_block', 'title' => 'Top potražnja',
		'fields' => array(
			array( 'key' => 'field_nekoko_top_requests_title', 'label' => 'Naslov', 'name' => 'top_requests_title', 'type' => 'text', 'default_value' => 'Top kategorije u potražnji' ),
			array( 'key' => 'field_nekoko_top_requests_text', 'label' => 'Tekst', 'name' => 'top_requests_text', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Izaberite kategoriju i pronađite ljude spremne da pomognu.' ),
			array( 'key' => 'field_nekoko_top_requests_items', 'label' => 'Kategorije sa ikonicama', 'instructions' => 'Opciono. Ako ne dodate stavke, prikazuje se do šest slučajnih kategorija Potražnje.', 'name' => 'top_requests_items', 'type' => 'repeater', 'layout' => 'table', 'max' => 6, 'button_label' => 'Dodajte kategoriju', 'sub_fields' => array(
				array( 'key' => 'field_nekoko_top_requests_item_category', 'label' => 'Kategorija', 'name' => 'category', 'type' => 'taxonomy', 'taxonomy' => array( 'kategorija_oglasa' ), 'field_type' => 'select', 'return_format' => 'id', 'allow_null' => 0, 'required' => 1 ),
				array( 'key' => 'field_nekoko_top_requests_item_icon', 'label' => 'Outline ikonica', 'name' => 'icon', 'type' => 'select', 'choices' => array( '' => 'Automatski izbor kategorije' ) + nekoko_core_outline_icon_choices(), 'return_format' => 'value' ),
			) ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-top-requests' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_top_requests_block_fields', 49 );

function nekoko_core_top_request_icon_for_term( $term ) {
	$saved = sanitize_key( get_term_meta( $term->term_id, 'nekoko_outline_icon', true ) );
	if ( $saved && array_key_exists( $saved, nekoko_core_outline_icon_choices() ) ) { return $saved; }
	$slug = sanitize_title( $term->slug );
	if ( false !== strpos( $slug, 'zivotinja' ) || false !== strpos( $slug, 'mack' ) || false !== strpos( $slug, 'psi' ) ) { return 'animal'; }
	if ( false !== strpos( $slug, 'poprav' ) ) { return 'repair'; }
	if ( false !== strpos( $slug, 'izrada' ) || false !== strpos( $slug, 'dekoracij' ) || false !== strpos( $slug, 'sivenj' ) || false !== strpos( $slug, 'vajanj' ) ) { return 'craft'; }
	if ( false !== strpos( $slug, 'kuca' ) || false !== strpos( $slug, 'basta' ) || false !== strpos( $slug, 'prostor' ) ) { return 'home'; }
	if ( false !== strpos( $slug, 'poducavanje' ) || false !== strpos( $slug, 'jezik' ) || false !== strpos( $slug, 'muzik' ) || false !== strpos( $slug, 'vestin' ) ) { return 'education'; }
	return 'event';
}

function nekoko_core_top_request_items( $custom_items = array() ) {
	$items = array();
	foreach ( (array) $custom_items as $item ) {
		$term = get_term( absint( $item['category'] ?? 0 ), 'kategorija_oglasa' );
		$root = $term && ! is_wp_error( $term ) ? nekoko_core_category_root( $term->term_id ) : false;
		if ( $term && ! is_wp_error( $term ) && $root && 'potraznja' === $root->slug ) { $items[] = array( 'term' => $term, 'icon' => sanitize_key( $item['icon'] ?? '' ) ?: nekoko_core_top_request_icon_for_term( $term ) ); }
	}
	if ( $items ) { return array_slice( $items, 0, 6 ); }

	$roots = nekoko_core_category_roots();
	$root = $roots['potraznja'] ?? null;
	if ( ! $root ) { return array(); }
	$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false, 'child_of' => $root->term_id ) );
	if ( is_wp_error( $terms ) ) { return array(); }
	shuffle( $terms );
	foreach ( array_slice( $terms, 0, 6 ) as $term ) { $items[] = array( 'term' => $term, 'icon' => nekoko_core_top_request_icon_for_term( $term ) ); }
	return $items;
}
