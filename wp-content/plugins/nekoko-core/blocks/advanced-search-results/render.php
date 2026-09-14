<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$sidebar_title = get_field( 'advanced_results_sidebar_title' ) ?: 'Filteri';
$title = get_field( 'advanced_results_title' ) ?: 'Rezultati pretrage';
$empty = get_field( 'advanced_results_empty' ) ?: 'Nema oglasa koji odgovaraju izabranim filterima.';
$clear = get_field( 'advanced_results_clear' ) ?: 'Obriši filtere';
$params = nekoko_core_advanced_search_params( $_GET );
$result = nekoko_core_advanced_search_results_html( $params, $empty );
$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false ) );
$id = 'nekoko-advanced-results-' . sanitize_html_class( $block['id'] ?? wp_unique_id() );
?>
<section class="nekoko-advanced-results" data-nekoko-advanced-search data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-empty-message="<?php echo esc_attr( $empty ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-title">
	<div class="nekoko-advanced-results__inner">
		<aside class="nekoko-advanced-results__sidebar">
			<form class="nekoko-advanced-results__form" data-nekoko-advanced-form>
				<div class="nekoko-advanced-results__filter-heading"><h2><?php echo esc_html( $sidebar_title ); ?></h2><button type="reset"><?php echo esc_html( $clear ); ?></button></div>
				<label>Ključna reč<input type="search" name="q" value="<?php echo esc_attr( $params['q'] ); ?>" placeholder="Šta tražite?"></label>
				<fieldset class="nekoko-advanced-results__type"><legend>Tip oglasa</legend><div><label><input type="radio" name="tip" value="ponuda" data-nekoko-advanced-type<?php checked( 'ponuda', $params['tip'] ); ?>> Ponuda</label><label><input type="radio" name="tip" value="potraznja" data-nekoko-advanced-type<?php checked( 'potraznja', $params['tip'] ); ?>> Potražnja</label></div></fieldset>
				<label>Kategorija<select name="kategorija" data-nekoko-advanced-category><option value="">Sve kategorije</option><?php if ( ! is_wp_error( $terms ) ) { foreach ( $terms as $term ) { if ( ! $term->parent ) { continue; } $root = nekoko_core_category_root( $term->term_id ); if ( ! $root ) { continue; } printf( '<option value="%1$d" data-tip="%2$s"%3$s>%4$s</option>', $term->term_id, esc_attr( $root->slug ), selected( $params['kategorija'], $term->term_id, false ), esc_html( $term->name ) ); } } ?></select></label>
				<label>Grad<input type="search" name="grad" value="<?php echo esc_attr( $params['grad'] ); ?>" placeholder="Npr. Beograd"><span class="nekoko-advanced-results__filter-help">Možete uneti samo deo naziva, npr. „novi“.</span></label>
				<fieldset data-nekoko-advanced-price<?php echo 'potraznja' === $params['tip'] ? ' hidden' : ''; ?>><legend>Cena (RSD)</legend><div><input type="number" min="0" name="cena_od" value="<?php echo esc_attr( $params['cena_od'] ?: '' ); ?>" placeholder="Od"><input type="number" min="0" name="cena_do" value="<?php echo esc_attr( $params['cena_do'] ?: '' ); ?>" placeholder="Do"></div></fieldset>
				<label data-nekoko-advanced-duration<?php echo 'potraznja' === $params['tip'] ? '' : ' hidden'; ?>>Trajanje<input type="search" name="trajanje" value="<?php echo esc_attr( $params['trajanje'] ); ?>" placeholder="Npr. 2 sata ili jedan dan"></label>
				<label>Sortiraj<select name="sort"><option value="novo"<?php selected( $params['sort'], 'novo' ); ?>>Najnovije</option><option value="cena_rastuce"<?php selected( $params['sort'], 'cena_rastuce' ); ?>>Cena: rastuće</option><option value="cena_opadajuce"<?php selected( $params['sort'], 'cena_opadajuce' ); ?>>Cena: opadajuće</option></select></label>
			</form>
		</aside>
		<div class="nekoko-advanced-results__content"><div class="nekoko-advanced-results__heading"><h2 id="<?php echo esc_attr( $id ); ?>-title"><?php echo esc_html( $title ); ?></h2><p data-nekoko-advanced-count><?php echo esc_html( sprintf( '%d oglasa', $result['count'] ) ); ?></p></div><div class="nekoko-advanced-results__grid" data-nekoko-advanced-target><?php echo $result['html']; ?></div></div>
	</div>
</section>
