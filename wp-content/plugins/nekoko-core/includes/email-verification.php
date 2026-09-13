<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_verification_hours() { return max( 1, min( 168, (int) nekoko_core_option( 'nekoko_verification_hours', 48 ) ) ); }
function nekoko_core_new_token() { return bin2hex( random_bytes( 32 ) ); }

function nekoko_core_issue_verification( $user_id, $purpose = 'registration', $email = '' ) {
	$user = get_userdata( $user_id ); if ( ! $user ) { return new WP_Error( 'invalid_user', 'Nalog nije pronađen.' ); }
	$token = nekoko_core_new_token(); $expires = time() + nekoko_core_verification_hours() * HOUR_IN_SECONDS;
	$prefix = 'nekoko_' . $purpose . '_';
	update_user_meta( $user_id, $prefix . 'token_hash', hash( 'sha256', $token ) );
	update_user_meta( $user_id, $prefix . 'expires', $expires );
	$url = add_query_arg( array( 'user_id' => $user_id, 'token' => $token, 'purpose' => $purpose ), nekoko_core_page_url( 'potvrda-emaila' ) );
	$recipient = $email ?: $user->user_email;
	$subject = sanitize_text_field( nekoko_core_option( 'nekoko_verification_subject', 'Potvrdite svoju NekoKO email adresu' ) );
	$intro = wp_kses_post( nekoko_core_option( 'nekoko_verification_intro', 'Hvala na registraciji.' ) );
	$button = sanitize_text_field( nekoko_core_option( 'nekoko_verification_button', 'Potvrdite email adresu' ) );
	$logo = get_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'medium' ) : '';
	$body = ( $logo ? '<p><img src="' . esc_url( $logo ) . '" alt="NekoKO" style="max-width:180px;height:auto"></p>' : '' ) . '<p>Zdravo ' . esc_html( $user->first_name ?: $user->display_name ) . ',</p><p>' . $intro . '</p><p><a href="' . esc_url( $url ) . '">' . esc_html( $button ) . '</a></p><p>' . esc_html( $url ) . '</p><p>Link važi ' . esc_html( nekoko_core_verification_hours() ) . ' sati. Ako niste otvorili nalog ili zatražili promenu emaila, ignorišite ovu poruku.</p>';
	return wp_mail( $recipient, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) ) ? true : new WP_Error( 'mail_failed', 'WordPress nije prihvatio email za slanje.' );
}

function nekoko_core_verify_email_request() {
	if ( ! is_page( 'potvrda-emaila' ) || empty( $_GET['user_id'] ) || empty( $_GET['token'] ) ) { return; }
	$user_id = absint( $_GET['user_id'] ); $token = sanitize_text_field( wp_unslash( $_GET['token'] ) ); $purpose = isset( $_GET['purpose'] ) && 'email_change' === $_GET['purpose'] ? 'email_change' : 'registration';
	$prefix = 'nekoko_' . $purpose . '_'; $hash = get_user_meta( $user_id, $prefix . 'token_hash', true ); $expires = (int) get_user_meta( $user_id, $prefix . 'expires', true );
	if ( ! $user_id || ! $hash || ! hash_equals( $hash, hash( 'sha256', $token ) ) || $expires < time() ) { set_transient( 'nekoko_verify_notice_' . wp_get_session_token(), 'Link za potvrdu nije važeći ili je istekao.', MINUTE_IN_SECONDS ); return; }
	if ( 'email_change' === $purpose ) { $email = get_user_meta( $user_id, 'nekoko_pending_email', true ); if ( ! is_email( $email ) || ( email_exists( $email ) && (int) email_exists( $email ) !== $user_id ) ) { return; } wp_update_user( array( 'ID' => $user_id, 'user_email' => $email ) ); delete_user_meta( $user_id, 'nekoko_pending_email' ); $message = 'Nova email adresa je uspešno potvrđena.'; }
	else { update_user_meta( $user_id, 'nekoko_email_verified', '1' ); $message = nekoko_core_option( 'nekoko_verification_success', 'Email je uspešno potvrđen. Sada možete da se prijavite.' ); }
	delete_user_meta( $user_id, $prefix . 'token_hash' ); delete_user_meta( $user_id, $prefix . 'expires' );
	set_transient( 'nekoko_verify_notice_' . wp_get_session_token(), $message, MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( 'prijava' ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_verify_email_request', 1 );

function nekoko_core_resend_verification() {
	if ( empty( $_POST['nekoko_resend_verification'] ) || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_resend_verification' ) ) { return; }
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ); $user = $email ? get_user_by( 'email', $email ) : false;
	if ( $user && ! nekoko_core_is_verified_user( $user->ID ) ) { $last = (int) get_user_meta( $user->ID, 'nekoko_verification_last_sent', true ); if ( $last + 5 * MINUTE_IN_SECONDS <= time() ) { nekoko_core_issue_verification( $user->ID ); update_user_meta( $user->ID, 'nekoko_verification_last_sent', time() ); } }
	wp_safe_redirect( add_query_arg( 'verification_sent', '1', nekoko_core_page_url( 'prijava' ) ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_resend_verification', 2 );
