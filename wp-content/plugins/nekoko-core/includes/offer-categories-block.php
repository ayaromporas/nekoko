<?php
/** Reusable complete Offer category grid. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_offer_categories_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name' => 'nekoko-offer-categories', 'title' => 'Sve kategorije u ponudi', 'description' => 'Mreža glavnih kategorija Ponude i njihovih podkategorija.',
		'category' => 'design', 'icon' => 'category', 'keywords' => array( 'ponuda', 'kategorije', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/offer-categories/render.php', 'mode' => 'edit',
		'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_offer_categories_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_categories_block', 45 );

function nekoko_core_register_offer_categories_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_offer_categories', 'title' => 'Sve kategorije u ponudi',
		'fields' => array(
			array( 'key' => 'field_nekoko_offer_categories_title', 'label' => 'Naslov', 'name' => 'offer_categories_title', 'type' => 'text', 'default_value' => 'Sve kategorije u ponudi' ),
			array( 'key' => 'field_nekoko_offer_categories_text', 'label' => 'Kratki tekst', 'name' => 'offer_categories_text', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Istražite glavne kategorije u ponudi i odaberite oblast koja vas zanima.' ),
			array( 'key' => 'field_nekoko_offer_categories_background', 'label' => 'Boja pozadine', 'name' => 'offer_categories_background', 'type' => 'color_picker', 'default_value' => '#f5f2eb', 'return_format' => 'string' ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-offer-categories' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_categories_fields', 46 );

function nekoko_core_enqueue_offer_categories_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	$plugin_url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-offer-categories', $plugin_url . 'assets/offer-categories-block.css', array(), filemtime( $plugin_dir . '/assets/offer-categories-block.css' ) );
}

function nekoko_core_offer_category_cards() {
	$roots = nekoko_core_category_roots();
	$root = $roots['ponuda'] ?? null;
	if ( ! $root ) { return array(); }
	$categories = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false, 'parent' => $root->term_id, 'orderby' => 'name', 'order' => 'ASC' ) );
	if ( is_wp_error( $categories ) ) { return array(); }
	$cards = array();
	foreach ( $categories as $category ) {
		$children = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false, 'parent' => $category->term_id, 'orderby' => 'name', 'order' => 'ASC' ) );
		$icon = function_exists( 'nekoko_core_top_request_icon_for_term' ) ? nekoko_core_top_request_icon_for_term( $category ) : 'craft';
		$cards[] = array( 'term' => $category, 'children' => is_wp_error( $children ) ? array() : $children, 'icon' => $icon );
	}
	return $cards;
}
