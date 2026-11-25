<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_verification_hours() { return max( 1, min( 168, (int) nekoko_core_option( 'nekoko_verification_hours', 48 ) ) ); }
function nekoko_core_new_token() { return bin2hex( random_bytes( 32 ) ); }

/** Shared dark logo for white email backgrounds; PNG works across mail clients. */
function nekoko_core_email_logo_url() {
	return plugins_url( 'assets/images/nekoko-logo-dark.png', NEKOKO_CORE_FILE );
}

function nekoko_core_email_logo_html() {
	return '<p style="margin:0 0 24px;background:#ffffff"><img src="' . esc_url( nekoko_core_email_logo_url() ) . '" alt="NekoKO" width="180" style="display:block;width:180px;max-width:100%;height:auto;border:0;background:#ffffff"></p>';
}

/** Use the same brand asset for WordPress password-reset emails. */
function nekoko_core_password_email_branding( $email ) {
	$email['message'] = '<div style="background:#ffffff;color:#17233c;padding:24px;font-family:Arial,sans-serif;line-height:1.6">' . nekoko_core_email_logo_html() . wpautop( make_clickable( esc_html( $email['message'] ) ) ) . '</div>';
	$headers = $email['headers'] ?? array();
	$headers = is_array( $headers ) ? $headers : preg_split( '/\r?\n/', $headers );
	$headers = array_filter( $headers, static function( $header ) { return 0 !== stripos( trim( $header ), 'Content-Type:' ); } );
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$email['headers'] = $headers;
	return $email;
}
add_filter( 'retrieve_password_notification_email', 'nekoko_core_password_email_branding' );

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
	$body = nekoko_core_email_logo_html() . '<p>Zdravo ' . esc_html( $user->first_name ?: $user->display_name ) . ',</p><p>' . $intro . '</p><p><a href="' . esc_url( $url ) . '">' . esc_html( $button ) . '</a></p><p>' . esc_html( $url ) . '</p><p>Link važi ' . esc_html( nekoko_core_verification_hours() ) . ' sati. Ako niste otvorili nalog ili zatražili promenu emaila, ignorišite ovu poruku.</p>';
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
	set_transient( 'nekoko_verify_notice_' . wp_get_session_token(), $message, MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( is_user_logged_in() ? 'moj-profil' : 'prijava' ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_verify_email_request', 1 );

function nekoko_core_resend_verification() {
    if ( empty( $_POST['nekoko_resend_verification'] ) ) { return; }
    if ( ! is_user_logged_in() || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_resend_verification' ) ) { wp_die( 'Nevažeći zahtev.', '', array( 'response' => 403 ) ); }
    $id = get_current_user_id();
    $redirect = is_page( 'postavite-oglas' ) ? nekoko_core_page_url( 'postavite-oglas' ) : nekoko_core_page_url( 'moj-profil' );
    if ( nekoko_core_is_verified_user() ) { wp_safe_redirect( $redirect ); exit; }
    $last = (int) get_user_meta( $id, 'nekoko_verification_last_sent', true );
    $notice = array( 'type' => 'error', 'message' => 'Link je već poslat. Sačekajte 5 minuta pre novog zahteva i proverite Spam/Neželjenu poštu.' );
    if ( $last + 5 * MINUTE_IN_SECONDS <= time() ) {
        $sent = nekoko_core_issue_verification( $id );
        if ( is_wp_error( $sent ) ) {
            $notice['message'] = 'Email trenutno nije moguće poslati. Pokušajte ponovo za nekoliko minuta ili pišite na info@nekoko.rs.';
        } else {
            update_user_meta( $id, 'nekoko_verification_last_sent', time() );
            $notice = array( 'type' => 'success', 'message' => 'Poslali smo vam email sa linkom za potvrdu. Proverite prijemno sanduče i Spam/Neželjenu poštu.' );
        }
    }
    set_transient( 'nekoko_resend_notice_' . $id, $notice, MINUTE_IN_SECONDS );
    wp_safe_redirect( $redirect . '#nekoko-email-verification' ); exit;
}
add_action( 'template_redirect', 'nekoko_core_resend_verification', 2 );
