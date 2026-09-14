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

function nekoko_core_advanced_search_query( $params ) {
	$args = array( 'post_type' => 'oglas', 'post_status' => 'publish', 'posts_per_page' => 24, 's' => $params['q'], 'no_found_rows' => false );
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

function nekoko_core_advanced_search_results_html( $params, $empty ) {
	$query = nekoko_core_advanced_search_query( $params ); ob_start();
	if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); $id = get_the_ID(); $categories = nekoko_core_oglas_categories( $id ); $category = $categories ? $categories[0]->name : 'Kategorija'; $root = $categories ? nekoko_core_category_root( $categories[0]->term_id ) : false; $demand = $root && 'potraznja' === $root->slug; $pill_class = $demand ? ' nekoko-advanced-results__pill--demand' : ''; $location = get_post_meta( $id, 'nekoko_listing_location', true ); $duration = get_post_meta( $id, 'nekoko_listing_duration', true ); $duration_na = '1' === get_post_meta( $id, 'nekoko_listing_duration_na', true ); $agreed = '1' === get_post_meta( $id, 'nekoko_listing_price_agreed', true ); $price = get_post_meta( $id, 'nekoko_listing_price', true ); $currency = get_post_meta( $id, 'nekoko_listing_currency', true ) ?: 'RSD'; ?>
	<article class="nekoko-advanced-results__card"><a class="nekoko-advanced-results__card-link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( get_the_title() ); ?>"></a><div class="nekoko-advanced-results__image"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large' ); } else { ?><span aria-hidden="true">⌕</span><?php } ?></div><div class="nekoko-advanced-results__card-body"><p class="nekoko-advanced-results__pill<?php echo esc_attr( $pill_class ); ?>"><?php echo esc_html( $category ); ?></p><h3><?php the_title(); ?></h3><p class="nekoko-advanced-results__meta"><?php echo esc_html( $location ?: 'Lokacija nije navedena' ); ?></p><strong><?php echo esc_html( $demand ? ( $duration_na ? 'Trajanje nije primenljivo' : ( $duration ? 'Trajanje ' . $duration : 'Trajanje po dogovoru' ) ) : ( $agreed ? 'Po dogovoru' : ( '' !== $price ? number_format_i18n( (float) $price, 0 ) . ' ' . $currency : 'Cena na upit' ) ) ); ?></strong></div></article>
	<?php endwhile; wp_reset_postdata(); else : ?><p class="nekoko-advanced-results__empty"><?php echo esc_html( $empty ); ?></p><?php endif; $html = ob_get_clean(); return array( 'html' => $html, 'count' => (int) $query->found_posts );
}

function nekoko_core_advanced_search_ajax() {
	$params = nekoko_core_advanced_search_params( $_GET ); $empty = sanitize_text_field( wp_unslash( $_GET['empty_message'] ?? '' ) ) ?: 'Nema oglasa koji odgovaraju izabranim filterima.'; $result = nekoko_core_advanced_search_results_html( $params, $empty ); wp_send_json_success( $result );
}
add_action( 'wp_ajax_nekoko_advanced_search', 'nekoko_core_advanced_search_ajax' );
add_action( 'wp_ajax_nopriv_nekoko_advanced_search', 'nekoko_core_advanced_search_ajax' );
