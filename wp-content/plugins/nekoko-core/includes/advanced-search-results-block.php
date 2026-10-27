<?php
/** Public advanced-search filters and AJAX result renderer. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_advanced_search_results_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array( 'name' => 'nekoko-advanced-search-results', 'title' => 'Napredna pretraga – rezultati', 'description' => 'Filteri i dinamički rezultati oglasa.', 'category' => 'design', 'icon' => 'filter', 'keywords' => array( 'pretraga', 'filteri', 'oglasi', 'NekoKO' ), 'render_template' => dirname( __DIR__ ) . '/blocks/advanced-search-results/render.php', 'mode' => 'edit', 'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_advanced_search_results_assets' ) );
}
add_action( 'acf/init', 'nekoko_core_register_advanced_search_results_block', 55 );

function nekoko_core_register_advanced_search_results_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array( 'key' => 'group_nekoko_advanced_search_results', 'title' => 'Napredna pretraga – rezultati', 'fields' => array(
		array( 'key' => 'field_nekoko_advanced_results_sidebar_title', 'label' => 'Naslov filtera', 'name' => 'advanced_results_sidebar_title', 'type' => 'text', 'default_value' => 'Filteri' ),
		array( 'key' => 'field_nekoko_advanced_results_title', 'label' => 'Naslov rezultata', 'name' => 'advanced_results_title', 'type' => 'text', 'default_value' => 'Rezultati pretrage' ),
		array( 'key' => 'field_nekoko_advanced_results_empty', 'label' => 'Poruka bez rezultata', 'name' => 'advanced_results_empty', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Nema oglasa koji odgovaraju izabranim filterima.' ),
		array( 'key' => 'field_nekoko_advanced_results_clear', 'label' => 'Tekst za brisanje filtera', 'name' => 'advanced_results_clear', 'type' => 'text', 'default_value' => 'Obriši filtere' ),
	), 'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-advanced-search-results' ) ) ), 'active' => true ) );
}
add_action( 'acf/init', 'nekoko_core_register_advanced_search_results_fields', 56 );

function nekoko_core_enqueue_advanced_search_results_assets() {
	$dir = dirname( NEKOKO_CORE_FILE ); $url = plugins_url( '/', NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-advanced-search-results', $url . 'assets/advanced-search-results.css', array(), filemtime( $dir . '/assets/advanced-search-results.css' ) );
	wp_enqueue_script( 'nekoko-advanced-search-results', $url . 'assets/advanced-search-results.js', array(), filemtime( $dir . '/assets/advanced-search-results.js' ), true );
}

function nekoko_core_advanced_search_params( $source ) {
	$source = is_array( $source ) ? $source : array();
	$params = array( 'q' => sanitize_text_field( wp_unslash( $source['q'] ?? '' ) ), 'tip' => sanitize_key( wp_unslash( $source['tip'] ?? '' ) ), 'kategorija' => absint( $source['kategorija'] ?? 0 ), 'grad' => sanitize_text_field( wp_unslash( $source['grad'] ?? '' ) ), 'cena_od' => max( 0, absint( $source['cena_od'] ?? 0 ) ), 'cena_do' => max( 0, absint( $source['cena_do'] ?? 0 ) ), 'trajanje' => sanitize_text_field( wp_unslash( $source['trajanje'] ?? '' ) ), 'sort' => sanitize_key( wp_unslash( $source['sort'] ?? 'novo' ) ) );
	if ( ! in_array( $params['tip'], array( 'ponuda', 'potraznja' ), true ) ) { $params['tip'] = ''; }
	if ( ! in_array( $params['sort'], array( 'novo', 'cena_rastuce', 'cena_opadajuce' ), true ) ) { $params['sort'] = 'novo'; }
	if ( $params['cena_do'] && $params['cena_od'] > $params['cena_do'] ) { $swap = $params['cena_od']; $params['cena_od'] = $params['cena_do']; $params['cena_do'] = $swap; }
	if ( $params['kategorija'] ) { $term = get_term( $params['kategorija'], 'kategorija_oglasa' ); $root = $term && ! is_wp_error( $term ) ? nekoko_core_category_root( $term->term_id ) : false; if ( ! $root || ( $params['tip'] && $root->slug !== $params['tip'] ) ) { $params['kategorija'] = 0; } elseif ( ! $params['tip'] ) { $params['tip'] = $root->slug; } }
	if ( ! $params['tip'] ) { $params['tip'] = 'ponuda'; }
	if ( 'potraznja' === $params['tip'] ) { $params['cena_od'] = 0; $params['cena_do'] = 0; }
	return $params;
}

/**
 * Searches listing titles by the first four characters of each entered word.
 *
 * Serbian word endings vary (for example, macka/macke and cuvam/cuvanje), so
 * matching the word stem is more useful than requiring an exact inflection.
 * This filter is restricted to the advanced-search query via its custom flag.
 */
