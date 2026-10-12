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
			<?php $nekoko_actions = nekoko_header_actions(); $nekoko_post_ad = $nekoko_actions[0]; ?>
			<div class="wp-block-button header-actions__post-ad <?php echo esc_attr( $nekoko_post_ad['style'] ); ?>">
				<a class="wp-block-button__link" href="<?php echo esc_url( $nekoko_post_ad['url'] ); ?>"<?php if ( '_blank' === $nekoko_post_ad['target'] ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>><?php echo esc_html( $nekoko_post_ad['label'] ); ?><?php if ( '_blank' === $nekoko_post_ad['target'] ) : ?><span class="screen-reader-text"> (otvara se u novom tabu)</span><?php endif; ?></a>
			</div>
			<form class="header-search" action="<?php echo esc_url( home_url( '/napredna-pretraga/' ) ); ?>" method="get" role="search">
				<label class="screen-reader-text" for="header-search-query"><?php esc_html_e( 'Pretražite oglase', 'nekoko' ); ?></label>
				<input id="header-search-query" class="header-search__input" type="search" name="q" placeholder="Pretražite oglase" required>
				<button class="header-search__submit" type="submit" aria-label="Pretraži">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m21 21-4.35-4.35m2.35-5.15a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"/></svg>
					<span class="screen-reader-text">Pretraži</span>
				</button>
			</form>
			<?php if ( is_user_logged_in() ) : ?>
				<?php $nekoko_account = nekoko_header_account_menu(); ?>
				<div class="header-account">
					<a class="header-account__trigger" href="<?php echo esc_url( $nekoko_account['profile_url'] ); ?>" aria-haspopup="true">
						<?php echo nekoko_header_account_icon( 'profile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?>
						<span><?php esc_html_e( 'Moj nalog', 'nekoko' ); ?></span>
						<svg class="header-account__chevron" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m6 9 6 6 6-6"/></svg>
					</a>
					<div class="header-account__dropdown">
						<span class="header-account__pointer" aria-hidden="true"></span>
						<ul class="header-account__menu">
							<?php foreach ( $nekoko_account['items'] as $nekoko_item ) : ?>
								<li class="header-account__item header-account__item--<?php echo esc_attr( $nekoko_item['icon'] ); ?>"><a href="<?php echo esc_url( $nekoko_item['url'] ); ?>"><?php echo nekoko_header_account_icon( $nekoko_item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?><span><?php echo esc_html( $nekoko_item['label'] ); ?></span></a></li>
							<?php endforeach; ?>
						</ul>
						<a class="header-account__logout" href="<?php echo esc_url( $nekoko_account['logout_url'] ); ?>"><?php echo nekoko_header_account_icon( 'logout' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?><span><?php echo esc_html( $nekoko_account['logout_label'] ); ?></span></a>
					</div>
				</div>
			<?php elseif ( isset( $nekoko_actions[1] ) ) : ?>
				<?php $nekoko_login = $nekoko_actions[1]; ?>
				<a class="header-account__trigger header-account__login" href="<?php echo esc_url( $nekoko_login['url'] ); ?>"<?php if ( '_blank' === $nekoko_login['target'] ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>
					<?php echo nekoko_header_account_icon( 'profile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?>
					<span><?php echo esc_html( $nekoko_login['label'] ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div></header>
