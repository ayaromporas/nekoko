<?php
/** Template part for displaying a page. @package Nekoko */
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry' ); ?>><header class="entry-header"><h1 class="entry-title"><?php the_title(); ?></h1></header><div class="entry-content"><?php the_content(); ?></div></article>
