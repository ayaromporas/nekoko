<?php
/** 404 template. @package Nekoko */
get_header();
?>
<main id="primary" class="site-main"><section class="not-found"><h1><?php esc_html_e( 'Page not found', 'nekoko' ); ?></h1><p><?php esc_html_e( 'The page you requested does not exist or has moved.', 'nekoko' ); ?></p><?php get_search_form(); ?></section></main>
<?php get_footer(); ?>
