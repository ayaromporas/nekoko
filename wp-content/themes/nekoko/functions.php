<?php
/**
 * Nekoko theme functions and definitions.
 *
 * @package Nekoko
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/footer-widgets.php';

function nekoko_setup() {
	load_theme_textdomain( 'nekoko', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support(
		'editor-color-palette',
		array(
			array( 'name' => __( 'Primary Blue', 'nekoko' ), 'slug' => 'nekoko-primary', 'color' => '#004682' ),
			array( 'name' => __( 'Secondary Yellow', 'nekoko' ), 'slug' => 'nekoko-secondary', 'color' => '#F5B335' ),
			array( 'name' => __( 'Brick', 'nekoko' ), 'slug' => 'nekoko-brick', 'color' => '#BD433F' ),
			array( 'name' => __( 'White', 'nekoko' ), 'slug' => 'nekoko-white', 'color' => '#FFFFFF' ),
			array( 'name' => __( 'Black', 'nekoko' ), 'slug' => 'nekoko-black', 'color' => '#000000' ),
		)
	);
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 96, 'width' => 240, 'flex-width' => true, 'flex-height' => true ) );
	register_nav_menus( array( 'primary' => __( 'Primary Menu', 'nekoko' ), 'footer' => __( 'Footer Menu', 'nekoko' ), 'social-menu' => __( 'Social Menu', 'nekoko' ) ) );
	add_editor_style( 'assets/css/editor-style.css' );
}
add_action( 'after_setup_theme', 'nekoko_setup' );

function nekoko_register_block_styles() {
	$button_styles = array(
		'nekoko-primary'   => __( 'Plavo dugme', 'nekoko' ),
		'nekoko-secondary' => __( 'Žuto dugme', 'nekoko' ),
		'nekoko-outline'   => __( 'Plavi okvir', 'nekoko' ),
	);

	foreach ( $button_styles as $name => $label ) {
		register_block_style( 'core/button', array( 'name' => $name, 'label' => $label ) );
	}

	// A single stylesheet keeps the frontend and the editor canvas in sync.
	wp_enqueue_block_style(
		'core/button',
		array(
			'handle' => 'nekoko-button-styles',
			'src'    => get_theme_file_uri( 'assets/css/button-styles.css' ),
			'ver'    => filemtime( get_theme_file_path( 'assets/css/button-styles.css' ) ),
		)
	);
}
add_action( 'init', 'nekoko_register_block_styles' );

function nekoko_register_new_button_styles() {
	$button_styles = array(
		'nekoko-yellow'           => 'NekoKO: Žuto',
		'nekoko-blue'             => 'NekoKO: Plavo',
		'nekoko-white-blue'       => 'NekoKO: Belo/plavi okvir',
		'nekoko-red'              => 'NekoKO: Crveno',
		'nekoko-yellow-small'     => 'NekoKO: Žuto – malo',
		'nekoko-blue-small'       => 'NekoKO: Plavo – malo',
		'nekoko-white-blue-small' => 'NekoKO: Belo/plavi okvir – malo',
		'nekoko-white-red-small'  => 'NekoKO: Belo/crveni okvir – malo',
		'nekoko-text-blue'        => 'NekoKO: Tekstualno – plavo',
		'nekoko-text-red'         => 'NekoKO: Tekstualno – crveno',
		'nekoko-text-yellow'      => 'NekoKO: Tekstualno – žuto',
		'nekoko-text-black'       => 'NekoKO: Tekstualno – crno',
	);
	foreach ( $button_styles as $name => $label ) { register_block_style( 'core/button', array( 'name' => $name, 'label' => $label ) ); }
}
add_action( 'init', 'nekoko_register_new_button_styles', 11 );

function nekoko_register_pill_styles() {
	register_block_style( 'core/paragraph', array( 'name' => 'nekoko-pill-blue', 'label' => 'NekoKO: Pilula – plava' ) );
	register_block_style( 'core/paragraph', array( 'name' => 'nekoko-pill-red', 'label' => 'NekoKO: Pilula – crvena' ) );
	register_block_style( 'core/paragraph', array( 'name' => 'nekoko-pill-light-blue', 'label' => 'NekoKO: Pilula – svetloplava' ) );
}
add_action( 'init', 'nekoko_register_pill_styles', 12 );

function nekoko_register_light_red_pill_style() {
	register_block_style( 'core/paragraph', array( 'name' => 'nekoko-pill-light-red', 'label' => 'NekoKO: Pilula – svetlocrvena' ) );
}
add_action( 'init', 'nekoko_register_light_red_pill_style', 13 );

function nekoko_register_button_pattern() {
	register_block_pattern_category( 'nekoko', array( 'label' => 'NekoKO' ) );
	register_block_pattern( 'nekoko/buttons', array( 'title' => 'NekoKO – dugmad', 'categories' => array( 'nekoko' ), 'content' => nekoko_button_pattern_content() ) );
}
add_action( 'init', 'nekoko_register_button_pattern', 20 );

function nekoko_button_pattern_content() {
	$buttons = array( 'nekoko-yellow', 'nekoko-blue', 'nekoko-white-blue', 'nekoko-red', 'nekoko-yellow-small', 'nekoko-blue-small', 'nekoko-white-blue-small', 'nekoko-white-red-small' );
	$content = '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Standardna dugmad</h3><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons">';
	foreach ( array_slice( $buttons, 0, 4 ) as $style ) { $content .= '<!-- wp:button {"className":"is-style-' . $style . '"} --><div class="wp-block-button is-style-' . $style . '"><a class="wp-block-button__link wp-element-button" href="/prijava/">Prijava</a></div><!-- /wp:button -->'; }
	$content .= '</div><!-- /wp:buttons --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Mala dugmad</h3><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons">';
	foreach ( array_slice( $buttons, 4 ) as $style ) { $content .= '<!-- wp:button {"className":"is-style-' . $style . '"} --><div class="wp-block-button is-style-' . $style . '"><a class="wp-block-button__link wp-element-button" href="/prijava/">Prijava</a></div><!-- /wp:button -->'; }
	return $content . '</div><!-- /wp:buttons -->';
}

function nekoko_update_test_page_buttons() {
	$version = '2';
	if ( get_option( 'nekoko_test_page_buttons_version' ) === $version ) { return; }
	$page = get_page_by_path( 'test-page', OBJECT, 'page' );
	if ( ! $page ) { return; }
	$section = '<!-- wp:heading {"anchor":"biblioteka-dugmici"} --><h2 class="wp-block-heading" id="biblioteka-dugmici">Dugmad</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Izaberite dugme, kopirajte ga i promenite njegov tekst i odredišni link. Pređite mišem preko dugmeta da biste videli hover stanje.</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Standardna dugmad</h3><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons">';
	foreach ( array( 'nekoko-yellow', 'nekoko-blue', 'nekoko-white-blue', 'nekoko-red' ) as $style ) { $section .= '<!-- wp:button {"className":"is-style-' . $style . '"} --><div class="wp-block-button is-style-' . $style . '"><a class="wp-block-button__link wp-element-button" href="/prijava/">Prijava</a></div><!-- /wp:button -->'; }
	$section .= '</div><!-- /wp:buttons --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Mala dugmad</h3><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons">';
	foreach ( array( 'nekoko-yellow-small', 'nekoko-blue-small', 'nekoko-white-blue-small', 'nekoko-white-red-small' ) as $style ) { $section .= '<!-- wp:button {"className":"is-style-' . $style . '"} --><div class="wp-block-button is-style-' . $style . '"><a class="wp-block-button__link wp-element-button" href="/prijava/">Prijava</a></div><!-- /wp:button -->'; }
	$section .= '</div><!-- /wp:buttons --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Tekstualna dugmad</h3><!-- /wp:heading --><!-- wp:buttons --><div class="wp-block-buttons">';
	foreach ( array( 'nekoko-text-blue', 'nekoko-text-red', 'nekoko-text-yellow', 'nekoko-text-black' ) as $style ) { $section .= '<!-- wp:button {"className":"is-style-' . $style . '"} --><div class="wp-block-button is-style-' . $style . '"><a class="wp-block-button__link wp-element-button" href="/prijava/">Pogledaj oglase</a></div><!-- /wp:button -->'; }
	$section .= '</div><!-- /wp:buttons -->';
	$updated = preg_replace( '~<!-- wp:heading \{\\"anchor\\":\\"biblioteka-dugmici\\"\} -->.*?(?=<!-- wp:heading \{\\"anchor\\":\\"biblioteka-mediji\\"\} -->)~s', $section, $page->post_content, 1, $count );
	if ( 1 === $count ) { wp_update_post( array( 'ID' => $page->ID, 'post_content' => $updated ) ); update_option( 'nekoko_test_page_buttons_version', $version, false ); }
}
add_action( 'init', 'nekoko_update_test_page_buttons', 30 );

function nekoko_update_test_page_pills() {
	$version = '1';
	if ( get_option( 'nekoko_test_page_pills_version' ) === $version ) { return; }
	$page = get_page_by_path( 'test-page', OBJECT, 'page' );
	if ( ! $page || false !== strpos( $page->post_content, 'biblioteka-pilule' ) ) { return; }
	$section = '<!-- wp:heading {"anchor":"biblioteka-pilule"} --><h2 class="wp-block-heading" id="biblioteka-pilule">08 — Pilule</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Pilula je mali tekst iznad naslova. Koristite Paragraph blok, unesite željenu oznaku i u panelu Stilovi izaberite plavu ili crvenu NekoKO pilulu.</p><!-- /wp:paragraph --><!-- wp:columns --><div class="wp-block-columns"><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"className":"is-style-nekoko-pill-blue"} --><p class="is-style-nekoko-pill-blue">Ponuda</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Istaknite ono što nudite</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Od čuvanja egzotičnih ljubimaca do kreativnih radionica, NekoKO daje prostor uslugama koje su korisne, neobične i ljudima zaista trebaju.</p><!-- /wp:paragraph --></div><!-- /wp:column --><!-- wp:column --><div class="wp-block-column"><!-- wp:paragraph {"className":"is-style-nekoko-pill-red"} --><p class="is-style-nekoko-pill-red">Potražnja</p><!-- /wp:paragraph --><!-- wp:heading {"level":3} --><h3 class="wp-block-heading">Recite šta vam treba</h3><!-- /wp:heading --><!-- wp:paragraph --><p>Kada tražite nešto van standardnih oglasa, važni su jasnoća, poverenje i dobar opis. Zato je potražnja na NekoKO jednostavna i pregledna.</p><!-- /wp:paragraph --></div><!-- /wp:column --></div><!-- /wp:columns -->';
	$anchor = '<!-- wp:heading {"anchor":"biblioteka-dugmici"} -->';
	$position = strpos( $page->post_content, $anchor );
	if ( false === $position ) { return; }
	$updated = substr( $page->post_content, 0, $position ) . $section . "\n\n" . substr( $page->post_content, $position );
	if ( wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( $updated ) ), true ) ) { update_option( 'nekoko_test_page_pills_version', $version, false ); }
}
add_action( 'init', 'nekoko_update_test_page_pills', 31 );

function nekoko_add_test_page_light_blue_pill() {
	$version = '1';
	if ( get_option( 'nekoko_test_page_light_blue_pill_version' ) === $version ) { return; }
	$page = get_page_by_path( 'test-page', OBJECT, 'page' );
	if ( ! $page || false !== strpos( $page->post_content, 'is-style-nekoko-pill-light-blue' ) ) { return; }
	$anchor = '<!-- wp:heading {"anchor":"biblioteka-dugmici"} -->';
	$position = strpos( $page->post_content, $anchor );
	if ( false === $position ) { return; }
	$section = '<!-- wp:paragraph {"className":"is-style-nekoko-pill-light-blue"} --><p class="is-style-nekoko-pill-light-blue">Podkategorija</p><!-- /wp:paragraph -->';
	$updated = substr( $page->post_content, 0, $position ) . $section . "\n\n" . substr( $page->post_content, $position );
	if ( wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( $updated ) ), true ) ) { update_option( 'nekoko_test_page_light_blue_pill_version', $version, false ); }
}
add_action( 'init', 'nekoko_add_test_page_light_blue_pill', 32 );

function nekoko_add_test_page_light_red_pill() {
	$version = '1';
	if ( get_option( 'nekoko_test_page_light_red_pill_version' ) === $version ) { return; }
	$page = get_page_by_path( 'test-page', OBJECT, 'page' );
	if ( ! $page || false !== strpos( $page->post_content, 'is-style-nekoko-pill-light-red' ) ) { return; }
	$anchor = '<!-- wp:heading {"anchor":"biblioteka-dugmici"} -->';
	$position = strpos( $page->post_content, $anchor );
	if ( false === $position ) { return; }
	$section = '<!-- wp:paragraph {"className":"is-style-nekoko-pill-light-red"} --><p class="is-style-nekoko-pill-light-red">Podkategorija – Potražnja</p><!-- /wp:paragraph -->';
	$updated = substr( $page->post_content, 0, $position ) . $section . "\n\n" . substr( $page->post_content, $position );
	if ( wp_update_post( array( 'ID' => $page->ID, 'post_content' => wp_slash( $updated ) ), true ) ) { update_option( 'nekoko_test_page_light_red_pill_version', $version, false ); }
}
add_action( 'init', 'nekoko_add_test_page_light_red_pill', 33 );

function nekoko_widgets_init() {
	register_sidebar( array( 'name' => __( 'Sidebar', 'nekoko' ), 'id' => 'sidebar-1', 'description' => __( 'Add widgets shown beside posts and archives.', 'nekoko' ), 'before_widget' => '<section id="%1$s" class="widget %2$s">', 'after_widget' => '</section>', 'before_title' => '<h2 class="widget-title">', 'after_title' => '</h2>' ) );
	for ( $column = 1; $column <= 4; $column++ ) {
		register_sidebar( array(
			'name'          => 'Footer ' . $column,
			'id'            => 'footer-' . $column,
			'description'   => __( 'Kolona u podnožju sajta. Naslov i sadržaj uređuju se u widgetima.', 'nekoko' ),
			'before_widget' => '<section id="%1$s" class="footer-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="footer-widget__title">',
			'after_title'   => '</h2>',
			'show_in_rest'  => true,
		) );
	}
	register_widget( 'Nekoko_Footer_Brand_Widget' );
	register_sidebar( array(
		'name'          => __( 'Footer dno', 'nekoko' ),
		'id'            => 'footer-dno',
		'description'   => __( 'Donji red footera, ispod razdelne linije.', 'nekoko' ),
		'before_widget' => '<section id="%1$s" class="footer-widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="footer-widget__title">',
		'after_title'   => '</h2>',
		'show_in_rest'  => true,
	) );
	register_widget( 'Nekoko_Footer_Copyright_Widget' );
}
add_action( 'widgets_init', 'nekoko_widgets_init' );

function nekoko_enqueue_assets() {
	wp_enqueue_style( 'nekoko-style', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
	// The header uses the same named Button styles even on pages without Button blocks.
	wp_enqueue_style( 'nekoko-button-styles', get_theme_file_uri( 'assets/css/button-styles.css' ), array( 'nekoko-style' ), filemtime( get_theme_file_path( 'assets/css/button-styles.css' ) ) );
	wp_enqueue_script( 'nekoko-theme', get_theme_file_uri( 'assets/js/theme.js' ), array(), filemtime( get_theme_file_path( 'assets/js/theme.js' ) ), true );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) { wp_enqueue_script( 'comment-reply' ); }
}
add_action( 'wp_enqueue_scripts', 'nekoko_enqueue_assets' );

/**
 * Keep widget-area block previews readable on the white Widgets editor canvas.
 *
 * Frontend footer colors intentionally remain confined to .site-footer in
 * style.css. The block Widgets editor does not render that wrapper, so it
 * needs its own narrowly scoped editor treatment rather than frontend CSS.
 */
