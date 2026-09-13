<?php
/** Reusable featured-offers block. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_featured_offers_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }

	acf_register_block_type( array(
		'name'            => 'nekoko-featured-offers',
		'title'           => 'Ponuda – istaknuto',
		'description'     => 'Istaknuta Ponuda sekcija sa četiri oglasa.',
		'category'        => 'design',
		'icon'            => 'screenoptions',
		'keywords'        => array( 'ponuda', 'oglasi', 'istaknuto', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/featured-offers/render.php',
		'mode'            => 'edit',
		'supports'        => array( 'align' => false, 'anchor' => false, 'jsx' => true ),
		'enqueue_assets'  => 'nekoko_core_enqueue_featured_offers_block_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_featured_offers_block', 44 );

function nekoko_core_register_featured_offers_block_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }

	acf_add_local_field_group( array(
		'key'    => 'group_nekoko_featured_offers_block',
		'title'  => 'Ponuda – istaknuto',
		'fields' => array(
			array( 'key' => 'field_nekoko_featured_offers_label', 'label' => 'Tekst plave oznake', 'name' => 'featured_offers_label', 'type' => 'text', 'default_value' => 'Ponuda' ),
			array( 'key' => 'field_nekoko_featured_offers_title', 'label' => 'Naslov', 'name' => 'featured_offers_title', 'type' => 'text', 'default_value' => 'Istaknite ono što nudite' ),
			array( 'key' => 'field_nekoko_featured_offers_text', 'label' => 'Tekst', 'name' => 'featured_offers_text', 'type' => 'textarea', 'rows' => 4, 'default_value' => 'Od čuvanja egzotičnih ljubimaca do kreativnih radionica, NekoKO daje prostor uslugama koje su korisne, neobične i ljudima zaista trebaju.' ),
			array( 'key' => 'field_nekoko_featured_offers_cta_label', 'label' => 'Tekst CTA dugmeta', 'name' => 'featured_offers_cta_label', 'type' => 'text', 'default_value' => 'Objavite ponudu' ),
			array( 'key' => 'field_nekoko_featured_offers_cta_link', 'label' => 'CTA link', 'name' => 'featured_offers_cta_link', 'type' => 'link', 'return_format' => 'array' ),
			array( 'key' => 'field_nekoko_featured_offers_listings', 'label' => 'Ručno izabrani oglasi', 'instructions' => 'Opciono. Ako ne izaberete oglase, prikazuju se četiri slučajne objavljene ponude.', 'name' => 'featured_offers_listings', 'type' => 'relationship', 'post_type' => array( 'oglas' ), 'post_status' => array( 'publish' ), 'filters' => array( 'search' ), 'return_format' => 'id', 'max' => 4 ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-featured-offers' ) ) ),
		'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_featured_offers_block_fields', 45 );

function nekoko_core_enqueue_featured_offers_block_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	$plugin_url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-featured-offers-block', $plugin_url . 'assets/featured-offers-block.css', array(), filemtime( $plugin_dir . '/assets/featured-offers-block.css' ) );
}

function nekoko_core_featured_offer_listings( $selected_listings = array() ) {
	$ids = array_values( array_filter( array_map( 'absint', (array) $selected_listings ) ) );
	if ( $ids ) {
		return get_posts( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 4 ) );
	}

	$roots = nekoko_core_category_roots();
	$root = $roots['ponuda'] ?? null;
	if ( ! $root ) { return array(); }
	return get_posts( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'posts_per_page' => 4, 'orderby' => 'rand', 'no_found_rows' => true, 'tax_query' => array( array( 'taxonomy' => 'kategorija_oglasa', 'field' => 'term_id', 'terms' => array( $root->term_id ), 'include_children' => true ) ) ) );
}
