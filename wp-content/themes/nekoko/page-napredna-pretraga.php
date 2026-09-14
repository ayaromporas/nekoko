<?php
get_header();
?>
<main id="primary" class="site-main site-main--home"><div class="offer-page-content"><?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?></div></main>
<?php get_footer(); ?>
