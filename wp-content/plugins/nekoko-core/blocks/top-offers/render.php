<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$title = get_field( 'top_offers_title' ) ?: 'Top kategorije u ponudi';
$text = get_field( 'top_offers_text' ) ?: 'Izaberite jednu od najtraženijih kategorija i pogledajte dostupne ponude.';
$items = nekoko_core_top_offer_items( get_field( 'top_offers_items' ) ?: array() );
$block_id = 'nekoko-top-offers-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-top-offers" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-top-offers__inner">
		<header class="nekoko-top-offers__header"><h2 id="<?php echo esc_attr( $block_id ); ?>-title"><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $text ); ?></p></header>
		<div class="nekoko-top-offers__grid">
			<?php foreach ( $items as $item ) : $url = get_term_link( $item['term'] ); if ( is_wp_error( $url ) ) { continue; } ?>
				<a class="nekoko-top-offers__item" href="<?php echo esc_url( $url ); ?>"><span class="nekoko-top-offers__icon"><?php echo nekoko_core_top_offer_icon( $item['icon'] ); ?></span><span class="nekoko-top-offers__label"><?php echo esc_html( $item['term']->name ); ?></span></a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
