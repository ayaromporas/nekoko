<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$title = get_field( 'offer_cta_banner_title' ) ?: 'Želite da ponudite svoju uslugu?';
$text = get_field( 'offer_cta_banner_text' ) ?: 'Pridružite se zajednici NekoKO i lako objavite svoju ponudu.';
$label = get_field( 'offer_cta_banner_button_label' ) ?: 'Objavite ponudu';
$link = get_field( 'offer_cta_banner_button_link' );
$url = is_array( $link ) && ! empty( $link['url'] ) ? $link['url'] : nekoko_core_page_url( 'postavite-oglas' );
$target = is_array( $link ) && ! empty( $link['target'] ) ? $link['target'] : '';
$note = get_field( 'offer_cta_banner_note' ) ?: 'Vaše veštine mogu nekome mnogo značiti';
$background = sanitize_hex_color( get_field( 'offer_cta_banner_background' ) ) ?: '#e4f2ff';
$block_id = 'nekoko-offer-cta-banner-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-offer-cta-banner" style="--nekoko-offer-cta-background:<?php echo esc_attr( $background ); ?>" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title"><div class="nekoko-offer-cta-banner__inner"><div class="nekoko-offer-cta-banner__content"><h2 id="<?php echo esc_attr( $block_id ); ?>-title"><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $text ); ?></p></div><a class="nekoko-offer-cta-banner__button" href="<?php echo esc_url( $url ); ?>"<?php echo $target ? ' target="' . esc_attr( $target ) . '" rel="noopener noreferrer"' : ''; ?>><?php echo esc_html( $label ); ?></a><aside class="nekoko-offer-cta-banner__note"><span class="nekoko-offer-cta-banner__rays" aria-hidden="true">☀</span><strong><?php echo nl2br( esc_html( $note ) ); ?></strong><span class="nekoko-offer-cta-banner__check" aria-hidden="true">✓</span></aside></div></section>
