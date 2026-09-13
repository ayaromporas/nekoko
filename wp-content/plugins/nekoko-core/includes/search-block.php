<?php
/** Search block and its public, read-only category endpoint. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_search_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }

	acf_register_block_type( array(
		'name'            => 'nekoko-search',
		'title'           => 'Pretraga oglasa',
		'description'     => 'Pretraga oglasa po pojmu, tipu i podkategoriji.',
		'category'        => 'design',
		'icon'            => 'search',
		'keywords'        => array( 'pretraga', 'oglasi', 'ponuda', 'potražnja' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/search-block/render.php',
		'mode'            => 'edit',
		'supports'        => array( 'align' => false, 'anchor' => false, 'jsx' => true ),
		'enqueue_assets'  => 'nekoko_core_enqueue_search_block_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_search_block', 42 );

function nekoko_core_register_search_block_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }

	acf_add_local_field_group( array(
		'key'      => 'group_nekoko_search_block',
		'title'    => 'Blok pretrage',
		'fields'   => array(
			array( 'key' => 'field_nekoko_search_block_title', 'label' => 'Naslov', 'name' => 'search_title', 'type' => 'text', 'default_value' => 'Pretražite', 'required' => 1 ),
			array( 'key' => 'field_nekoko_search_block_text', 'label' => 'Tekst', 'name' => 'search_text', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Pronađite neobičan posao, specifičnu pomoć ili osobu za poseban zadatak.', 'required' => 1 ),
			array( 'key' => 'field_nekoko_search_block_placeholder', 'label' => 'Placeholder polja za pretragu', 'name' => 'search_placeholder', 'type' => 'text', 'default_value' => 'Šta tražite?', 'required' => 1 ),
			array( 'key' => 'field_nekoko_search_block_submit', 'label' => 'Labela dugmeta', 'name' => 'search_submit_label', 'type' => 'text', 'default_value' => 'Pošalji', 'required' => 1 ),
		),
		'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-search' ) ) ),
		'active'   => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_search_block_fields', 43 );

function nekoko_core_enqueue_search_block_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	$plugin_url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-search-block', $plugin_url . 'assets/search-block.css', array(), filemtime( $plugin_dir . '/assets/search-block.css' ) );
	wp_enqueue_script( 'nekoko-search-block', $plugin_url . 'assets/search-block.js', array(), filemtime( $plugin_dir . '/assets/search-block.js' ), true );
}

/** Return all descendants of one category branch for the public search form. */
function nekoko_core_search_block_categories() {
	$branch = sanitize_key( wp_unslash( $_GET['branch'] ?? '' ) );
	$roots = nekoko_core_category_roots();
	$root = $roots[ $branch ] ?? null;
	if ( ! $root ) { wp_send_json_error( array( 'message' => 'Nepoznat tip oglasa.' ), 400 ); }

	$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) { wp_send_json_error( array( 'message' => 'Kategorije trenutno nisu dostupne.' ), 500 ); }

	$by_parent = array();
	foreach ( $terms as $term ) { $by_parent[ (int) $term->parent ][] = $term; }
	$output = array();
	$collect = function( $parent, $depth ) use ( &$collect, &$output, $by_parent ) {
		foreach ( $by_parent[ (int) $parent ] ?? array() as $term ) {
			$output[] = array( 'id' => (int) $term->term_id, 'label' => str_repeat( '— ', $depth ) . $term->name );
			$collect( $term->term_id, $depth + 1 );
		}
	};
	$collect( $root->term_id, 0 );
	wp_send_json_success( array( 'categories' => $output ) );
}
add_action( 'wp_ajax_nekoko_search_categories', 'nekoko_core_search_block_categories' );
add_action( 'wp_ajax_nopriv_nekoko_search_categories', 'nekoko_core_search_block_categories' );
