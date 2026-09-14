<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_classification_box( $post ) {
	$classification = nekoko_core_oglas_classification( $post->ID );
	$selected = is_wp_error( $classification ) ? array() : $classification['ids'];
	$root_id = is_wp_error( $classification ) ? 0 : $classification['root']->term_id;
	wp_nonce_field( 'nekoko_classification_' . $post->ID, 'nekoko_classification_nonce' );
	echo '<div class="nekoko-classification"><fieldset><legend>Tip oglasa (obavezno)</legend>';
	$roots = nekoko_core_category_roots();
	foreach ( $roots as $root ) {
		printf( '<p><label><input type="radio" required name="tax_input[kategorija_oglasa][]" value="%1$d" data-nekoko-root %2$s> %3$s</label></p>', $root->term_id, checked( $root_id, $root->term_id, false ), esc_html( $root->name ) );
	}
	echo '</fieldset><p>Izaberite jednu ili više kategorija iz iste grane.</p>';
	$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false ) );
	$render = function( $parent ) use ( &$render, $terms, $selected ) {
		echo '<ul class="nekoko-classification-tree">';
		foreach ( $terms as $term ) {
			if ( (int) $term->parent !== (int) $parent ) { continue; }
			printf( '<li><label><input type="checkbox" name="tax_input[kategorija_oglasa][]" value="%1$d" %2$s> %3$s</label>', $term->term_id, checked( in_array( $term->term_id, $selected, true ), true, false ), esc_html( $term->name ) );
			$render( $term->term_id ); echo '</li>';
		}
		echo '</ul>';
	};
	foreach ( $roots as $root ) {
		printf( '<fieldset data-nekoko-branch="%d"><legend>Kategorije: %s</legend>', $root->term_id, esc_html( $root->name ) );
		$render( $root->term_id ); echo '</fieldset>';
	}
	if ( ! $roots ) { echo '<p>Nedostaju grane kategorija. Obratite se administratoru.</p>'; }
	echo '</div>';
}
add_action( 'add_meta_boxes_oglas', function() {
	add_meta_box( 'nekoko-classification', 'Klasifikacija oglasa', 'nekoko_core_classification_box', 'oglas', 'side', 'high', array( '__back_compat_meta_box' => true ) );
} );

/** Classic editor validation happens before edit_post(), not after data loss. */
function nekoko_core_validate_classic_classification() {
	if ( ( $_POST['action'] ?? '' ) !== 'editpost' ) { return; }
	$id = absint( $_POST['post_ID'] ?? 0 );
	if ( 'oglas' !== get_post_type( $id ) ) { return; }
	if ( ! current_user_can( 'edit_post', $id ) || ! current_user_can( get_taxonomy( 'kategorija_oglasa' )->cap->assign_terms ) ) { wp_die( 'Nemate dozvolu za izmenu klasifikacije.', '', array( 'response' => 403 ) ); }
	check_admin_referer( 'nekoko_classification_' . $id, 'nekoko_classification_nonce' );
	$raw = isset( $_POST['tax_input']['kategorija_oglasa'] ) ? wp_unslash( $_POST['tax_input']['kategorija_oglasa'] ) : array();
	$result = nekoko_core_classify_categories( $raw );
	if ( is_wp_error( $result ) ) { wp_die( esc_html( $result->get_error_message() ), 'Klasifikacija oglasa', array( 'response' => 400, 'back_link' => true ) ); }
	$_POST['tax_input']['kategorija_oglasa'] = $result['ids'];
}
add_action( 'load-post.php', 'nekoko_core_validate_classic_classification' );

function nekoko_core_classification_editor_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'oglas' !== $screen->post_type ) { return; }
	wp_enqueue_script( 'nekoko-classification-editor', plugins_url( 'assets/classification-editor.js', NEKOKO_CORE_FILE ), array( 'wp-hooks', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-plugins', 'wp-api-fetch', 'wp-html-entities' ), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/classification-editor.js' ), true );
}
add_action( 'enqueue_block_editor_assets', 'nekoko_core_classification_editor_assets' );
function nekoko_core_classification_admin_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'oglas' !== $screen->post_type ) { return; }
	wp_enqueue_script( 'nekoko-classification-admin', plugins_url( 'assets/classification-admin.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/classification-admin.js' ), true );
	wp_localize_script( 'nekoko-classification-admin', 'nekokoListingApproval', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nekoko_listing_approval' ), 'error' => 'Status oglasa nije moguće promeniti. Pokušajte ponovo.' ) );
	wp_enqueue_style( 'nekoko-classification-admin', plugins_url( 'assets/classification-admin.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/classification-admin.css' ) );
}
add_action( 'admin_enqueue_scripts', 'nekoko_core_classification_admin_assets' );

