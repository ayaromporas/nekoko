<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$title = get_field( 'offer_featured_category_title' ) ?: 'Pomoć i svakodnevne usluge';
$text = get_field( 'offer_featured_category_text' ) ?: 'Podrška u svakodnevnim obavezama kada vam je potrebna.';
$icon = get_field( 'offer_featured_category_icon' );
$icon_url = is_array( $icon ) && ! empty( $icon['url'] ) ? $icon['url'] : '';
$icon_alt = is_array( $icon ) && isset( $icon['alt'] ) ? $icon['alt'] : '';
$icon_color = sanitize_hex_color( get_field( 'offer_featured_category_icon_color' ) ) ?: '#004682';
$background = sanitize_hex_color( get_field( 'offer_featured_category_background' ) ) ?: '#ffffff';
$pills = get_field( 'offer_featured_category_pills' ) ?: array();
$link_label = get_field( 'offer_featured_category_link_label' ) ?: 'Pogledaj oglase';
$link = get_field( 'offer_featured_category_link' );
$url = is_array( $link ) && ! empty( $link['url'] ) ? $link['url'] : nekoko_core_page_url( 'napredna-pretraga' );
$target = is_array( $link ) && ! empty( $link['target'] ) ? $link['target'] : '';
$block_id = 'nekoko-offer-featured-category-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-offer-featured-category" style="--nekoko-featured-category-background:<?php echo esc_attr( $background ); ?>;--nekoko-featured-category-icon:<?php echo esc_attr( $icon_color ); ?>" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-offer-featured-category__inner"><article class="nekoko-offer-featured-category__card"><span class="nekoko-offer-featured-category__icon"><?php if ( $icon_url ) : ?><img src="<?php echo esc_url( $icon_url ); ?>" alt="<?php echo esc_attr( $icon_alt ); ?>" /><?php else : ?><?php echo nekoko_core_handshake_icon(); ?><?php endif; ?></span><div class="nekoko-offer-featured-category__content"><h2 id="<?php echo esc_attr( $block_id ); ?>-title"><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $text ); ?></p><?php if ( $pills ) : ?><div class="nekoko-offer-featured-category__pills"><?php foreach ( $pills as $pill ) : $pill_link = $pill['link'] ?? array(); if ( empty( $pill['label'] ) || ! is_array( $pill_link ) || empty( $pill_link['url'] ) ) { continue; } ?><a href="<?php echo esc_url( $pill_link['url'] ); ?>"<?php echo ! empty( $pill_link['target'] ) ? ' target="' . esc_attr( $pill_link['target'] ) . '" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $pill['label'] ); ?></a><?php endforeach; ?></div><?php endif; ?></div><a class="nekoko-offer-featured-category__link" href="<?php echo esc_url( $url ); ?>"<?php echo $target ? ' target="' . esc_attr( $target ) . '" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $link_label ); ?> <span aria-hidden="true">→</span></a></article></div>
</section>