function nekoko_enqueue_widgets_editor_style() {
	$screen = get_current_screen();

	if ( ! $screen || 'widgets' !== $screen->id || ! $screen->is_block_editor() ) {
		return;
	}

	wp_enqueue_style(
		'nekoko-widgets-editor',
		get_theme_file_uri( 'assets/css/widgets-editor.css' ),
		array(),
		filemtime( get_theme_file_path( 'assets/css/widgets-editor.css' ) )
	);
}
add_action( 'enqueue_block_editor_assets', 'nekoko_enqueue_widgets_editor_style' );

/** Read optional site-wide ACF values without depending on ACF being active. */
function nekoko_header_option( $name ) {
	return function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
}

function nekoko_header_label( $name, $fallback ) {
	$value = nekoko_header_option( $name );
	return is_string( $value ) && '' !== trim( $value ) ? trim( $value ) : $fallback;
}

function nekoko_header_link( $name, $fallback ) {
	$value = nekoko_header_option( $name );
	$url = is_array( $value ) && isset( $value['url'] ) && is_string( $value['url'] ) ? esc_url_raw( $value['url'], array( 'http', 'https' ) ) : '';
	return array( 'url' => $url ? $url : $fallback, 'target' => $url && isset( $value['target'] ) && '_blank' === $value['target'] ? '_blank' : '_self' );
}

/** Authentication state and the nonce-protected logout URL are resolved per request. */
function nekoko_header_actions() {
	$post_ad = nekoko_header_link( 'header_post_ad_link', home_url( '/postavite-oglas/' ) );
	$post_ad['label'] = nekoko_header_label( 'header_post_ad_label', 'Postavite oglas' );
	$post_ad['style'] = 'is-style-nekoko-yellow';

	if ( is_user_logged_in() ) {
		$redirect = nekoko_header_option( 'header_logout_redirect' );
		$redirect = is_string( $redirect ) ? esc_url_raw( $redirect, array( 'http', 'https' ) ) : '';
		$redirect = $redirect ? wp_validate_redirect( $redirect, home_url( '/' ) ) : home_url( '/' );
		$account = array( 'url' => wp_logout_url( $redirect ), 'target' => '_self', 'label' => nekoko_header_label( 'header_logout_label', 'Odjava' ) );
	} else {
		$account = nekoko_header_link( 'header_login_link', home_url( '/prijava/' ) );
		$account['label'] = nekoko_header_label( 'header_login_label', 'Prijava' );
	}
	$account['style'] = 'is-style-nekoko-white-blue';
	return array( $post_ad, $account );
}
