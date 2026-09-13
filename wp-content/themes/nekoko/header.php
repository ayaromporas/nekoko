<?php
/** Site header. @package Nekoko */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'nekoko' ); ?></a>
<header class="site-header"><div class="site-header__inner">
	<div class="site-branding">
		<?php if ( has_custom_logo() ) { the_custom_logo(); } ?>
	</div>
	<button class="menu-toggle" type="button" aria-controls="header-menu-panel" aria-expanded="false" aria-label="Otvori meni">
		<span class="menu-toggle__icon" aria-hidden="true"><span></span><span></span><span></span></span>
	</button>
	<div id="header-menu-panel" class="site-header__content">
		<nav class="main-navigation" aria-label="Glavni meni">
			<?php wp_nav_menu( array( 'theme_location' => 'primary', 'menu_id' => 'primary-menu', 'menu_class' => 'primary-menu', 'container' => false, 'fallback_cb' => false ) ); ?>
		</nav>
		<div class="header-actions">
			<?php foreach ( nekoko_header_actions() as $nekoko_action ) : ?>
				<div class="wp-block-button <?php echo esc_attr( $nekoko_action['style'] ); ?>">
					<a class="wp-block-button__link" href="<?php echo esc_url( $nekoko_action['url'] ); ?>"<?php if ( '_blank' === $nekoko_action['target'] ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $nekoko_action['label'] ); ?><?php if ( '_blank' === $nekoko_action['target'] ) : ?><span class="screen-reader-text"> (otvara se u novom tabu)</span><?php endif; ?></a>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div></header>
