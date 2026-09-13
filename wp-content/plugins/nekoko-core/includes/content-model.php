<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_taxonomy_labels( $singular, $plural ) {
	return array(
		'name' => $plural, 'singular_name' => $singular, 'menu_name' => $plural,
		'search_items' => 'Pretraži: ' . $plural, 'popular_items' => 'Najčešće korišćeni termini',
		'all_items' => 'Svi termini', 'parent_item' => 'Nadređeni termin', 'parent_item_colon' => 'Nadređeni termin:',
		'edit_item' => 'Uredi termin', 'view_item' => 'Pogledaj termin', 'update_item' => 'Ažuriraj termin',
		'add_new_item' => 'Dodaj novi termin', 'new_item_name' => 'Naziv novog termina',
		'separate_items_with_commas' => 'Razdvojite termine zarezima', 'add_or_remove_items' => 'Dodaj ili ukloni termine',
		'choose_from_most_used' => 'Izaberi među najčešćim terminima', 'not_found' => 'Nema pronađenih termina.',
		'no_terms' => 'Nema termina', 'filter_by_item' => 'Filtriraj prema terminu',
		'items_list_navigation' => 'Navigacija kroz termine', 'items_list' => 'Lista termina',
		'back_to_items' => 'Nazad na listu termina', 'item_link' => 'Link termina',
		'item_link_description' => 'Link ka arhivi ovog termina.',
		'name_field_description' => 'Naziv koji se prikazuje posetiocima.',
		'slug_field_description' => 'Deo URL adrese, malim slovima i sa crticama.',
		'parent_field_description' => 'Izaberite nadređenu kategoriju za ovu podkategoriju.',
		'desc_field_description' => 'Opis koji se prikazuje na arhivi termina.',
	);
}

function nekoko_core_register_model() {
	register_post_type( 'oglas', array(
		'labels' => array(
			'name' => 'Oglasi', 'singular_name' => 'Oglas', 'menu_name' => 'Oglasi', 'name_admin_bar' => 'Oglas',
			'add_new' => 'Dodaj oglas', 'add_new_item' => 'Dodaj novi oglas', 'edit_item' => 'Uredi oglas',
			'new_item' => 'Novi oglas', 'view_item' => 'Pogledaj oglas', 'view_items' => 'Pogledaj oglase',
			'search_items' => 'Pretraži oglase', 'not_found' => 'Nema pronađenih oglasa.',
			'not_found_in_trash' => 'Nema oglasa u otpadu.', 'all_items' => 'Svi oglasi',
			'archives' => 'Oglasi', 'attributes' => 'Svojstva oglasa', 'parent_item_colon' => 'Nadređeni oglas:',
			'insert_into_item' => 'Umetni u oglas', 'uploaded_to_this_item' => 'Otpremljeno uz ovaj oglas',
			'featured_image' => 'Izdvojena slika', 'set_featured_image' => 'Postavi izdvojenu sliku',
			'remove_featured_image' => 'Ukloni izdvojenu sliku', 'use_featured_image' => 'Koristi kao izdvojenu sliku',
			'filter_items_list' => 'Filtriraj listu oglasa', 'filter_by_date' => 'Filtriraj prema datumu',
			'items_list_navigation' => 'Navigacija kroz oglase', 'items_list' => 'Lista oglasa',
			'item_published' => 'Oglas je objavljen.', 'item_published_privately' => 'Oglas je objavljen privatno.',
			'item_reverted_to_draft' => 'Oglas je vraćen u nacrt.', 'item_trashed' => 'Oglas je premešten u otpad.',
			'item_scheduled' => 'Objavljivanje oglasa je zakazano.', 'item_updated' => 'Oglas je ažuriran.',
			'item_link' => 'Link oglasa', 'item_link_description' => 'Link ka pojedinačnom oglasu.',
		),
		'public' => true, 'show_ui' => true, 'show_in_rest' => true, 'rest_base' => 'oglasi',
		'has_archive' => 'oglasi', 'rewrite' => array( 'slug' => 'oglas', 'with_front' => false ),
		'menu_icon' => 'dashicons-megaphone',
		'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'revisions', 'custom-fields' ),
	) );

	$common = array( 'public' => true, 'show_ui' => true, 'show_in_rest' => true, 'show_admin_column' => true );
	register_taxonomy( 'kategorija_oglasa', array( 'oglas' ), array_merge( $common, array(
		'labels' => nekoko_core_taxonomy_labels( 'Kategorija oglasa', 'Kategorije oglasa' ),
		'hierarchical' => true, 'rewrite' => array( 'slug' => 'kategorije', 'with_front' => false, 'hierarchical' => false ),
		'meta_box_cb' => false, 'show_in_quick_edit' => false,
	) ) );
	register_taxonomy( 'oglas_tag', array( 'oglas' ), array_merge( $common, array(
		'labels' => nekoko_core_taxonomy_labels( 'Tag oglasa', 'Tagovi oglasa' ),
		'hierarchical' => false, 'rewrite' => array( 'slug' => 'oznake', 'with_front' => false ),
	) ) );
	foreach ( nekoko_core_rewrite_rules() as $regex => $query ) { add_rewrite_rule( $regex, $query, 'top' ); }
}
add_action( 'init', 'nekoko_core_register_model' );

