<?php
/** Sidebar template. @package Nekoko */
if ( ! is_active_sidebar( 'sidebar-1' ) ) { return; }
?>
<aside class="widget-area" aria-label="<?php esc_attr_e( 'Sidebar', 'nekoko' ); ?>"><?php dynamic_sidebar( 'sidebar-1' ); ?></aside>
