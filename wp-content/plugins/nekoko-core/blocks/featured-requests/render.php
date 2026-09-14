<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$label = get_field( 'featured_requests_label' ) ?: 'Potražnja';
$title = get_field( 'featured_requests_title' ) ?: 'Recite šta vam treba';
$text = get_field( 'featured_requests_text' ) ?: 'Objavite Potražnju kada vam je potrebna pomoć — neko da pričuva mačku, pomogne oko selidbe, fotografiše proslavu. Na jednom mestu pronalazite ljude spremne da pomognu.';
$cta_label = get_field( 'featured_requests_cta_label' ) ?: 'Objavite potražnju';
$cta_link = get_field( 'featured_requests_cta_link' );
$cta_url = is_array( $cta_link ) && ! empty( $cta_link['url'] ) ? $cta_link['url'] : nekoko_core_page_url( 'postavite-oglas' );
$cta_target = is_array( $cta_link ) && ! empty( $cta_link['target'] ) ? $cta_link['target'] : '';
$listings = nekoko_core_featured_request_listings( get_field( 'featured_requests_listings' ) ?: array() );
$block_id = 'nekoko-featured-requests-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-featured-offers nekoko-featured-requests" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-featured-offers__inner">
		<div class="nekoko-featured-offers__intro">
			<p class="nekoko-featured-offers__pill"><?php echo esc_html( $label ); ?></p>
			<h2 id="<?php echo esc_attr( $block_id ); ?>-title" class="nekoko-featured-offers__title"><?php echo esc_html( $title ); ?></h2>
			<p class="nekoko-featured-offers__text"><?php echo esc_html( $text ); ?></p>
			<a class="nekoko-featured-offers__cta" href="<?php echo esc_url( $cta_url ); ?>"<?php echo $cta_target ? ' target="' . esc_attr( $cta_target ) . '" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $cta_label ); ?></a>
		</div>
		<div class="nekoko-featured-offers__grid">
			<?php foreach ( $listings as $listing ) : $categories = nekoko_core_oglas_categories( $listing->ID ); $category = $categories ? $categories[0]->name : 'Kategorija'; $location = get_post_meta( $listing->ID, 'nekoko_listing_location', true ); $duration = get_post_meta( $listing->ID, 'nekoko_listing_duration', true ); $duration_na = '1' === get_post_meta( $listing->ID, 'nekoko_listing_duration_na', true ); ?>
				<article class="nekoko-featured-offers__card"><p class="nekoko-featured-offers__card-pill"><?php echo esc_html( $category ); ?></p><h3 class="nekoko-featured-offers__card-title"><a href="<?php echo esc_url( get_permalink( $listing ) ); ?>"><?php echo esc_html( $listing->post_title ); ?></a></h3><div class="nekoko-featured-offers__card-meta"><span><?php echo esc_html( $location ?: 'Lokacija nije navedena' ); ?></span><strong><?php echo esc_html( $duration_na ? 'Trajanje nije primenljivo' : ( $duration ? 'Trajanje ' . $duration : 'Trajanje po dogovoru' ) ); ?></strong></div></article>
			<?php endforeach; ?>
			<?php if ( ! $listings ) : ?><p class="nekoko-featured-offers__empty">Trenutno nema objavljenih potražnji za prikaz.</p><?php endif; ?>
		</div>
	</div>
</section>
