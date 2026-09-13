<?php
/** @var array $block */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$title = get_field( 'search_title' ) ?: 'Pretražite';
$text = get_field( 'search_text' ) ?: 'Pronađite neobičan posao, specifičnu pomoć ili osobu za poseban zadatak.';
$placeholder = get_field( 'search_placeholder' ) ?: 'Šta tražite?';
$submit_label = get_field( 'search_submit_label' ) ?: 'Pošalji';
$block_id = 'nekoko-search-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-search-block" aria-labelledby="<?php echo esc_attr( $block_id ); ?>-title">
	<div class="nekoko-search-block__inner">
		<div class="nekoko-search-block__intro">
			<h3 id="<?php echo esc_attr( $block_id ); ?>-title" class="nekoko-search-block__title"><?php echo esc_html( $title ); ?></h3>
			<p class="nekoko-search-block__text"><?php echo esc_html( $text ); ?></p>
		</div>
		<form class="nekoko-search-block__form" action="<?php echo esc_url( home_url( '/napredna-pretraga/' ) ); ?>" method="get" data-nekoko-search-form data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
			<div class="nekoko-search-block__row">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-query"><?php echo esc_html( $placeholder ); ?></label>
				<input id="<?php echo esc_attr( $block_id ); ?>-query" class="nekoko-search-block__query" type="search" name="q" placeholder="<?php echo esc_attr( $placeholder ); ?>">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-type">Tip oglasa</label>
				<select id="<?php echo esc_attr( $block_id ); ?>-type" class="nekoko-search-block__select" name="tip" data-nekoko-search-type required>
					<option value="">Tip oglasa</option>
					<option value="ponuda">Ponuda</option>
					<option value="potraznja">Potražnja</option>
				</select>
			</div>
			<div class="nekoko-search-block__row nekoko-search-block__row--bottom">
				<label class="screen-reader-text" for="<?php echo esc_attr( $block_id ); ?>-category">Kategorija</label>
				<select id="<?php echo esc_attr( $block_id ); ?>-category" class="nekoko-search-block__select nekoko-search-block__category" name="kategorija" data-nekoko-search-category disabled required>
					<option value="">Prvo izaberite tip oglasa</option>
				</select>
				<button class="nekoko-search-block__submit" type="submit"><?php echo esc_html( $submit_label ); ?></button>
			</div>
			<p class="nekoko-search-block__status" data-nekoko-search-status aria-live="polite"></p>
		</form>
	</div>
</section>
