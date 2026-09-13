<?php
/** Archive template. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main"><div class="site-content"><section class="content-area">
	<header class="page-header"><?php the_archive_title( '<h1 class="page-title">', '</h1>' ); the_archive_description( '<div class="page-description">', '</div>' ); ?></header>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', get_post_type() ); endwhile; the_posts_pagination(); endif; ?>
</section><?php get_sidebar(); ?></div></main>
<?php get_footer(); ?>