/** Explicit portal-only routes also work when older content uses index.php permalinks. */
function nekoko_core_rewrite_rules() {
	$rules = array(
		// Header CTA fallback for installations retaining legacy index.php permalinks.
		'^postavite-oglas/?$' => 'index.php?pagename=postavite-oglas',
		'^oglasi/page/([0-9]+)/?$' => 'index.php?post_type=oglas&paged=$matches[1]',
		'^oglasi/feed/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?post_type=oglas&feed=$matches[1]',
		'^oglasi/(feed|rdf|rss|rss2|atom)/?$' => 'index.php?post_type=oglas&feed=$matches[1]',
		'^oglasi/?$' => 'index.php?post_type=oglas',
		'^oglas/([^/]+)/([0-9]+)/?$' => 'index.php?oglas=$matches[1]&page=$matches[2]',
		'^oglas/([^/]+)/?$' => 'index.php?oglas=$matches[1]',
	);
	foreach ( array( 'kategorije' => 'kategorija_oglasa', 'oznake' => 'oglas_tag' ) as $base => $taxonomy ) {
		$rules[ '^' . $base . '/([^/]+)/page/([0-9]+)/?$' ] = 'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]';
		$rules[ '^' . $base . '/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' ] = 'index.php?' . $taxonomy . '=$matches[1]&feed=$matches[2]';
		$rules[ '^' . $base . '/([^/]+)/?$' ] = 'index.php?' . $taxonomy . '=$matches[1]';
	}
	return $rules;
}
function nekoko_core_post_link( $url, $post, $leavename ) {
	if ( 'oglas' !== $post->post_type || ! get_option( 'permalink_structure' ) ) { return $url; }
	$slug = $leavename ? '%oglas%' : $post->post_name;
	return $slug ? home_url( user_trailingslashit( 'oglas/' . $slug, 'single' ) ) : $url;
}
add_filter( 'post_type_link', 'nekoko_core_post_link', 10, 3 );
function nekoko_core_archive_link( $url, $post_type ) {
	return 'oglas' === $post_type && get_option( 'permalink_structure' ) ? home_url( '/oglasi/' ) : $url;
}
add_filter( 'post_type_archive_link', 'nekoko_core_archive_link', 10, 2 );
function nekoko_core_term_link( $url, $term, $taxonomy ) {
	$bases = array( 'kategorija_oglasa' => 'kategorije', 'oglas_tag' => 'oznake' );
	return isset( $bases[ $taxonomy ] ) && get_option( 'permalink_structure' ) ? home_url( user_trailingslashit( $bases[ $taxonomy ] . '/' . $term->slug, 'category' ) ) : $url;
}
add_filter( 'term_link', 'nekoko_core_term_link', 10, 3 );

/** Keep WordPress pagination and canonical redirects on the portal's clean routes. */
function nekoko_core_portal_paging_url( $url ) {
	if ( ! $url || is_admin() || ! ( is_post_type_archive( 'oglas' ) || is_singular( 'oglas' ) || is_tax( array( 'kategorija_oglasa', 'oglas_tag' ) ) ) ) { return $url; }
	$root = home_url( '/' );
	if ( 0 !== strpos( $url, $root ) ) { return $url; }
	$parts = explode( '?', substr( $url, strlen( $root ) ), 2 );
	$parts[0] = preg_replace( '~(^|/)index\.php/~', '$1', $parts[0] );
	return $root . implode( '?', $parts );
}
add_filter( 'get_pagenum_link', 'nekoko_core_portal_paging_url' );
add_filter( 'paginate_links', 'nekoko_core_portal_paging_url' );
add_filter( 'redirect_canonical', 'nekoko_core_portal_paging_url' );
