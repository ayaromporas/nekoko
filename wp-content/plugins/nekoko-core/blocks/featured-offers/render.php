<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$label = get_field( 'featured_offers_label' ) ?: 'Ponuda';
$title = get_field( 'featured_offers_title' ) ?: 'Istaknite ono što nudite';
$text = get_field( 'featured_offers_text' ) ?: 'Od čuvanja egzotičnih ljubimaca do kreativnih radionica, NekoKO daje prostor uslugama koje su korisne, neobične i ljudima zaista trebaju.';
$cta_label = get_field( 'featured_offers_cta_label' ) ?: 'Objavite ponudu';
$cta_link = get_field( 'featured_offers_cta_link' );
$cta_url = is_array( $cta_link ) && ! empty( $cta_link['url'] ) ? $cta_link['url'] : nekoko_core_page_url( 'postavite-oglas' );
$cta_target = is_array( $cta_link ) && ! empty( $cta_link['target'] ) ? $cta_link['target'] : '';
$listings = nekoko_core_featured_offer_listings( get_field( 'featured_offers_listings' ) ?: array() );
$block_id = 'nekoko-featured-offers-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-featured-offers" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-featured-offers__inner">
		<div class="nekoko-featured-offers__intro">
			<p class="nekoko-featured-offers__pill"><?php echo esc_html( $label ); ?></p>
			<h2 id="<?php echo esc_attr( $block_id ); ?>-title" class="nekoko-featured-offers__title"><?php echo esc_html( $title ); ?></h2>
			<p class="nekoko-featured-offers__text"><?php echo esc_html( $text ); ?></p>
			<a class="nekoko-featured-offers__cta" href="<?php echo esc_url( $cta_url ); ?>"<?php echo $cta_target ? ' target="' . esc_attr( $cta_target ) . '" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $cta_label ); ?></a>
		</div>
		<div class="nekoko-featured-offers__grid">
			<?php foreach ( $listings as $listing ) : $categories = nekoko_core_oglas_categories( $listing->ID ); $category = $categories ? $categories[0]->name : 'Kategorija'; $location = get_post_meta( $listing->ID, 'nekoko_listing_location', true ); $agreed = '1' === get_post_meta( $listing->ID, 'nekoko_listing_price_agreed', true ); $price = get_post_meta( $listing->ID, 'nekoko_listing_price', true ); $price_label = $agreed || '' === $price ? 'Po dogovoru' : number_format_i18n( (float) $price, 0 ) . ' RSD'; ?>
				<article class="nekoko-featured-offers__card">
					<p class="nekoko-featured-offers__card-pill"><?php echo esc_html( $category ); ?></p>
					<h3 class="nekoko-featured-offers__card-title"><a href="<?php echo esc_url( get_permalink( $listing ) ); ?>"><?php echo esc_html( $listing->post_title ); ?></a></h3>
					<div class="nekoko-featured-offers__card-meta"><span><?php echo esc_html( $location ?: 'Lokacija nije navedena' ); ?></span><strong><?php echo esc_html( $price_label ); ?></strong></div>
				</article>
			<?php endforeach; ?>
			<?php if ( ! $listings ) : ?><p class="nekoko-featured-offers__empty">Trenutno nema objavljenih ponuda za prikaz.</p><?php endif; ?>
		</div>
	</div>
</section>
