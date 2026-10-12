<?php
/** Logged-in listing interactions: saved ads and direct messages. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_saved_listing_ids( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	$ids = get_user_meta( $user_id, 'nekoko_saved_listing_ids', true );
	$ids = is_array( $ids ) ? $ids : array();
	return array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
}

function nekoko_core_ajax_toggle_saved_listing() {
	check_ajax_referer( 'nekoko_listing_interaction', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Prijavite se da biste sačuvali oglas.', 'login_url' => nekoko_core_page_url( 'prijava' ) ), 401 );
	}

	$post_id = absint( $_POST['listing_id'] ?? 0 );
	if ( ! $post_id || 'oglas' !== get_post_type( $post_id ) || 'publish' !== get_post_status( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Oglas nije dostupan.' ), 404 );
	}

	$ids = nekoko_core_saved_listing_ids();
	$position = array_search( $post_id, $ids, true );
	if ( false === $position ) {
		$ids[] = $post_id;
		$saved = true;
	} else {
		unset( $ids[ $position ] );
		$ids = array_values( $ids );
		$saved = false;
	}
	update_user_meta( get_current_user_id(), 'nekoko_saved_listing_ids', $ids );
	wp_send_json_success( array( 'saved' => $saved, 'message' => $saved ? 'Oglas je sačuvan.' : 'Oglas je uklonjen iz sačuvanih oglasa.' ) );
}
add_action( 'wp_ajax_nekoko_toggle_saved_listing', 'nekoko_core_ajax_toggle_saved_listing' );
add_action( 'wp_ajax_nopriv_nekoko_toggle_saved_listing', 'nekoko_core_ajax_toggle_saved_listing' );

function nekoko_core_ajax_send_listing_message() {
	check_ajax_referer( 'nekoko_listing_interaction', 'nonce' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Prijavite se da biste poslali poruku.', 'login_url' => nekoko_core_page_url( 'prijava' ) ), 401 );
	}

	$post_id = absint( $_POST['listing_id'] ?? 0 );
	$message = trim( sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ) );
	$post = get_post( $post_id );
	if ( ! $post || 'oglas' !== $post->post_type || 'publish' !== $post->post_status ) {
		wp_send_json_error( array( 'message' => 'Oglas nije dostupan.' ), 404 );
	}
	if ( (int) $post->post_author === get_current_user_id() ) {
		wp_send_json_error( array( 'message' => 'Ne možete poslati poruku za svoj oglas.' ), 400 );
	}
	if ( mb_strlen( $message ) < 5 || mb_strlen( $message ) > 2000 ) {
		wp_send_json_error( array( 'message' => 'Poruka treba da ima između 5 i 2.000 karaktera.' ), 400 );
	}

	$recipient = get_userdata( $post->post_author );
	$sender = wp_get_current_user();
	if ( ! $recipient || ! is_email( $recipient->user_email ) || ! is_email( $sender->user_email ) ) {
		wp_send_json_error( array( 'message' => 'Poruku trenutno nije moguće poslati.' ), 500 );
	}

	$subject = sprintf( 'NekoKO poruka za oglas: %s', $post->post_title );
	$body = sprintf(
		"Zdravo,\n\n%s vam šalje poruku za oglas \"%s\":\n\n%s\n\nOglas: %s\n\nPošiljalac: %s <%s>",
		$sender->display_name,
		$post->post_title,
		$message,
		get_permalink( $post ),
		$sender->display_name,
		$sender->user_email
	);
	$headers = array( 'Content-Type: text/plain; charset=UTF-8', sprintf( 'Reply-To: %s <%s>', $sender->display_name, $sender->user_email ) );
	if ( ! wp_mail( $recipient->user_email, $subject, $body, $headers ) ) {
		wp_send_json_error( array( 'message' => 'Slanje poruke nije uspelo. Pokušajte ponovo kasnije.' ), 500 );
	}

	wp_send_json_success( array( 'message' => 'Poruka je uspešno poslata.' ) );
}
add_action( 'wp_ajax_nekoko_send_listing_message', 'nekoko_core_ajax_send_listing_message' );
add_action( 'wp_ajax_nopriv_nekoko_send_listing_message', 'nekoko_core_ajax_send_listing_message' );

function nekoko_core_shortcode_saved_listings() {
	nekoko_core_require_login();
	$ids = nekoko_core_saved_listing_ids();
	$listings = $ids ? get_posts( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'post__in' => $ids, 'orderby' => 'post__in', 'numberposts' => -1 ) ) : array();
	return nekoko_core_template( 'saved-listings', array( 'listings' => $listings ) );
}
add_shortcode( 'nekoko_saved_listings', 'nekoko_core_shortcode_saved_listings' );
