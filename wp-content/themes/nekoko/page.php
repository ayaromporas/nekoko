<?php
/** Page template. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main"><div class="site-content site-content--full"><section class="content-area">
	<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content', 'page' ); endwhile; ?>
</section></div></main>
<?php get_footer(); ?>
