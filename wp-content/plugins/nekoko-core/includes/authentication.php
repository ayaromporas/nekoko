<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_authenticate_email_login( $user, $username, $password ) {
	if ( $user instanceof WP_User || ! $username ) { return $user; }
	if ( is_email( $username ) ) { $found = get_user_by( 'email', $username ); if ( $found ) { return wp_authenticate_username_password( null, $found->user_login, $password ); } }
	return $user;
}
add_filter( 'authenticate', 'nekoko_core_authenticate_email_login', 20, 3 );

function nekoko_core_frontend_login() {
	if ( empty( $_POST['nekoko_login'] ) ) { return; }
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_login' ) ) { set_transient( 'nekoko_login_error', 'Forma je istekla. Pokušajte ponovo.', MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( 'prijava' ) ); exit; }
	$login = sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) ); $user = is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );
	if ( $user && ! nekoko_core_is_verified_user( $user->ID ) ) { set_transient( 'nekoko_login_error', 'Vaša email adresa još nije potvrđena. Proverite email ili zatražite novi link za potvrdu.', MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( 'prijava' ) ); exit; }
	$result = wp_signon( array( 'user_login' => $login, 'user_password' => (string) ( $_POST['pwd'] ?? '' ), 'remember' => ! empty( $_POST['rememberme'] ) ), is_ssl() );
	if ( is_wp_error( $result ) ) { set_transient( 'nekoko_login_error', 'Email/korisničko ime ili lozinka nisu ispravni.', MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( 'prijava' ) ); exit; }
	wp_safe_redirect( home_url( '/' ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_frontend_login', 4 );

function nekoko_core_hide_admin_for_oglasivac() { if ( is_admin() && ! wp_doing_ajax() && current_user_can( 'read' ) && ! current_user_can( 'manage_options' ) ) { wp_safe_redirect( nekoko_core_page_url( 'moji-oglasi' ) ); exit; } }
add_action( 'admin_init', 'nekoko_core_hide_admin_for_oglasivac' );
