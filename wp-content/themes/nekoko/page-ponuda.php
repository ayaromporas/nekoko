<?php
/** Offer page template. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main site-main--home">
	<?php while ( have_posts() ) : the_post(); ?>
		<div class="offer-page-content"><?php the_content(); ?></div>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