function nekoko_core_advanced_search_stem_title_search( $search, $query ) {
	$query_text = $query->get( 'nekoko_stem_search' );
	if ( ! is_string( $query_text ) || '' === $query_text ) { return $search; }

	$terms = preg_split( '/[^\p{L}\p{N}]+/u', $query_text, -1, PREG_SPLIT_NO_EMPTY );
	if ( ! $terms ) { return ''; }

	global $wpdb;
	$clauses = array();
	foreach ( $terms as $term ) {
		$length = function_exists( 'mb_strlen' ) ? mb_strlen( $term, 'UTF-8' ) : strlen( $term );
		$stem = $length > 4 ? ( function_exists( 'mb_substr' ) ? mb_substr( $term, 0, 4, 'UTF-8' ) : substr( $term, 0, 4 ) ) : $term;
		$clauses[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $stem ) . '%' );
	}

	return ' AND (' . implode( ' AND ', $clauses ) . ') ';
}
add_filter( 'posts_search', 'nekoko_core_advanced_search_stem_title_search', 10, 2 );

function nekoko_core_advanced_search_query( $params ) {
	$args = array( 'post_type' => 'oglas', 'post_status' => 'publish', 'posts_per_page' => 24, 'nekoko_stem_search' => $params['q'], 'no_found_rows' => false );
	$tax_query = array(); $roots = nekoko_core_category_roots();
	if ( $params['tip'] && isset( $roots[ $params['tip'] ] ) ) { $tax_query[] = array( 'taxonomy' => 'kategorija_oglasa', 'field' => 'term_id', 'terms' => array( $roots[ $params['tip'] ]->term_id ), 'include_children' => true ); }
	if ( $params['kategorija'] ) { $tax_query[] = array( 'taxonomy' => 'kategorija_oglasa', 'field' => 'term_id', 'terms' => array( $params['kategorija'] ), 'include_children' => true ); }
	if ( $tax_query ) { $args['tax_query'] = $tax_query; }
	$meta = array(); if ( $params['grad'] ) { $meta[] = array( 'key' => 'nekoko_listing_location', 'value' => $params['grad'], 'compare' => 'LIKE' ); }
	if ( $params['cena_od'] || $params['cena_do'] ) { $meta[] = array( 'key' => 'nekoko_listing_price', 'value' => array( $params['cena_od'], $params['cena_do'] ?: 999999999 ), 'type' => 'NUMERIC', 'compare' => 'BETWEEN' ); }
	if ( 'potraznja' === $params['tip'] && $params['trajanje'] ) { $meta[] = array( 'key' => 'nekoko_listing_duration', 'value' => $params['trajanje'], 'compare' => 'LIKE' ); }
	if ( $meta ) { $args['meta_query'] = $meta; }
	if ( 'cena_rastuce' === $params['sort'] || 'cena_opadajuce' === $params['sort'] ) { $args['meta_key'] = 'nekoko_listing_price'; $args['orderby'] = 'meta_value_num'; $args['order'] = 'cena_rastuce' === $params['sort'] ? 'ASC' : 'DESC'; }
	return new WP_Query( $args );
}