add_filter( 'manage_oglas_posts_columns', function( $columns ) {
	return array( 'cb' => $columns['cb'] ?? '', 'title' => 'Naslov', 'author' => 'Autor', 'nekoko_branch' => 'Tip', 'nekoko_category' => 'Kategorija', 'nekoko_approval' => 'Odobren', 'date' => 'Datum' );
} );
add_action( 'manage_oglas_posts_custom_column', function( $column, $post_id ) {
	if ( 'nekoko_branch' === $column ) {
		$result = nekoko_core_oglas_classification( $post_id );
		if ( is_wp_error( $result ) ) { echo 'Nije izabrano'; return; }
		echo '<a href="' . esc_url( add_query_arg( array( 'post_type' => 'oglas', 'nekoko_branch' => $result['root']->slug ), admin_url( 'edit.php' ) ) ) . '">' . esc_html( $result['root']->name ) . '</a>';
	} elseif ( 'nekoko_category' === $column ) {
		$paths = array_map( function( $term ) { return esc_html( nekoko_core_category_path( $term->term_id ) ); }, nekoko_core_oglas_categories( $post_id ) );
		echo $paths ? implode( '<br>', $paths ) : '—';
	}
}, 10, 2 );

add_action( 'manage_oglas_posts_custom_column', function( $column, $post_id ) {
	if ( 'nekoko_approval' !== $column ) {
		return;
	}
	$approved = 'publish' === get_post_status( $post_id );
	printf( '<label class="nekoko-listing-approval"><input type="checkbox" data-nekoko-listing-approval data-post-id="%1$d" %2$s aria-label="%3$s"><span data-nekoko-listing-approval-status>%4$s</span></label>', $post_id, checked( $approved, true, false ), esc_attr( $approved ? 'Oglas je odobren. Odznačite za vraćanje na čekanje.' : 'Oglas je na čekanju. Označite za odobravanje.' ), esc_html( $approved ? 'Odobren' : 'Na čekanju' ) );
}, 10, 2 );

function nekoko_core_toggle_listing_approval() {
	check_ajax_referer( 'nekoko_listing_approval', 'nonce' );
	$post_id  = absint( $_POST['post_id'] ?? 0 );
	$approved = ! empty( $_POST['approved'] );
	if ( ! $post_id || 'oglas' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Nemate dozvolu za izmenu ovog oglasa.' ), 403 );
	}
	$updated = wp_update_post( array( 'ID' => $post_id, 'post_status' => $approved ? 'publish' : 'draft' ), true );
	if ( is_wp_error( $updated ) ) {
		wp_send_json_error( array( 'message' => $updated->get_error_message() ), 500 );
	}
	wp_send_json_success( array( 'approved' => $approved, 'label' => $approved ? 'Odobren' : 'Na čekanju' ) );
}
add_action( 'wp_ajax_nekoko_toggle_listing_approval', 'nekoko_core_toggle_listing_approval' );

add_filter( 'manage_edit-kategorija_oglasa_columns', function( $columns ) { $columns['nekoko_branch'] = 'Tip/Grana'; return $columns; } );
add_filter( 'manage_kategorija_oglasa_custom_column', function( $output, $column, $id ) {
	if ( 'nekoko_branch' !== $column ) { return $output; }
	$root = nekoko_core_category_root( $id );
	return $root ? esc_html( $root->name ) : 'Neraspoređeno';
}, 10, 3 );

function nekoko_core_classification_filters( $post_type ) {
	if ( 'oglas' !== $post_type ) { return; }
	$branch = sanitize_key( $_GET['nekoko_branch'] ?? '' );
	$category = absint( $_GET['nekoko_category'] ?? 0 );
	echo '<label class="screen-reader-text" for="nekoko-branch-filter">Tip oglasa</label><select name="nekoko_branch" id="nekoko-branch-filter"><option value="">Svi tipovi</option>';
	foreach ( nekoko_core_category_roots() as $root ) { printf( '<option value="%s" %s>%s</option>', esc_attr( $root->slug ), selected( $branch, $root->slug, false ), esc_html( $root->name ) ); }
	echo '</select><label class="screen-reader-text" for="nekoko-category-filter">Kategorija oglasa</label><select name="nekoko_category" id="nekoko-category-filter"><option value="">Sve kategorije iz grane</option>';
	foreach ( get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false ) ) as $term ) {
		if ( ! $term->parent ) { continue; }
		$root = nekoko_core_category_root( $term->term_id );
		if ( ! $root ) { continue; }
		printf( '<option value="%d" data-branch="%s" %s>%s</option>', $term->term_id, esc_attr( $root->slug ), selected( $category, $term->term_id, false ), esc_html( nekoko_core_category_path( $term->term_id ) ) );
	}
	echo '</select>';
}
add_action( 'restrict_manage_posts', 'nekoko_core_classification_filters' );
function nekoko_core_filter_admin_oglasi( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'oglas' !== $query->get( 'post_type' ) ) { return; }
	$branch = sanitize_key( $_GET['nekoko_branch'] ?? '' ); $category = absint( $_GET['nekoko_category'] ?? 0 );
	if ( ! $branch && ! $category ) { return; }
	$roots = nekoko_core_category_roots(); $root = $roots[ $branch ] ?? null;
	if ( ! $root ) { $query->set( 'post__in', array( 0 ) ); return; }
	if ( $category ) {
		$category_root = nekoko_core_category_root( $category );
		if ( ! $category_root || $category_root->term_id !== $root->term_id ) { $query->set( 'post__in', array( 0 ) ); return; }
	}
	$tax_query = (array) $query->get( 'tax_query' );
	$tax_query[] = array( 'taxonomy' => 'kategorija_oglasa', 'field' => 'term_id', 'terms' => array( $category ?: $root->term_id ), 'include_children' => true );
	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'nekoko_core_filter_admin_oglasi' );
