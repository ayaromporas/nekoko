<?php
/** Single listing. @package Nekoko */
get_header();

$nekoko_icon = static function( $name ) {
	$icons = array(
		'heart' => '<path d="M20.8 8.5c0 5.4-8.8 10.3-8.8 10.3S3.2 13.9 3.2 8.5A4.8 4.8 0 0 1 12 5.8a4.8 4.8 0 0 1 8.8 2.7Z"/>',
		'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'phone' => '<path d="M7 3h3l1.5 4-2 1.4a15.5 15.5 0 0 0 6 6l1.4-2L21 14v3a2 2 0 0 1-2.2 2A17 17 0 0 1 5 5.2 2 2 0 0 1 7 3Z"/>',
		'pin' => '<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>',
		'tag' => '<path d="M20 13 13 20 3 10V3h7l10 10Z"/><circle cx="7.5" cy="7.5" r="1"/>',
		'grid' => '<rect x="4" y="4" width="6" height="6" rx="1"/><rect x="14" y="4" width="6" height="6" rx="1"/><rect x="4" y="14" width="6" height="6" rx="1"/><rect x="14" y="14" width="6" height="6" rx="1"/>',
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
		'share' => '<circle cx="18" cy="5" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="18" cy="19" r="2"/><path d="m8 11 8-5M8 13l8 5"/>',
		'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9" r="1.5"/><path d="m3 17 5-5 4 4 3-3 6 6"/>',
	);
	return '<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . ( $icons[ $name ] ?? '' ) . '</svg>';
};
?>
<main id="primary" class="site-main site-main--listing">
	<?php while ( have_posts() ) : the_post();
		$listing_id = get_the_ID();
		$image_ids = function_exists( 'nekoko_core_listing_image_ids' ) ? nekoko_core_listing_image_ids( $listing_id ) : array_filter( array( get_post_thumbnail_id( $listing_id ) ) );
		$classification = function_exists( 'nekoko_core_oglas_classification' ) ? nekoko_core_oglas_classification( $listing_id ) : false;
		$root = is_array( $classification ) ? $classification['root'] : false;
		$leaf_terms = function_exists( 'nekoko_core_oglas_categories' ) ? nekoko_core_oglas_categories( $listing_id ) : array();
		$direct_categories = array();
		foreach ( $leaf_terms as $term ) {
			$direct = $term;
			if ( $root && (int) $term->parent !== (int) $root->term_id ) {
				foreach ( array_reverse( get_ancestors( $term->term_id, 'kategorija_oglasa', 'taxonomy' ) ) as $ancestor_id ) {
					$ancestor = get_term( $ancestor_id, 'kategorija_oglasa' );
					if ( $ancestor && ! is_wp_error( $ancestor ) && (int) $ancestor->parent === (int) $root->term_id ) { $direct = $ancestor; break; }
				}
			}
			$direct_categories[ $direct->term_id ] = $direct;
		}
		$author_id = (int) get_post_field( 'post_author', $listing_id );
		$author = get_userdata( $author_id );
		$author_name = trim( get_user_meta( $author_id, 'first_name', true ) . ' ' . get_user_meta( $author_id, 'last_name', true ) );
		$author_name = $author_name ?: ( $author ? $author->display_name : 'NekoKO korisnik' );
		$author_image = absint( get_user_meta( $author_id, 'nekoko_profile_image', true ) );
		$phone = (string) get_user_meta( $author_id, 'nekoko_phone', true );
		$location = (string) get_post_meta( $listing_id, 'nekoko_listing_location', true );
		$duration = (string) get_post_meta( $listing_id, 'nekoko_listing_duration', true );
		$duration_na = '1' === get_post_meta( $listing_id, 'nekoko_listing_duration_na', true );
		$agreed = '1' === get_post_meta( $listing_id, 'nekoko_listing_price_agreed', true );
		$price = get_post_meta( $listing_id, 'nekoko_listing_price', true );
		$is_demand = $root && 'potraznja' === $root->slug;
		$price_label = $is_demand ? ( $duration_na ? 'Trajanje nije primenljivo' : ( $duration ? 'Trajanje: ' . $duration : 'Po dogovoru' ) ) : ( $agreed || '' === $price ? 'Po dogovoru' : number_format_i18n( (float) $price, 0 ) . ' RSD' );
		$excerpt = function_exists( 'nekoko_core_listing_excerpt' ) ? nekoko_core_listing_excerpt( $listing_id, 26 ) : get_the_excerpt();
		$saved_ids = function_exists( 'nekoko_core_saved_listing_ids' ) && is_user_logged_in() ? nekoko_core_saved_listing_ids() : array();
		$is_saved = in_array( $listing_id, $saved_ids, true );
		$tags = get_the_terms( $listing_id, 'oglas_tag' );
		$similar_args = array( 'post_type' => 'oglas', 'post_status' => 'publish', 'posts_per_page' => 3, 'post__not_in' => array( $listing_id ), 'no_found_rows' => true );
		if ( $root ) { $similar_args['tax_query'] = array( array( 'taxonomy' => 'kategorija_oglasa', 'field' => 'term_id', 'terms' => array( $root->term_id ), 'include_children' => true ) ); }
		$similar = new WP_Query( $similar_args );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'nekoko-listing-single' . ( $is_demand ? ' nekoko-listing-single--demand' : '' ) ); ?> data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
		<?php echo function_exists( 'nekoko_core_test_listing_badge' ) ? nekoko_core_test_listing_badge( $listing_id, 'single' ) : ''; ?>
		<nav class="nekoko-listing-single__breadcrumbs" aria-label="Putanja">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Početna</a><span aria-hidden="true">›</span>
			<?php if ( $root ) : ?><a href="<?php echo esc_url( get_term_link( $root ) ); ?>"><?php echo esc_html( $root->name ); ?></a><span aria-hidden="true">›</span><?php endif; ?>
			<?php $last_category = end( $direct_categories ); if ( $last_category ) : ?><span><?php echo esc_html( $last_category->name ); ?></span><span aria-hidden="true">›</span><?php endif; ?>
			<span aria-current="page"><?php the_title(); ?></span>
		</nav>

		<header class="nekoko-listing-single__header">
			<div>
				<h1><?php the_title(); ?></h1>
				<div class="nekoko-listing-single__labels">
					<?php if ( $root ) : ?><a class="nekoko-listing-single__label nekoko-listing-single__label--<?php echo esc_attr( $root->slug ); ?>" href="<?php echo esc_url( get_term_link( $root ) ); ?>"><?php echo esc_html( $root->name ); ?></a><?php endif; ?>
					<?php foreach ( $direct_categories as $category ) : ?><a class="nekoko-listing-single__label nekoko-listing-single__label--category" href="<?php echo esc_url( get_term_link( $category ) ); ?>"><?php echo esc_html( $category->name ); ?></a><?php endforeach; ?>
					<?php if ( $location ) : ?><span class="nekoko-listing-single__location"><?php echo $nekoko_icon( 'pin' ); ?><?php echo esc_html( $location ); ?></span><?php endif; ?>
				</div>
			</div>
			<div class="nekoko-listing-single__quick-actions">
				<button class="nekoko-listing-single__quick-button" type="button" data-listing-save aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"><?php echo $nekoko_icon( 'heart' ); ?><span data-listing-save-label><?php echo $is_saved ? 'Sačuvano' : 'Sačuvaj'; ?></span></button>
				<button class="nekoko-listing-single__quick-button" type="button" data-listing-share><?php echo $nekoko_icon( 'share' ); ?><span>Podeli oglas</span></button>
			</div>
		</header>

		<div class="nekoko-listing-single__layout">
			<div class="nekoko-listing-single__main">
				<section class="nekoko-listing-gallery" data-listing-gallery>
					<div class="nekoko-listing-gallery__main">
						<?php if ( $image_ids ) : $first_image = $image_ids[0]; ?>
							<?php echo wp_get_attachment_image( $first_image, 'large', false, array( 'data-listing-main-image' => '', 'class' => 'nekoko-listing-gallery__image', 'alt' => get_post_meta( $first_image, '_wp_attachment_image_alt', true ) ) ); ?>
							<span class="nekoko-listing-gallery__count"><?php echo $nekoko_icon( 'image' ); ?><span data-listing-image-count>1 / <?php echo count( $image_ids ); ?></span></span>
						<?php else : ?><div class="nekoko-listing-gallery__empty">Nema fotografije</div><?php endif; ?>
					</div>
					<?php if ( count( $image_ids ) > 1 ) : ?><div class="nekoko-listing-gallery__thumbs" aria-label="Fotografije oglasa">
						<?php foreach ( $image_ids as $index => $image_id ) : $full = wp_get_attachment_image_url( $image_id, 'large' ); $alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true ); ?>
							<button class="nekoko-listing-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" type="button" data-listing-thumb data-image-src="<?php echo esc_url( $full ); ?>" data-image-alt="<?php echo esc_attr( $alt ); ?>" data-image-position="<?php echo esc_attr( $index + 1 ); ?>" aria-label="Prikaži fotografiju <?php echo esc_attr( $index + 1 ); ?>"><?php echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'alt' => '' ) ); ?></button>
						<?php endforeach; ?>
					</div><?php endif; ?>
				</section>

				<section class="nekoko-listing-single__description">
					<h2>Opis oglasa</h2>
					<div class="nekoko-listing-single__content"><?php the_content(); ?></div>
					<?php if ( $tags && ! is_wp_error( $tags ) ) : ?><div class="nekoko-listing-single__tags"><h3>Tagovi</h3><div><?php foreach ( $tags as $tag ) : ?><a href="<?php echo esc_url( get_term_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a><?php endforeach; ?></div></div><?php endif; ?>
				</section>
			</div>

			<aside class="nekoko-listing-single__sidebar" aria-label="Informacije o oglasu">
				<section class="nekoko-listing-sidecard nekoko-listing-sidecard--contact">
					<p class="nekoko-listing-sidecard__eyebrow"><?php echo $is_demand ? 'Trajanje' : 'Cena'; ?></p>
					<p class="nekoko-listing-sidecard__price"><?php echo esc_html( $price_label ); ?></p>
					<?php if ( $excerpt ) : ?><p class="nekoko-listing-sidecard__excerpt"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
					<button class="nekoko-listing-sidecard__message" type="button" data-listing-open-dialog="message"><?php echo $nekoko_icon( 'mail' ); ?>Pošalji poruku</button>
					<div class="nekoko-listing-sidecard__actions"><button type="button" data-listing-open-dialog="phone"><?php echo $nekoko_icon( 'phone' ); ?>Pozovi</button><button type="button" data-listing-save aria-pressed="<?php echo $is_saved ? 'true' : 'false'; ?>"><?php echo $nekoko_icon( 'heart' ); ?><span data-listing-save-label><?php echo $is_saved ? 'Sačuvano' : 'Sačuvaj oglas'; ?></span></button></div>
				</section>

				<section class="nekoko-listing-sidecard nekoko-listing-author-card">
					<p class="nekoko-listing-sidecard__title">Oglas postavio</p>
					<a class="nekoko-listing-author-card__person" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">
						<?php if ( $author_image ) { echo wp_get_attachment_image( $author_image, 'thumbnail', false, array( 'alt' => '' ) ); } else { echo get_avatar( $author_id, 96, '', '', array( 'alt' => '' ) ); } ?>
						<span><strong><?php echo esc_html( $author_name ); ?></strong><small>Verifikovan korisnik</small><small>Član od <?php echo esc_html( date_i18n( 'Y.', strtotime( $author->user_registered ?? '' ) ) ); ?></small></span>
					</a>
					<a class="nekoko-listing-author-card__all" href="<?php echo esc_url( get_author_posts_url( $author_id ) ); ?>">Svi oglasi ovog korisnika</a>
				</section>

				<section class="nekoko-listing-sidecard nekoko-listing-details">
					<h2>Detalji oglasa</h2>
					<dl>
						<?php if ( $location ) : ?><div><dt><?php echo $nekoko_icon( 'pin' ); ?>Lokacija</dt><dd><?php echo esc_html( $location ); ?></dd></div><?php endif; ?>
						<?php if ( $root ) : ?><div><dt><?php echo $nekoko_icon( 'tag' ); ?>Tip oglasa</dt><dd><?php echo esc_html( $root->name ); ?></dd></div><?php endif; ?>
						<?php if ( $leaf_terms ) : ?><div><dt><?php echo $nekoko_icon( 'grid' ); ?>Kategorija</dt><dd><?php echo esc_html( implode( ' · ', wp_list_pluck( $leaf_terms, 'name' ) ) ); ?></dd></div><?php endif; ?>
						<div><dt><?php echo $nekoko_icon( 'calendar' ); ?>Objavljeno</dt><dd><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date( 'j. F Y.' ) ); ?></time></dd></div>
					</dl>
				</section>
			</aside>
		</div>

		<?php if ( $similar->have_posts() ) : ?><section class="nekoko-listing-similar"><div class="nekoko-listing-similar__heading"><h2>Slični oglasi</h2><?php if ( $root ) : ?><a href="<?php echo esc_url( get_term_link( $root ) ); ?>">Pogledaj sve oglase <span aria-hidden="true">→</span></a><?php endif; ?></div><div class="nekoko-listing-similar__grid">
			<?php while ( $similar->have_posts() ) : $similar->the_post(); $similar_id = get_the_ID(); $similar_root = nekoko_core_oglas_classification( $similar_id ); $similar_price = get_post_meta( $similar_id, 'nekoko_listing_price', true ); $similar_agreed = '1' === get_post_meta( $similar_id, 'nekoko_listing_price_agreed', true ); ?>
				<article class="nekoko-listing-similar__card"><a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>"></a><div class="nekoko-listing-similar__image"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large' ); } ?></div><div class="nekoko-listing-similar__body"><span class="nekoko-listing-similar__type"><?php echo esc_html( is_array( $similar_root ) ? $similar_root['root']->name : 'Oglas' ); ?></span><h3><?php the_title(); ?></h3><p><?php echo $nekoko_icon( 'pin' ); ?><?php echo esc_html( get_post_meta( $similar_id, 'nekoko_listing_location', true ) ?: 'Lokacija nije navedena' ); ?></p><strong><?php echo esc_html( $similar_agreed || '' === $similar_price ? 'Po dogovoru' : number_format_i18n( (float) $similar_price, 0 ) . ' RSD' ); ?></strong></div></article>
			<?php endwhile; wp_reset_postdata(); ?></div></section><?php endif; ?>
	</article>

	<dialog class="nekoko-listing-dialog" data-listing-dialog="message" aria-labelledby="listing-message-title"><form method="dialog" class="nekoko-listing-dialog__box" data-listing-message-form><button class="nekoko-listing-dialog__close" type="button" data-dialog-close aria-label="Zatvori">×</button><h2 id="listing-message-title">Pošaljite poruku</h2><p>Poruka će biti poslata autoru oglasa, uz vaše ime i email adresu.</p><label>Poruka<textarea name="message" required minlength="5" maxlength="2000" placeholder="Napišite poruku…"></textarea></label><p class="nekoko-listing-dialog__status" data-listing-message-status aria-live="polite"></p><button type="submit">Pošalji poruku</button></form></dialog>
	<dialog class="nekoko-listing-dialog" data-listing-dialog="phone" aria-labelledby="listing-phone-title"><form method="dialog" class="nekoko-listing-dialog__box"><button class="nekoko-listing-dialog__close" type="button" data-dialog-close aria-label="Zatvori">×</button><h2 id="listing-phone-title">Pozovite oglašivača</h2><?php if ( $phone ) : ?><p class="nekoko-listing-dialog__phone"><a href="tel:<?php echo esc_attr( preg_replace( '/[^+0-9]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p><?php else : ?><p>Autor oglasa trenutno nije ostavio broj telefona.</p><?php endif; ?></form></dialog>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
