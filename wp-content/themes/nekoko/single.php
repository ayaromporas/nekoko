<?php
/** Single post template. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main"><div class="site-content"><section class="content-area">
	<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', get_post_type() ); endwhile; ?>
</section><?php get_sidebar(); ?></div></main>
<?php get_footer(); ?>
