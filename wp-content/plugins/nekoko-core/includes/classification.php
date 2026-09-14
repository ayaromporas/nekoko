<?php
/** A listing belongs to exactly one category branch. No duplicated post meta. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_category_roots() {
	$roots = array();
	foreach ( array( 'ponuda', 'potraznja' ) as $slug ) {
		$term = get_term_by( 'slug', $slug, 'kategorija_oglasa' );
		if ( $term && ! $term->parent ) { $roots[ $slug ] = $term; }
	}
	return $roots;
}

/** Root taxonomy terms are represented by their dedicated landing pages. */
function nekoko_core_redirect_root_category_landing_pages() {
	if ( ! is_tax( 'kategorija_oglasa' ) ) { return; }
	$term = get_queried_object();
	if ( ! $term || is_wp_error( $term ) || (int) $term->parent || ! in_array( $term->slug, array( 'ponuda', 'potraznja' ), true ) ) { return; }
	wp_safe_redirect( nekoko_core_page_url( $term->slug ), 301 );
	exit;
}
add_action( 'template_redirect', 'nekoko_core_redirect_root_category_landing_pages', 1 );

function nekoko_core_category_root( $term_id ) {
	$term = get_term( (int) $term_id, 'kategorija_oglasa' );
	$visited = array();
	while ( $term && ! is_wp_error( $term ) && $term->parent ) {
		if ( isset( $visited[ $term->term_id ] ) ) { return false; }
		$visited[ $term->term_id ] = true;
		$term = get_term( $term->parent, 'kategorija_oglasa' );
	}
	return $term && ! is_wp_error( $term ) && in_array( $term->slug, array( 'ponuda', 'potraznja' ), true ) ? $term : false;
}

function nekoko_core_category_path( $term_id, $include_root = false ) {
	$ids = array_reverse( get_ancestors( (int) $term_id, 'kategorija_oglasa', 'taxonomy' ) );
	$ids[] = (int) $term_id;
	$names = array();
	foreach ( $ids as $id ) {
		$term = get_term( $id, 'kategorija_oglasa' );
		if ( $term && ! is_wp_error( $term ) && ( $include_root || $term->parent ) ) { $names[] = $term->name; }
	}
	return implode( ' → ', $names );
}

/** Validate all IDs, reject mixed branches, and add the inferred root. */
function nekoko_core_classify_categories( $raw ) {
	$error = new WP_Error( 'nekoko_invalid_classification', 'Izaberite Ponudu ili Potražnju i kategorije samo iz te grane.', array( 'status' => 400 ) );
	if ( ! is_array( $raw ) || ! $raw ) { return $error; }
	$ids = array(); $roots = array();
	foreach ( $raw as $id ) {
		if ( ! is_scalar( $id ) || ! ctype_digit( (string) $id ) || (int) $id < 1 ) { return $error; }
		$root = nekoko_core_category_root( $id );
		if ( ! $root ) { return $error; }
		$ids[] = (int) $id;
		$roots[ $root->term_id ] = $root;
	}
	if ( count( $roots ) !== 1 ) { return $error; }
	$root = reset( $roots );
	$ids[] = (int) $root->term_id;
	$ids = array_values( array_unique( $ids ) ); sort( $ids );
	return array( 'root' => $root, 'ids' => $ids );
}

function nekoko_core_oglas_classification( $post_id ) {
	$ids = wp_get_object_terms( $post_id, 'kategorija_oglasa', array( 'fields' => 'ids' ) );
	return is_wp_error( $ids ) ? $ids : nekoko_core_classify_categories( $ids );
}

