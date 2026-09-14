<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$label = get_field( 'offer_hero_label' ) ?: 'Kategorije ponude';
$title = get_field( 'offer_hero_title' ) ?: 'Ponuda';
$text = get_field( 'offer_hero_text' ) ?: 'Pregledajte glavne kategorije ponude i pronađite usluge, pomoć i specifične poslove koji odgovaraju onome što vam je potrebno.';
$placeholder = get_field( 'offer_hero_placeholder' ) ?: 'Šta tražite?';
$category_label = get_field( 'offer_hero_category_label' ) ?: 'Kategorija';
$submit_label = get_field( 'offer_hero_submit' ) ?: 'Pretraži';
$search_url = esc_url_raw( get_field( 'offer_hero_search_url' ) ) ?: home_url( '/napredna-pretraga/' );
$image_id = absint( get_field( 'offer_hero_image' ) );
$categories = nekoko_core_offer_hero_categories();
$block_id = 'nekoko-offer-hero-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-offer-hero" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-offer-hero__inner"><div class="nekoko-offer-hero__intro"><p class="nekoko-offer-hero__pill"><?php echo esc_html( $label ); ?></p><h1 id="<?php echo esc_attr( $block_id ); ?>-title"><?php echo esc_html( $title ); ?></h1><p><?php echo esc_html( $text ); ?></p></div><div class="nekoko-offer-hero__visual"><?php if ( $image_id ) { echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'nekoko-offer-hero__image', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); } else { ?><img class="nekoko-offer-hero__image" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/offer-hero-collage.png' ); ?>" alt="<?php esc_attr_e( 'Kolaž usluga koje se nude', 'nekoko-core' ); ?>" loading="eager" fetchpriority="high"><?php } ?></div><form class="nekoko-offer-hero__form" action="<?php echo esc_url( $search_url ); ?>" method="get"><label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-query"><?php echo esc_html( $placeholder ); ?></label><input id="<?php echo esc_attr( $block_id ); ?>-query" type="search" name="q" placeholder="<?php echo esc_attr( $placeholder ); ?>"><label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-category"><?php echo esc_html( $category_label ); ?></label><select id="<?php echo esc_attr( $block_id ); ?>-category" name="kategorija"><option value=""><?php echo esc_html( $category_label ); ?></option><?php foreach ( $categories as $item ) : ?><option value="<?php echo esc_attr( $item['term']->term_id ); ?>"><?php echo esc_html( str_repeat( '— ', $item['depth'] ) . $item['term']->name ); ?></option><?php endforeach; ?></select><input type="hidden" name="tip" value="ponuda"><button type="submit"><?php echo esc_html( $submit_label ); ?></button></form></div>
</section>
