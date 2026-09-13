<?php
/** Main template file. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main"><div class="site-content"><section class="content-area">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', get_post_type() ); endwhile; the_posts_pagination(); else : ?>
		<section class="not-found"><h1><?php esc_html_e( 'Nothing found', 'nekoko' ); ?></h1><p><?php esc_html_e( 'Try searching for what you need.', 'nekoko' ); ?></p><?php get_search_form(); ?></section>
	<?php endif; ?>
</section><?php get_sidebar(); ?></div></main>
<?php get_footer(); ?>
