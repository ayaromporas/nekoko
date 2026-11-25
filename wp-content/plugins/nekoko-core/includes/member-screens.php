<?php
/** Portal screens for public WordPress account flows. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_member_screen_pages() {
	if ( '1' === get_option( 'nekoko_member_screens_version' ) ) { return; }
	foreach ( array( 'odjava' => 'Odjava', 'potvrda-zahteva' => 'Potvrda zahteva' ) as $slug => $title ) {
		if ( ! get_page_by_path( $slug ) ) { wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title, 'post_content' => '[nekoko_member_screen]' ) ); }
	}
	update_option( 'nekoko_member_screens_version', '1', false );
}
add_action( 'init', 'nekoko_core_member_screen_pages', 23 );

function nekoko_core_is_admin_login( $redirect ) {
	return false !== strpos( (string) $redirect, '/wp-admin' ) || ! empty( $_REQUEST['interim-login'] );
}
function nekoko_core_member_login_url( $url, $redirect ) {
	return nekoko_core_is_admin_login( $redirect ) ? $url : home_url( '/prijava/' );
}
add_filter( 'login_url', 'nekoko_core_member_login_url', 10, 2 );
add_filter( 'register_url', static function() { return home_url( '/registracija/' ); } );

function nekoko_core_login_status_notice() {
	$message = '';
	if ( isset( $_GET['checkemail'] ) ) { $message = 'Proverite email za naredni korak. Pogledajte i Spam/Neželjenu poštu.'; }
	elseif ( ! empty( $_GET['loggedout'] ) ) { $message = 'Uspešno ste se odjavili.'; }
	elseif ( isset( $_GET['resetpass'] ) ) { $message = 'Lozinka je promenjena. Prijavite se sa novom lozinkom.'; }
	elseif ( ! empty( $_GET['reauth'] ) || ! empty( $_GET['expired'] ) ) { $message = 'Sesija je istekla. Prijavite se ponovo.'; }
	elseif ( isset( $_GET['registration'] ) && 'disabled' === $_GET['registration'] ) { $message = 'Registracija trenutno nije dostupna.'; }
	return $message ? nekoko_core_form_notice( $message ) : '';
}

function nekoko_core_member_logout_url( $url, $redirect ) {
	return add_query_arg( array( '_wpnonce' => wp_create_nonce( 'log-out' ), 'redirect_to' => wp_validate_redirect( $redirect, home_url( '/' ) ) ), home_url( '/odjava/' ) );
}
add_filter( 'logout_url', 'nekoko_core_member_logout_url', 10, 2 );

function nekoko_core_member_login_routes() {
	$action = sanitize_key( $_REQUEST['action'] ?? 'login' );
	if ( nekoko_core_is_admin_login( $_REQUEST['redirect_to'] ?? '' ) ) { return; }
	$url = '';
	if ( 'register' === $action ) { $url = home_url( '/registracija/' ); }
	elseif ( 'logout' === $action ) {
		$url = add_query_arg( array( '_wpnonce' => sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) ), 'redirect_to' => wp_validate_redirect( wp_unslash( $_REQUEST['redirect_to'] ?? '' ), home_url( '/' ) ) ), home_url( '/odjava/' ) );
	}
	elseif ( 'confirmaction' === $action ) {
		$url = add_query_arg( array( 'request_id' => absint( $_GET['request_id'] ?? 0 ), 'confirm_key' => sanitize_text_field( wp_unslash( $_GET['confirm_key'] ?? '' ) ) ), home_url( '/potvrda-zahteva/' ) );
	}
	elseif ( in_array( $action, array( 'login', 'checkemail' ), true ) ) {
		$url = home_url( '/prijava/' );
		foreach ( array( 'checkemail', 'loggedout', 'registration', 'resetpass', 'reauth', 'expired' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { $url = add_query_arg( $key, sanitize_key( $_GET[ $key ] ), $url ); }
		}
	}
	if ( $url ) { wp_safe_redirect( $url ); exit; }
}
add_action( 'login_init', 'nekoko_core_member_login_routes', 20 );

function nekoko_core_member_screen_request() {
	if ( ! is_page( array( 'odjava', 'potvrda-zahteva' ) ) ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	if ( is_page( 'odjava' ) && is_user_logged_in() && wp_verify_nonce( $_REQUEST['_wpnonce'] ?? '', 'log-out' ) ) {
		wp_logout();
		wp_safe_redirect( wp_validate_redirect( wp_unslash( $_REQUEST['redirect_to'] ?? '' ), home_url( '/' ) ) ); exit;
	}
	if ( is_page( 'potvrda-zahteva' ) ) {
		$id = absint( $_GET['request_id'] ?? 0 );
		$result = wp_validate_user_request_key( $id, sanitize_text_field( wp_unslash( $_GET['confirm_key'] ?? '' ) ) );
		if ( is_wp_error( $result ) ) { $GLOBALS['nekoko_member_screen_error'] = 'Link za potvrdu zahteva je istekao ili nije važeći.'; }
		else { do_action( 'user_request_action_confirmed', $id ); $GLOBALS['nekoko_member_screen_notice'] = 'Zahtev je potvrđen. Administrator je obavešten i obradiće vaš zahtev.'; }
	}
}
add_action( 'template_redirect', 'nekoko_core_member_screen_request', 2 );

add_shortcode( 'nekoko_member_screen', static function() {
	return nekoko_core_template( 'member-screen' );
} );

/** Render public HTML errors in the theme; keep machine and admin responses intact. */
function nekoko_core_public_die_handler( $handler ) {
	if ( is_admin() || wp_doing_ajax() || wp_is_json_request() || wp_is_serving_rest_request() || defined( 'XMLRPC_REQUEST' ) || headers_sent() || ! did_action( 'wp_loaded' ) ) { return $handler; }
	return 'nekoko_core_render_public_error';
}
add_filter( 'wp_die_handler', 'nekoko_core_public_die_handler' );
function nekoko_core_render_public_error( $message, $title = '', $args = array() ) {
	list( $message, $title, $args ) = _wp_die_process_input( $message, $title, $args );
	if ( ! headers_sent() ) { status_header( $args['response'] ); nocache_headers(); }
	get_header();
	echo '<main id="primary" class="site-main"><div class="nekoko-form"><h1>' . esc_html( $title ?: 'Zahtev nije moguće obraditi' ) . '</h1><div role="alert">' . wp_kses_post( $message ) . '</div><p><a href="' . esc_url( home_url( '/' ) ) . '">Nazad na početnu stranicu</a></p></div></main>';
	get_footer();
	if ( $args['exit'] ) { exit; }
}

add_filter( 'the_password_form', static function( $form ) { return '<div class="nekoko-form">' . $form . '</div>'; } );
