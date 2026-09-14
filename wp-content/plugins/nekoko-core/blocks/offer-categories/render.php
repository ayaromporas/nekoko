<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$title = get_field( 'offer_categories_title' ) ?: 'Sve kategorije u ponudi';
$text = get_field( 'offer_categories_text' ) ?: 'Istražite glavne kategorije u ponudi i odaberite oblast koja vas zanima.';
$background = sanitize_hex_color( get_field( 'offer_categories_background' ) ) ?: '#f5f2eb';
$cards = nekoko_core_offer_category_cards();
$block_id = 'nekoko-offer-categories-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-offer-categories" style="--nekoko-offer-categories-background:<?php echo esc_attr( $background ); ?>" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-offer-categories__inner">
		<header class="nekoko-offer-categories__header"><h2 id="<?php echo esc_attr( $block_id ); ?>-title"><?php echo esc_html( $title ); ?></h2><p><?php echo esc_html( $text ); ?></p></header>
		<div class="nekoko-offer-categories__grid">
			<?php foreach ( $cards as $card ) :
				$url = get_term_link( $card['term'] );
				if ( is_wp_error( $url ) ) { continue; }
			?>
				<article class="nekoko-offer-categories__card">
					<a class="nekoko-offer-categories__card-overlay" href="<?php echo esc_url( $url ); ?>" aria-label="<?php echo esc_attr( sprintf( 'Pogledaj oglase u kategoriji %s', $card['term']->name ) ); ?>"></a>
					<span class="nekoko-offer-categories__icon"><?php echo nekoko_core_top_offer_icon( $card['icon'] ); ?></span>
					<h3><?php echo esc_html( $card['term']->name ); ?></h3>
					<?php if ( $card['children'] ) : ?><div class="nekoko-offer-categories__pills"><?php foreach ( $card['children'] as $child ) : $child_url = get_term_link( $child ); if ( is_wp_error( $child_url ) ) { continue; } ?><a href="<?php echo esc_url( $child_url ); ?>"><?php echo esc_html( $child->name ); ?></a><?php endforeach; ?></div><?php endif; ?>
					<span class="nekoko-offer-categories__link">Pogledaj oglase <span aria-hidden="true">&rarr;</span></span>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
