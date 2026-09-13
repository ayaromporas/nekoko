<?php
/** @var array $block */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$title = get_field( 'hero_title' ) ?: 'Neko traži. Neko zna. NekoKO ih povezuje.';
$text = get_field( 'hero_text' ) ?: 'NekoKo povezuje ljude kojima je potrebna pomoć sa onima koji imaju znanje i veštine da je pruže. Pronađite pravu osobu za posao ili ponudite ono što umete i zaradite dodatno — brzo, jednostavno, na jednom mestu.';
$image_id = (int) get_field( 'hero_image' );

// Preserve CTA values saved under the former grouped fields until the block is
// opened and saved with the new, direct controls.
$legacy_primary = get_field( 'hero_primary_cta' ) ?: array();
$legacy_secondary = get_field( 'hero_secondary_cta' ) ?: array();
$legacy_data = isset( $block['data'] ) && is_array( $block['data'] ) ? $block['data'] : array();
$legacy_primary_link = $legacy_primary['link'] ?? ( $legacy_data['hero_primary_cta_link'] ?? '' );
$legacy_secondary_link = $legacy_secondary['link'] ?? ( $legacy_data['hero_secondary_cta_link'] ?? '' );
$legacy_primary_url = is_array( $legacy_primary_link ) ? ( $legacy_primary_link['url'] ?? '' ) : $legacy_primary_link;
$legacy_secondary_url = is_array( $legacy_secondary_link ) ? ( $legacy_secondary_link['url'] ?? '' ) : $legacy_secondary_link;

$primary_label = get_field( 'hero_primary_label' ) ?: ( $legacy_primary['label'] ?? ( $legacy_data['hero_primary_cta_label'] ?? 'Pretražite oglase' ) );
$primary_url = get_field( 'hero_primary_link' ) ?: ( $legacy_primary_url ?: home_url( '/napredna-pretraga/' ) );
$secondary_label = get_field( 'hero_secondary_label' ) ?: ( $legacy_secondary['label'] ?? ( $legacy_data['hero_secondary_cta_label'] ?? 'Kako funkcioniše' ) );
$secondary_url = get_field( 'hero_secondary_link' ) ?: ( $legacy_secondary_url ?: home_url( '/#kako-funkcionise' ) );
$fallback_image = get_stylesheet_directory_uri() . '/assets/images/home-hero-collage.png';
?>
<section class="nekoko-home-hero">
	<div class="nekoko-home-hero__inner nekoko-grid">
		<div class="nekoko-home-hero__content">
			<h1 class="nekoko-home-hero__title"><?php echo esc_html( $title ); ?></h1>
			<p class="nekoko-home-hero__text"><?php echo esc_html( $text ); ?></p>
			<div class="nekoko-home-hero__actions">
				<a class="button button--secondary" href="<?php echo esc_url( $primary_url ); ?>"><?php echo esc_html( $primary_label ); ?></a>
				<a class="button nekoko-home-hero__secondary-action" href="<?php echo esc_url( $secondary_url ); ?>"><?php echo esc_html( $secondary_label ); ?></a>
			</div>
		</div>
		<div class="nekoko-home-hero__visual">
			<?php if ( $image_id ) : ?>
				<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'nekoko-home-hero__image', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?>
			<?php else : ?>
				<img class="nekoko-home-hero__image" src="<?php echo esc_url( $fallback_image ); ?>" alt="<?php esc_attr_e( 'Kolaž ljudi sa različitim veštinama', 'nekoko-core' ); ?>" loading="eager" fetchpriority="high">
			<?php endif; ?>
		</div>
	</div>
</section>
