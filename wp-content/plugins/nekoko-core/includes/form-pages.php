<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_frontend_pages() {
	return array(
		'prijava' => array( 'title' => 'Prijava', 'content' => '[nekoko_login]' ),
		'registracija' => array( 'title' => 'Registracija', 'content' => '[nekoko_registration]' ),
		'potvrda-emaila' => array( 'title' => 'Potvrda emaila', 'content' => '[nekoko_verify_email]' ),
		'postavite-oglas' => array( 'title' => 'Postavite oglas', 'content' => '[nekoko_listing_form]' ),
		'moji-oglasi' => array( 'title' => 'Moji oglasi', 'content' => '[nekoko_user_listings]' ),
		'moj-profil' => array( 'title' => 'Moj profil', 'content' => '[nekoko_profile_form]' ),
	);
}

function nekoko_core_maybe_setup_frontend_pages() {
	$version = '2';
	if ( get_option( 'nekoko_frontend_pages_version' ) === $version ) { return; }
	foreach ( nekoko_core_frontend_pages() as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $existing ) { wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page['title'], 'post_name' => $slug, 'post_content' => $page['content'] ) ); }
		elseif ( false === strpos( $existing->post_content, $page['content'] ) ) { wp_update_post( array( 'ID' => $existing->ID, 'post_content' => rtrim( $existing->post_content ) . "\n\n" . $page['content'] ) ); }
	}
	update_option( 'nekoko_frontend_pages_version', $version, false );
}
add_action( 'init', 'nekoko_core_maybe_setup_frontend_pages', 20 );

function nekoko_core_template( $name, $args = array() ) {
	$file = dirname( __DIR__ ) . '/templates/' . $name . '.php';
	if ( ! is_readable( $file ) ) { return ''; }
	ob_start(); extract( $args, EXTR_SKIP ); include $file; return ob_get_clean();
}

function nekoko_core_frontend_assets() {
	if ( is_admin() ) { return; }
	wp_enqueue_style( 'nekoko-core-forms', plugins_url( 'assets/css/forms.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/css/forms.css' ) );
	wp_enqueue_script( 'nekoko-core-forms', plugins_url( 'assets/js/forms.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/forms.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'nekoko_core_frontend_assets' );

function nekoko_core_shortcode_login() { return nekoko_core_template( 'login-form' ); }
function nekoko_core_shortcode_registration() { return nekoko_core_template( 'registration-form' ); }
function nekoko_core_shortcode_verify() { return nekoko_core_template( 'verify-email' ); }
add_shortcode( 'nekoko_login', 'nekoko_core_shortcode_login' );
add_shortcode( 'nekoko_registration', 'nekoko_core_shortcode_registration' );
add_shortcode( 'nekoko_verify_email', 'nekoko_core_shortcode_verify' );
