<?php
/** Shared archive layout for listings and their taxonomies. @package Nekoko */
get_header();
$nekoko_term = get_queried_object();
$nekoko_is_term = $nekoko_term instanceof WP_Term;
$nekoko_title = $nekoko_is_term ? $nekoko_term->name : 'Oglasi';
if ( $nekoko_is_term && 'kategorija_oglasa' === $nekoko_term->taxonomy && function_exists( 'nekoko_core_category_path' ) ) {
	$nekoko_title = nekoko_core_category_path( $nekoko_term->term_id, true );
}
$nekoko_icon_id = $nekoko_is_term ? absint( get_term_meta( $nekoko_term->term_id, 'nekoko_icon_id', true ) ) : 0;
$nekoko_hero_id = $nekoko_is_term ? absint( get_term_meta( $nekoko_term->term_id, 'nekoko_hero_id', true ) ) : 0;
?>
<main id="primary" class="site-main">
	<div class="site-content site-content--full">
		<section class="content-area" aria-labelledby="oglas-archive-title">
			<header class="page-header">
				<?php if ( $nekoko_icon_id ) { echo wp_get_attachment_image( $nekoko_icon_id, array( 64, 64 ), false, array( 'alt' => '' ) ); } ?>
				<h1 id="oglas-archive-title" class="page-title"><?php echo esc_html( $nekoko_title ); ?></h1>
				<?php if ( $nekoko_hero_id ) { echo wp_get_attachment_image( $nekoko_hero_id, 'large' ); } ?>
				<?php if ( $nekoko_is_term ) : ?>
					<?php $nekoko_description = term_description( $nekoko_term->term_id, $nekoko_term->taxonomy ); ?>
					<?php if ( $nekoko_description ) : ?><div class="page-description"><?php echo wp_kses_post( $nekoko_description ); ?></div><?php endif; ?>
				<?php else : ?>
					<p class="page-description">Ponude i potražnje za neobičnim poslovima, praktičnom pomoći i kreativnim uslugama.</p>
				<?php endif; ?>
			</header>
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/oglas/card' ); endwhile; ?>
				<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => 'Prethodna strana', 'next_text' => 'Sledeća strana', 'screen_reader_text' => 'Navigacija kroz oglase', 'aria_label' => 'Strane oglasa' ) ); ?>
			<?php else : ?>
				<p>Trenutno nema objavljenih oglasa u ovoj arhivi.</p>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php get_footer(); ?>