/** Return the three taxonomy selections used by the public listing form. */
function nekoko_core_listing_taxonomy_selection( $post_id ) {
	$selection = array( 'branch' => '', 'category' => 0, 'subcategory' => 0 );
	$classification = nekoko_core_oglas_classification( $post_id );
	if ( is_wp_error( $classification ) || empty( $classification['root'] ) ) { return $selection; }

	$root = $classification['root'];
	$selection['branch'] = $root->slug;
	$terms = wp_get_object_terms( $post_id, 'kategorija_oglasa' );
	if ( is_wp_error( $terms ) ) { return $selection; }

	foreach ( $terms as $term ) {
		if ( (int) $term->parent === (int) $root->term_id ) { $selection['category'] = (int) $term->term_id; continue; }
		$parent = $term->parent ? get_term( $term->parent, 'kategorija_oglasa' ) : null;
		if ( $parent && ! is_wp_error( $parent ) && (int) $parent->parent === (int) $root->term_id ) { $selection['category'] = (int) $parent->term_id; $selection['subcategory'] = (int) $term->term_id; }
	}
	return $selection;
}

/** Keep only the deepest assigned paths, so parent names are not repeated. */
function nekoko_core_oglas_categories( $post_id ) {
	$terms = wp_get_object_terms( $post_id, 'kategorija_oglasa' );
	if ( is_wp_error( $terms ) ) { return array(); }
	$parents = array();
	foreach ( $terms as $term ) { $parents = array_merge( $parents, get_ancestors( $term->term_id, 'kategorija_oglasa', 'taxonomy' ) ); }
	return array_values( array_filter( $terms, function( $term ) use ( $parents ) { return $term->parent && ! in_array( $term->term_id, $parents, true ); } ) );
}

/** Validate before the REST callback (including autosaves, whose core controller
 * does not safely handle WP_Error from prepare_item_for_database()). Core REST
 * handles authentication, cookie nonce and edit/assign capabilities. */
function nekoko_core_validate_classification_rest( $response, $handler, $request ) {
	if ( null !== $response || ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) || ! preg_match( '~^/wp/v2/oglasi(?:/([0-9]+))?(/autosaves)?/?$~', $request->get_route(), $matches ) ) { return $response; }
	$id = isset( $matches[1] ) ? (int) $matches[1] : 0;
	$autosave = ! empty( $matches[2] );
	$ids = $request->has_param( 'kategorija_oglasa' ) ? $request->get_param( 'kategorija_oglasa' ) : ( $id ? wp_get_object_terms( $id, 'kategorija_oglasa', array( 'fields' => 'ids' ) ) : array() );
	// An unfinished autosave can remain unclassified, but cannot be published.
	if ( $autosave && array() === $ids && in_array( get_post_status( $id ), array( 'draft', 'auto-draft' ), true ) ) { return $response; }
	$result = nekoko_core_classify_categories( $ids );
	if ( is_wp_error( $result ) ) { return $result; }
	$request->set_param( 'kategorija_oglasa', $result['ids'] );
	return $response;
}
add_filter( 'rest_request_before_callbacks', 'nekoko_core_validate_classification_rest', 20, 3 );

function nekoko_core_prepare_classification_rest( $prepared, $request ) {
	$ids = $request->get_param( 'kategorija_oglasa' );
	if ( is_array( $ids ) && $ids ) { $prepared->tax_input['kategorija_oglasa'] = $ids; }
	return $prepared;
}
add_filter( 'rest_pre_insert_oglas', 'nekoko_core_prepare_classification_rest', 20, 2 );

/** Prevent invalid WP insert/update imports before the post is written.
 * WP offers only an empty-content short circuit here; REST/classic editors
 * receive the more specific validation message before reaching this hook. */
function nekoko_core_validate_classification_insert( $empty, $postarr ) {
	if ( 'oglas' !== $postarr['post_type'] || in_array( $postarr['post_status'], array( 'auto-draft', 'trash' ), true ) ) { return $empty; }
	$ids = $postarr['tax_input']['kategorija_oglasa'] ?? ( ! empty( $postarr['ID'] ) ? wp_get_object_terms( $postarr['ID'], 'kategorija_oglasa', array( 'fields' => 'ids' ) ) : array() );
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE && array() === $ids && in_array( $postarr['post_status'], array( 'draft', 'auto-draft' ), true ) ) { return $empty; }
	return $empty || is_wp_error( nekoko_core_classify_categories( $ids ) );
}
add_filter( 'wp_insert_post_empty_content', 'nekoko_core_validate_classification_insert', 20, 2 );

