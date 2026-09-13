<?php
/** Reusable listing card. @package Nekoko */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?> aria-labelledby="oglas-title-<?php the_ID(); ?>">
	<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
	<header class="entry-header">
		<h2 id="oglas-title-<?php the_ID(); ?>" class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
	</header>
	<div class="entry-content"><?php the_excerpt(); ?></div>
	<?php get_template_part( 'template-parts/oglas/meta' ); ?>
</article>
