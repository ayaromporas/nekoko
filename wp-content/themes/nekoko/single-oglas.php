<?php
/** Single listing. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main">
	<div class="site-content site-content--full">
		<div class="content-area">
			<?php while ( have_posts() ) : the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?> aria-labelledby="oglas-title-<?php the_ID(); ?>">
					<header class="entry-header"><h1 id="oglas-title-<?php the_ID(); ?>" class="entry-title"><?php the_title(); ?></h1></header>
					<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
					<?php get_template_part( 'template-parts/oglas/meta' ); ?>
					<div class="entry-content">
						<?php the_content(); ?>
						<?php wp_link_pages( array( 'before' => '<nav aria-label="Strane ovog oglasa"><p>Strane: ', 'after' => '</p></nav>', 'nextpagelink' => 'Sledeća', 'previouspagelink' => 'Prethodna' ) ); ?>
					</div>
					<footer class="entry-footer"><p><a href="<?php echo esc_url( get_post_type_archive_link( 'oglas' ) ); ?>">Svi oglasi</a></p></footer>
				</article>
			<?php endwhile; ?>
		</div>
	</div>
</main>
<?php get_footer(); ?>
