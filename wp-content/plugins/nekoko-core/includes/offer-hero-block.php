<?php
/** Reusable Offer hero block. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_offer_hero_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name' => 'nekoko-offer-hero', 'title' => 'Hero ponuda', 'description' => 'Uvodna sekcija stranice Ponuda sa pretragom kategorija.',
		'category' => 'design', 'icon' => 'cover-image', 'keywords' => array( 'ponuda', 'hero', 'pretraga', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/offer-hero/render.php', 'mode' => 'edit',
		'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_offer_hero_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_hero_block', 43 );

function nekoko_core_register_offer_hero_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_offer_hero', 'title' => 'Hero ponuda',
		'fields' => array(
			array( 'key' => 'field_nekoko_offer_hero_label', 'label' => 'Tekst oznake', 'name' => 'offer_hero_label', 'type' => 'text', 'default_value' => 'Kategorije ponude' ),
			array( 'key' => 'field_nekoko_offer_hero_title', 'label' => 'Naslov', 'name' => 'offer_hero_title', 'type' => 'text', 'default_value' => 'Ponuda' ),
			array( 'key' => 'field_nekoko_offer_hero_text', 'label' => 'Tekst', 'name' => 'offer_hero_text', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Pregledajte glavne kategorije ponude i pronađite usluge, pomoć i specifične poslove koji odgovaraju onome što vam je potrebno.' ),
			array( 'key' => 'field_nekoko_offer_hero_placeholder', 'label' => 'Placeholder pretrage', 'name' => 'offer_hero_placeholder', 'type' => 'text', 'default_value' => 'Šta tražite?' ),
			array( 'key' => 'field_nekoko_offer_hero_category_label', 'label' => 'Početna oznaka kategorije', 'name' => 'offer_hero_category_label', 'type' => 'text', 'default_value' => 'Kategorija' ),
			array( 'key' => 'field_nekoko_offer_hero_submit', 'label' => 'Tekst dugmeta', 'name' => 'offer_hero_submit', 'type' => 'text', 'default_value' => 'Pretraži' ),
			array( 'key' => 'field_nekoko_offer_hero_search_url', 'label' => 'Stranica rezultata pretrage', 'name' => 'offer_hero_search_url', 'type' => 'url', 'default_value' => home_url( '/napredna-pretraga/' ) ),
			array( 'key' => 'field_nekoko_offer_hero_image', 'label' => 'Slika kolaža', 'name' => 'offer_hero_image', 'type' => 'image', 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp' ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-offer-hero' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_hero_fields', 44 );

function nekoko_core_enqueue_offer_hero_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	$plugin_url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-offer-hero', $plugin_url . 'assets/offer-hero-block.css', array(), filemtime( $plugin_dir . '/assets/offer-hero-block.css' ) );
}

function nekoko_core_offer_hero_categories() {
	$roots = nekoko_core_category_roots();
	$root = $roots['ponuda'] ?? null;
	if ( ! $root ) { return array(); }
	$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) { return array(); }
	$by_parent = array();
	foreach ( $terms as $term ) { $by_parent[ (int) $term->parent ][] = $term; }
	$output = array();
	$collect = function( $parent, $depth ) use ( &$collect, &$output, $by_parent ) {
		foreach ( $by_parent[ (int) $parent ] ?? array() as $term ) { $output[] = array( 'term' => $term, 'depth' => $depth ); $collect( $term->term_id, $depth + 1 ); }
	};
	$collect( $root->term_id, 0 );
	return $output;
}