/** Low-level taxonomy APIs have no pre-assignment error filter. Normalize valid
 * writes; roll invalid writes back to their previous valid terms. */
function nekoko_core_enforce_classification( $post_id, $previous_tt_ids = array() ) {
	static $busy = array();
	if ( isset( $busy[ $post_id ] ) || ! empty( $GLOBALS['nekoko_core_deleting'][ $post_id ] ) || 'oglas' !== get_post_type( $post_id ) ) { return; }
	$ids = wp_get_object_terms( $post_id, 'kategorija_oglasa', array( 'fields' => 'ids' ) );
	if ( is_wp_error( $ids ) ) { return; }
	$result = nekoko_core_classify_categories( $ids );
	if ( is_wp_error( $result ) ) {
		$previous = array();
		foreach ( $previous_tt_ids as $tt_id ) { $term = get_term_by( 'term_taxonomy_id', $tt_id, 'kategorija_oglasa' ); if ( $term ) { $previous[] = (int) $term->term_id; } }
		$result = nekoko_core_classify_categories( $previous );
		$target = is_wp_error( $result ) ? array() : $result['ids'];
		$GLOBALS['nekoko_classification_corrected'] = true;
	} else { $target = $result['ids']; }
	sort( $ids ); sort( $target );
	if ( $ids === $target ) { return; }
	$busy[ $post_id ] = true;
	wp_set_object_terms( $post_id, $target, 'kategorija_oglasa', false );
	unset( $busy[ $post_id ] );
}
function nekoko_core_classification_terms_set( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
	if ( 'kategorija_oglasa' !== $taxonomy ) { return; }
	// WordPress passes no old IDs when append=true. Capture them before the
	// first new relationship so mixed-branch appends can be rolled back too.
	if ( ! $old_tt_ids && isset( $GLOBALS['nekoko_classification_before_add'][ $object_id ] ) ) { $old_tt_ids = $GLOBALS['nekoko_classification_before_add'][ $object_id ]; }
	nekoko_core_enforce_classification( $object_id, $old_tt_ids );
	unset( $GLOBALS['nekoko_classification_before_add'][ $object_id ] );
}
add_action( 'set_object_terms', 'nekoko_core_classification_terms_set', 100, 6 );
function nekoko_core_classification_before_add( $object_id, $tt_id, $taxonomy ) {
	if ( 'kategorija_oglasa' !== $taxonomy || 'oglas' !== get_post_type( $object_id ) || isset( $GLOBALS['nekoko_classification_before_add'][ $object_id ] ) ) { return; }
	$previous = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'tt_ids' ) );
	$GLOBALS['nekoko_classification_before_add'][ $object_id ] = is_wp_error( $previous ) ? array() : $previous;
}
add_action( 'add_term_relationship', 'nekoko_core_classification_before_add', 10, 3 );
function nekoko_core_classification_terms_removed( $object_id, $tt_ids, $taxonomy ) {
	if ( 'kategorija_oglasa' !== $taxonomy ) { return; }
	$current = wp_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'tt_ids' ) );
	nekoko_core_enforce_classification( $object_id, array_merge( is_wp_error( $current ) ? array() : $current, $tt_ids ) );
}
add_action( 'deleted_term_relationships', 'nekoko_core_classification_terms_removed', 100, 3 );
function nekoko_core_classification_before_delete( $id ) { $GLOBALS['nekoko_core_deleting'][ $id ] = true; }
function nekoko_core_classification_after_delete( $id ) { unset( $GLOBALS['nekoko_core_deleting'][ $id ] ); }
add_action( 'before_delete_post', 'nekoko_core_classification_before_delete' );
add_action( 'deleted_post', 'nekoko_core_classification_after_delete' );