/** Split a listing's taxonomy terms into direct categories and subcategory pills. */
function nekoko_core_advanced_search_card_categories( $post_id ) {
	$terms = nekoko_core_oglas_categories( $post_id );
	$root = false;
	$categories = array();
	$subcategories = array();

	foreach ( $terms as $term ) {
		$term_root = nekoko_core_category_root( $term->term_id );
		if ( ! $term_root ) { continue; }
		if ( ! $root ) { $root = $term_root; }

		$category = $term;
		if ( (int) $term->parent !== (int) $term_root->term_id ) {
			foreach ( array_reverse( get_ancestors( $term->term_id, 'kategorija_oglasa', 'taxonomy' ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, 'kategorija_oglasa' );
				if ( $ancestor && ! is_wp_error( $ancestor ) && (int) $ancestor->parent === (int) $term_root->term_id ) { $category = $ancestor; break; }
			}
			$subcategories[ $term->term_id ] = $term;
		}
		$categories[ $category->term_id ] = $category;
	}

	return array( 'root' => $root, 'categories' => array_values( $categories ), 'subcategories' => array_values( $subcategories ) );
}

function nekoko_core_advanced_search_results_html( $params, $empty ) {
	$query = nekoko_core_advanced_search_query( $params ); ob_start();
	if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();
		$id = get_the_ID(); $card_categories = nekoko_core_advanced_search_card_categories( $id ); $root = $card_categories['root']; $demand = $root && 'potraznja' === $root->slug; $pill_class = $demand ? ' nekoko-advanced-results__pill--demand' : ''; $category_names = array_map( static function( $category ) { return $category->name; }, $card_categories['categories'] ); $location = get_post_meta( $id, 'nekoko_listing_location', true ); $duration = get_post_meta( $id, 'nekoko_listing_duration', true ); $duration_na = '1' === get_post_meta( $id, 'nekoko_listing_duration_na', true ); $agreed = '1' === get_post_meta( $id, 'nekoko_listing_price_agreed', true ); $price = get_post_meta( $id, 'nekoko_listing_price', true );
		$excerpt = function_exists( 'nekoko_core_listing_excerpt' ) ? nekoko_core_listing_excerpt( $id ) : get_the_excerpt();
		?>
		<article class="nekoko-advanced-results__card">
			<a class="nekoko-advanced-results__card-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>"></a>
			<div class="nekoko-advanced-results__image">
				<?php echo function_exists( 'nekoko_core_test_listing_badge' ) ? nekoko_core_test_listing_badge( $id ) : ''; ?>
				<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large' ); } else { ?><span aria-hidden="true">⌕</span><?php } ?>
			</div>
			<div class="nekoko-advanced-results__card-body">
				<?php if ( $category_names ) : ?>
					<p class="nekoko-advanced-results__category"><?php echo esc_html( implode( ' · ', $category_names ) ); ?></p>
				<?php endif; ?>
				<?php if ( $card_categories['subcategories'] ) : ?>
					<div class="nekoko-advanced-results__pills">
						<?php foreach ( $card_categories['subcategories'] as $subcategory ) : ?>
							<p class="nekoko-advanced-results__pill<?php echo esc_attr( $pill_class ); ?>"><?php echo esc_html( $subcategory->name ); ?></p>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<h3><?php the_title(); ?></h3>
				<?php if ( $excerpt ) : ?><p class="nekoko-advanced-results__excerpt"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
				<p class="nekoko-advanced-results__meta"><?php echo esc_html( $location ?: 'Lokacija nije navedena' ); ?></p>
				<strong><?php echo esc_html( $demand ? ( $duration_na ? 'Trajanje nije primenljivo' : ( $duration ? 'Trajanje ' . $duration : 'Trajanje po dogovoru' ) ) : ( $agreed || '' === $price ? 'Po dogovoru' : number_format_i18n( (float) $price, 0 ) . ' RSD' ) ); ?></strong>
				<time class="nekoko-advanced-results__date" datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">Datum objave: <?php echo esc_html( get_the_date( 'j. F Y.' ) ); ?></time>
			</div>
		</article>
	<?php endwhile; wp_reset_postdata(); else : ?>
		<p class="nekoko-advanced-results__empty"><?php echo esc_html( $empty ); ?></p>
	<?php endif; $html = ob_get_clean(); return array( 'html' => $html, 'count' => (int) $query->found_posts );
}

function nekoko_core_advanced_search_ajax() {
	$params = nekoko_core_advanced_search_params( $_GET ); $empty = sanitize_text_field( wp_unslash( $_GET['empty_message'] ?? '' ) ) ?: 'Nema oglasa koji odgovaraju izabranim filterima.'; $result = nekoko_core_advanced_search_results_html( $params, $empty ); wp_send_json_success( $result );
}
add_action( 'wp_ajax_nekoko_advanced_search', 'nekoko_core_advanced_search_ajax' );
add_action( 'wp_ajax_nopriv_nekoko_advanced_search', 'nekoko_core_advanced_search_ajax' );
