<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_frontend_user() {
	if ( empty( $_POST['nekoko_registration'] ) ) { return; }
	$errors = new WP_Error();
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_registration' ) ) { $errors->add( 'nonce', 'Forma je istekla. Pokušajte ponovo.' ); }
	if ( ! empty( $_POST['website'] ) ) { $errors->add( 'bot', 'Registracija nije uspela.' ); }
	$first = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ); $last = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ); $password = (string) ( $_POST['password'] ?? '' ); $repeat = (string) ( $_POST['password_repeat'] ?? '' );
	if ( ! $first || ! $last ) { $errors->add( 'name', 'Ime i prezime su obavezni.' ); }
	if ( ! is_email( $email ) ) { $errors->add( 'email', 'Unesite ispravnu email adresu.' ); } elseif ( email_exists( $email ) ) { $errors->add( 'email_exists', 'Ova email adresa je već registrovana.' ); }
	if ( $password !== $repeat ) { $errors->add( 'password_match', 'Lozinke se ne poklapaju.' ); } elseif ( ! nekoko_core_password_is_strong( $password ) ) { $errors->add( 'password', 'Lozinka mora imati najmanje 10 karaktera i sadržati slovo i broj.' ); }
	if ( empty( $_POST['terms'] ) ) { $errors->add( 'terms', 'Morate prihvatiti uslove korišćenja i politiku privatnosti.' ); }
	$GLOBALS['nekoko_registration_values'] = array( 'first_name' => $first, 'last_name' => $last, 'email' => sanitize_text_field( wp_unslash( $_POST['email'] ?? '' ) ) );
	if ( $errors->has_errors() ) { $GLOBALS['nekoko_registration_errors'] = $errors; return; }
	$user_id = wp_insert_user( array( 'user_login' => nekoko_core_unique_login_from_email( $email ), 'user_pass' => $password, 'user_email' => $email, 'first_name' => $first, 'last_name' => $last, 'display_name' => trim( $first . ' ' . $last ), 'role' => 'oglasivac' ) );
	if ( is_wp_error( $user_id ) ) { $GLOBALS['nekoko_registration_errors'] = $user_id; return; }
	update_user_meta( $user_id, 'nekoko_email_verified', '0' );
	update_user_meta( $user_id, 'nekoko_verification_last_sent', time() );
	$mail = nekoko_core_issue_verification( $user_id );
	// A separate receipt per submission survives refreshes and cannot be consumed by another visitor.
	$receipt = strtolower( wp_generate_password( 32, false, false ) );
	set_transient( 'nekoko_registration_receipt_' . $receipt, array( 'mail_sent' => ! is_wp_error( $mail ) ), DAY_IN_SECONDS );
	wp_safe_redirect( add_query_arg( 'registracija_zavrsena', $receipt, nekoko_core_page_url( 'registracija' ) ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_register_frontend_user', 3 );

/** Set the portal member role on existing and newly deployed installations. */
function nekoko_core_setup_default_member_role() {
    nekoko_core_register_oglasivac_role();
    if ( get_option( 'nekoko_default_member_role_version' ) === '1' ) { return; }
    update_option( 'default_role', 'oglasivac' );
    update_option( 'nekoko_default_member_role_version', '1', false );
}
add_action( 'init', 'nekoko_core_setup_default_member_role', 15 );