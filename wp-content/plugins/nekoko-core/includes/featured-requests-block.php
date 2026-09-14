<?php
/** Reusable featured-requests block. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_featured_requests_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }

	acf_register_block_type( array(
		'name'            => 'nekoko-featured-requests',
		'title'           => 'Potražnja – istaknuto',
		'description'     => 'Istaknuta Potražnja sekcija sa četiri oglasa.',
		'category'        => 'design',
		'icon'            => 'screenoptions',
		'keywords'        => array( 'potražnja', 'oglasi', 'istaknuto', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/featured-requests/render.php',
		'mode'            => 'edit',
		'supports'        => array( 'align' => false, 'anchor' => false, 'jsx' => true ),
		'enqueue_assets'  => 'nekoko_core_enqueue_featured_offers_block_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_featured_requests_block', 46 );

function nekoko_core_register_featured_requests_block_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }

	acf_add_local_field_group( array(
		'key'    => 'group_nekoko_featured_requests_block',
		'title'  => 'Potražnja – istaknuto',
		'fields' => array(
			array( 'key' => 'field_nekoko_featured_requests_label', 'label' => 'Tekst oznake', 'name' => 'featured_requests_label', 'type' => 'text', 'default_value' => 'Potražnja' ),
			array( 'key' => 'field_nekoko_featured_requests_title', 'label' => 'Naslov', 'name' => 'featured_requests_title', 'type' => 'text', 'default_value' => 'Recite šta vam treba' ),
			array( 'key' => 'field_nekoko_featured_requests_text', 'label' => 'Tekst', 'name' => 'featured_requests_text', 'type' => 'textarea', 'rows' => 4, 'default_value' => 'Objavite Potražnju kada vam je potrebna pomoć — neko da pričuva mačku, pomogne oko selidbe, fotografiše proslavu. Na jednom mestu pronalazite ljude spremne da pomognu.' ),
			array( 'key' => 'field_nekoko_featured_requests_cta_label', 'label' => 'Tekst CTA dugmeta', 'name' => 'featured_requests_cta_label', 'type' => 'text', 'default_value' => 'Objavite potražnju' ),
			array( 'key' => 'field_nekoko_featured_requests_cta_link', 'label' => 'CTA link', 'name' => 'featured_requests_cta_link', 'type' => 'link', 'return_format' => 'array' ),
			array( 'key' => 'field_nekoko_featured_requests_listings', 'label' => 'Ručno izabrani oglasi', 'instructions' => 'Opciono. Ako ne izaberete oglase, prikazuju se četiri slučajne objavljene potražnje.', 'name' => 'featured_requests_listings', 'type' => 'relationship', 'post_type' => array( 'oglas' ), 'post_status' => array( 'publish' ), 'filters' => array( 'search' ), 'return_format' => 'id', 'max' => 4 ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-featured-requests' ) ) ),
		'active'   => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_featured_requests_block_fields', 47 );

function nekoko_core_featured_request_listings( $selected_listings = array() ) {
	$ids = array_values( array_filter( array_map( 'absint', (array) $selected_listings ) ) );
	if ( $ids ) {
		return get_posts( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'posts_per_page' => 4 ) );
	}

	$roots = nekoko_core_category_roots();
	$root = $roots['potraznja'] ?? null;
	if ( ! $root ) { return array(); }
	return get_posts( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'posts_per_page' => 4, 'orderby' => 'rand', 'no_found_rows' => true, 'tax_query' => array( array( 'taxonomy' => 'kategorija_oglasa', 'field' => 'term_id', 'terms' => array( $root->term_id ), 'include_children' => true ) ) ) );
}
