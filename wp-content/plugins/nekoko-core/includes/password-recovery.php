<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_password_pages() {
	if ( get_option( 'nekoko_password_pages_version' ) === '1' ) { return; }
	foreach ( array( 'zaboravljena-lozinka' => array( 'Zaboravljena lozinka', '[nekoko_password_recovery]' ), 'nova-lozinka' => array( 'Nova lozinka', '[nekoko_password_recovery]' ) ) as $slug => $page ) {
		if ( ! get_page_by_path( $slug ) ) { wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $page[0], 'post_content' => $page[1] ) ); }
	}
	update_option( 'nekoko_password_pages_version', '1', false );
}
add_action( 'init', 'nekoko_core_password_pages', 22 );

function nekoko_core_lostpassword_url( $url ) { return home_url( '/zaboravljena-lozinka/' ); }
add_filter( 'lostpassword_url', 'nekoko_core_lostpassword_url' );

function nekoko_core_password_legacy_redirect() {
	$action = sanitize_key( $_REQUEST['action'] ?? '' );
	if ( in_array( $action, array( 'lostpassword', 'retrievepassword' ), true ) ) {
		wp_safe_redirect( home_url( '/zaboravljena-lozinka/' ) ); exit;
	}
	if ( in_array( $action, array( 'rp', 'resetpass' ), true ) ) {
		$url = add_query_arg( array( 'key' => sanitize_text_field( wp_unslash( $_GET['key'] ?? '' ) ), 'login' => sanitize_text_field( wp_unslash( $_GET['login'] ?? '' ) ) ), home_url( '/nova-lozinka/' ) );
		wp_safe_redirect( $url ); exit;
	}
}
add_action( 'login_init', 'nekoko_core_password_legacy_redirect' );

function nekoko_core_reset_cookie( $value, $expires ) {
	setcookie( 'nekoko_reset_' . COOKIEHASH, $value, array( 'expires' => $expires, 'path' => COOKIEPATH ?: '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
}

function nekoko_core_password_request() {
	if ( ! is_page( array( 'zaboravljena-lozinka', 'nova-lozinka' ) ) ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
	header( 'Referrer-Policy: no-referrer' );
	$errors = new WP_Error();
	if ( is_page( 'nova-lozinka' ) ) {
		if ( isset( $_GET['key'], $_GET['login'] ) ) {
			$value = base64_encode( wp_json_encode( array( sanitize_text_field( wp_unslash( $_GET['login'] ) ), sanitize_text_field( wp_unslash( $_GET['key'] ) ) ) ) );
			nekoko_core_reset_cookie( $value, time() + HOUR_IN_SECONDS );
			wp_safe_redirect( home_url( '/nova-lozinka/' ) ); exit;
		}
		$credentials = json_decode( base64_decode( wp_unslash( $_COOKIE[ 'nekoko_reset_' . COOKIEHASH ] ?? '' ), true ) ?: '', true );
		$user = is_array( $credentials ) && count( $credentials ) === 2 ? check_password_reset_key( (string) $credentials[1], (string) $credentials[0] ) : new WP_Error( 'invalid_key' );
		if ( is_wp_error( $user ) ) { $errors->add( 'invalid_key', 'Link za promenu lozinke je istekao ili nije važeći. Zatražite novi link.' ); }
		else { $GLOBALS['nekoko_password_reset_valid'] = true; }
	}
	if ( isset( $_POST['nekoko_password_recovery'] ) && ! $errors->has_errors() ) {
		if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_password_recovery' ) ) { $errors->add( 'nonce', 'Forma je istekla. Osvežite stranicu i pokušajte ponovo.' ); }
		elseif ( is_page( 'zaboravljena-lozinka' ) ) {
			$login = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );
			if ( '' === trim( $login ) ) { $errors->add( 'empty', 'Unesite email adresu ili korisničko ime.' ); }
			else {
				$result = retrieve_password( $login );
				if ( is_wp_error( $result ) && ! array_intersect( $result->get_error_codes(), array( 'invalid_email', 'invalidcombo', 'invalid_username' ) ) ) { $errors->add( 'mail', 'Email trenutno nije moguće poslati. Pokušajte ponovo kasnije.' ); }
				else { $GLOBALS['nekoko_password_notice'] = 'Ako postoji nalog sa unetim podacima, poslali smo link za promenu lozinke. Proverite i Spam/Neželjenu poštu.'; }
			}
		} else {
			$password = (string) wp_unslash( $_POST['pass1'] ?? '' );
			if ( ! nekoko_core_password_is_strong( $password ) ) { $errors->add( 'password', 'Lozinka mora imati najmanje 10 znakova i sadržati slovo i broj.' ); }
			if ( $password !== (string) wp_unslash( $_POST['pass2'] ?? '' ) ) { $errors->add( 'match', 'Lozinke se ne poklapaju.' ); }
			do_action( 'validate_password_reset', $errors, $user );
			if ( ! $errors->has_errors() ) {
				reset_password( $user, $password );
				nekoko_core_reset_cookie( '', time() - HOUR_IN_SECONDS );
				$GLOBALS['nekoko_password_reset_valid'] = false;
				$GLOBALS['nekoko_password_notice'] = 'Lozinka je uspešno promenjena. Sada možete da se prijavite.';
			}
		}
	}
	$GLOBALS['nekoko_password_errors'] = $errors;
}
add_action( 'template_redirect', 'nekoko_core_password_request', 2 );
add_shortcode( 'nekoko_password_recovery', static function() { return nekoko_core_template( 'password-recovery' ); } );
