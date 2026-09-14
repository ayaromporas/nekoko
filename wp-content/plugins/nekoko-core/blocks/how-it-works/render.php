<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$title = get_field( 'how_title' ) ?: 'Kako funkcioniše';
$lead = get_field( 'how_lead' ) ?: 'Pronađite ono što vam treba ili ponudite ono što znate i umete.';
$text = get_field( 'how_text' ) ?: 'NekoKO povezuje ljude koji nude i traže specifične usluge, pomoć i neobične poslove. Ceo proces može da stane u četiri jednostavna koraka.';
$background = sanitize_hex_color( get_field( 'how_background' ) ) ?: '#eaf4ff';
$steps = get_field( 'how_steps' ) ?: array(
	array( 'fallback_icon' => 'search', 'title' => 'Pretražite oglase', 'text' => 'Proverite da li neko već nudi ono što vam treba ili traži baš ono što vi možete da ponudite.', 'link' => array( 'url' => home_url( '/napredna-pretraga/' ) ) ),
	array( 'fallback_icon' => 'account', 'title' => 'Napravite nalog', 'text' => 'Registrujte se kako biste mogli da objavljujete oglase i upravljate svojim ponudama i potražnjama.', 'link' => array( 'url' => nekoko_core_page_url( 'prijava' ) ) ),
	array( 'fallback_icon' => 'post', 'title' => 'Postavite oglas', 'text' => 'Ako niste pronašli odgovarajući oglas, objavite svoju ponudu ili potražnju i jasno navedite šta nudite ili šta vam je potrebno.', 'link' => array( 'url' => nekoko_core_page_url( 'postavite-oglas' ) ) ),
	array( 'fallback_icon' => 'agreement', 'title' => 'Povežite se i dogovorite saradnju', 'text' => 'Stupite u kontakt, dogovorite detalje i ostvarite saradnju — bilo da želite da zaradite pružanjem usluge ili rešite konkretan problem.', 'link' => array( 'url' => home_url( '/dogovorite-se/' ) ) ),
);
$anchor = sanitize_title( $block['anchor'] ?? 'kako-funkcionise' ) ?: 'kako-funkcionise';
?>
<section id="<?php echo esc_attr( $anchor ); ?>" class="nekoko-how-it-works" style="--nekoko-how-background:<?php echo esc_attr( $background ); ?>" aria-labelledby="<?php echo esc_attr( $anchor ); ?>-title">
	<div class="nekoko-how-it-works__inner"><header class="nekoko-how-it-works__header"><h2 id="<?php echo esc_attr( $anchor ); ?>-title"><?php echo esc_html( $title ); ?></h2><p class="nekoko-how-it-works__lead"><strong><?php echo esc_html( $lead ); ?></strong></p><p><?php echo esc_html( $text ); ?></p></header><ol class="nekoko-how-it-works__steps">
		<?php foreach ( $steps as $index => $step ) : $link = is_array( $step['link'] ?? null ) ? $step['link'] : array(); $url = ! empty( $link['url'] ) ? $link['url'] : ( $steps[ $index ]['link']['url'] ?? home_url( '/' ) ); $target = ! empty( $link['target'] ) ? $link['target'] : ''; $image_id = absint( $step['image'] ?? 0 ); ?><li class="nekoko-how-it-works__step"><a class="nekoko-how-it-works__step-link" href="<?php echo esc_url( $url ); ?>"<?php echo $target ? ' target="' . esc_attr( $target ) . '" rel="noopener noreferrer"' : ''; ?>><span class="nekoko-how-it-works__icon"><?php if ( $image_id ) { echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'alt' => '', 'class' => 'nekoko-how-it-works__icon-image' ) ); } else { echo nekoko_core_how_it_works_icon( sanitize_key( $step['fallback_icon'] ?? array( 'search', 'account', 'post', 'agreement' )[ $index ] ?? 'search' ) ); } ?></span><h3><?php echo esc_html( $step['title'] ?? '' ); ?></h3><p><?php echo esc_html( $step['text'] ?? '' ); ?></p></a></li><?php endforeach; ?>
	</ol></div>
</section>
