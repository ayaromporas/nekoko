<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$label = get_field( 'advanced_search_hero_label' ) ?: 'Napredna pretraga';
$title = get_field( 'advanced_search_hero_title' ) ?: 'Pronađite tačno ono što vam treba';
$text = get_field( 'advanced_search_hero_text' ) ?: 'Pretražujte ponude i potražnje po ključnim rečima, tipu oglasa, kategoriji, lokaciji i drugim filterima. Brzo i jednostavno dođite do prave usluge ili osobe koja može da vam pomogne.';
$image_id = absint( get_field( 'advanced_search_hero_image' ) );
$background = sanitize_hex_color( get_field( 'advanced_search_hero_background' ) ) ?: '#fffaf0';
$id = 'nekoko-advanced-search-hero-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-advanced-search-hero" style="--nekoko-advanced-search-background:<?php echo esc_attr( $background ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-title"><div class="nekoko-advanced-search-hero__inner"><div class="nekoko-advanced-search-hero__intro"><p class="nekoko-advanced-search-hero__pill"><?php echo esc_html( $label ); ?></p><h1 id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $title ); ?></h1><p><?php echo esc_html( $text ); ?></p></div><div class="nekoko-advanced-search-hero__visual"><?php if ( $image_id ) { echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'nekoko-advanced-search-hero__image', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); } else { ?><img class="nekoko-advanced-search-hero__image" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/advanced-search-hero-collage.png' ); ?>" alt="Kolaž različitih usluga" loading="eager" fetchpriority="high"><?php } ?></div></div></section>
