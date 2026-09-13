<?php
/** Template part for displaying a post. @package Nekoko */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>>
	<?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'large' ); } ?>
	<header class="entry-header">
		<?php the_title( is_singular() ? '<h1 class="entry-title">' : '<h2 class="entry-title"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', is_singular() ? '</h1>' : '</a></h2>' ); ?>
		<?php if ( 'post' === get_post_type() ) : ?><p class="entry-meta"><?php echo esc_html( get_the_date() ); ?></p><?php endif; ?>
	</header>
	<div class="entry-content"><?php is_singular() ? the_content() : the_excerpt(); ?></div>
</article>
