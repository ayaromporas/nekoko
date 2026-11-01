<?php
/** Collaboration confirmation and listing-specific reviews. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_review_tables() {
	global $wpdb;
	return array(
		'collaborations' => $wpdb->prefix . 'nekoko_collaborations',
		'reviews'        => $wpdb->prefix . 'nekoko_reviews',
	);
}

function nekoko_core_maybe_install_review_tables() {
	if ( '1' === get_option( 'nekoko_review_tables_version' ) ) { return; }
	global $wpdb;
	$tables = nekoko_core_review_tables();
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	dbDelta( "CREATE TABLE {$tables['collaborations']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		listing_id bigint(20) unsigned NOT NULL,
		requester_id bigint(20) unsigned NOT NULL,
		provider_id bigint(20) unsigned NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'pending',
		created_at datetime NOT NULL,
		confirmed_at datetime NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY collaboration (listing_id,requester_id,provider_id),
		KEY provider_status (provider_id,status),
		KEY requester_status (requester_id,status)
	) $charset;" );
	dbDelta( "CREATE TABLE {$tables['reviews']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		collaboration_id bigint(20) unsigned NOT NULL,
		listing_id bigint(20) unsigned NOT NULL,
		reviewer_id bigint(20) unsigned NOT NULL,
		reviewed_user_id bigint(20) unsigned NOT NULL,
		rating tinyint(3) unsigned NOT NULL,
		review_text longtext NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY collaboration_id (collaboration_id),
		KEY listing_id (listing_id),
		KEY reviewed_user_id (reviewed_user_id)
	) $charset;" );
	update_option( 'nekoko_review_tables_version', '1', false );
}
add_action( 'init', 'nekoko_core_maybe_install_review_tables', 1 );

function nekoko_core_collaboration_notice_key( $user_id ) { return 'nekoko_collaboration_notice_' . absint( $user_id ); }
function nekoko_core_set_collaboration_notice( $message, $type = 'success' ) { set_transient( nekoko_core_collaboration_notice_key( get_current_user_id() ), array( 'message' => $message, 'type' => $type ), MINUTE_IN_SECONDS ); }
function nekoko_core_take_collaboration_notice( $user_id = 0 ) { $key = nekoko_core_collaboration_notice_key( $user_id ?: get_current_user_id() ); $notice = get_transient( $key ); delete_transient( $key ); return is_array( $notice ) ? $notice : false; }

function nekoko_core_collaboration( $listing_id, $requester_id, $provider_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['collaborations']} WHERE listing_id = %d AND requester_id = %d AND provider_id = %d", $listing_id, $requester_id, $provider_id ) );
}

function nekoko_core_confirmed_collaboration( $listing_id, $requester_id, $provider_id ) {
	$collaboration = nekoko_core_collaboration( $listing_id, $requester_id, $provider_id );
	return $collaboration && 'confirmed' === $collaboration->status ? $collaboration : false;
}

function nekoko_core_request_collaboration() {
	if ( ! is_user_logged_in() ) { auth_redirect(); }
	$listing_id = absint( $_POST['listing_id'] ?? 0 );
	$profile_id = absint( $_POST['profile_id'] ?? 0 );
	if ( ! $listing_id || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_request_collaboration_' . $listing_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$listing = get_post( $listing_id );
	$provider_id = $listing ? (int) $listing->post_author : 0;
	$redirect = $profile_id ? get_author_posts_url( $profile_id ) : home_url( '/' );
	if ( ! $listing || 'oglas' !== $listing->post_type || 'publish' !== $listing->post_status || ! $provider_id || $provider_id !== $profile_id || $provider_id === get_current_user_id() ) {
		nekoko_core_set_collaboration_notice( 'Zahtev za saradnju nije moguće poslati za ovaj oglas.', 'error' );
		wp_safe_redirect( $redirect ); exit;
	}
	$existing = nekoko_core_collaboration( $listing_id, get_current_user_id(), $provider_id );
	if ( $existing ) {
		nekoko_core_set_collaboration_notice( 'Zahtev za saradnju za ovaj oglas već postoji.', 'error' );
		wp_safe_redirect( $redirect ); exit;
	}
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$inserted = $wpdb->insert( $tables['collaborations'], array( 'listing_id' => $listing_id, 'requester_id' => get_current_user_id(), 'provider_id' => $provider_id, 'status' => 'pending', 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%s', '%s' ) );
	nekoko_core_set_collaboration_notice( $inserted ? 'Zahtev za potvrdu saradnje je poslat pružaocu usluge.' : 'Zahtev za saradnju trenutno nije moguće poslati.', $inserted ? 'success' : 'error' );
	wp_safe_redirect( $redirect ); exit;
}
add_action( 'admin_post_nekoko_request_collaboration', 'nekoko_core_request_collaboration' );

function nekoko_core_incoming_collaboration_requests( $provider_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return $wpdb->get_results( $wpdb->prepare( "SELECT c.*, p.post_title, u.display_name FROM {$tables['collaborations']} c INNER JOIN {$wpdb->posts} p ON p.ID = c.listing_id INNER JOIN {$wpdb->users} u ON u.ID = c.requester_id WHERE c.provider_id = %d AND c.status = 'pending' ORDER BY c.created_at ASC", $provider_id ) );
}

function nekoko_core_confirm_collaboration() {
	if ( ! is_user_logged_in() ) { auth_redirect(); }
	$collaboration_id = absint( $_POST['collaboration_id'] ?? 0 );
	if ( ! $collaboration_id || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_confirm_collaboration_' . $collaboration_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$collaboration = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['collaborations']} WHERE id = %d", $collaboration_id ) );
	if ( ! $collaboration || (int) $collaboration->provider_id !== get_current_user_id() || 'pending' !== $collaboration->status ) {
		nekoko_core_set_collaboration_notice( 'Ovaj zahtev više nije dostupan.', 'error' );
		wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) ); exit;
	}
	$updated = $wpdb->update( $tables['collaborations'], array( 'status' => 'confirmed', 'confirmed_at' => current_time( 'mysql', true ) ), array( 'id' => $collaboration_id ), array( '%s', '%s' ), array( '%d' ) );
	nekoko_core_set_collaboration_notice( false !== $updated ? 'Saradnja je potvrđena. Korisnik sada može da ostavi recenziju za ovaj oglas.' : 'Potvrdu saradnje nije moguće sačuvati.', false !== $updated ? 'success' : 'error' );
	wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) ); exit;
}
add_action( 'admin_post_nekoko_confirm_collaboration', 'nekoko_core_confirm_collaboration' );

function nekoko_core_review_summary( $user_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$summary = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS count, AVG(rating) AS average_rating FROM {$tables['reviews']} WHERE reviewed_user_id = %d", $user_id ) );
	return array( 'count' => (int) ( $summary->count ?? 0 ), 'average' => isset( $summary->average_rating ) ? round( (float) $summary->average_rating, 1 ) : 0 );
}

function nekoko_core_listing_reviews( $listing_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return $wpdb->get_results( $wpdb->prepare( "SELECT r.*, u.display_name FROM {$tables['reviews']} r INNER JOIN {$wpdb->users} u ON u.ID = r.reviewer_id WHERE r.listing_id = %d ORDER BY r.created_at DESC", $listing_id ) );
}

function nekoko_core_review_exists( $collaboration_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['reviews']} WHERE collaboration_id = %d", $collaboration_id ) );
}

/**
 * Start a review from its listing. A confirmed collaboration opens the form;
 * otherwise this same action creates the one pending confirmation request.
 */
function nekoko_core_start_review() {
	if ( ! is_user_logged_in() ) { auth_redirect(); }
	$listing_id = absint( $_POST['listing_id'] ?? 0 );
	if ( ! $listing_id || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_start_review_' . $listing_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$listing = get_post( $listing_id );
	$redirect = $listing ? get_permalink( $listing ) : home_url( '/' );
	$provider_id = $listing ? (int) $listing->post_author : 0;
	if ( ! $listing || 'oglas' !== $listing->post_type || 'publish' !== $listing->post_status || ! $provider_id || $provider_id === get_current_user_id() ) {
		set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => 'Recenziju nije moguće ostaviti za ovaj oglas.', 'type' => 'error' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( $redirect . '#nekoko-listing-reviews-title' ); exit;
	}
	$collaboration = nekoko_core_collaboration( $listing_id, get_current_user_id(), $provider_id );
	if ( $collaboration && 'confirmed' === $collaboration->status ) {
		if ( nekoko_core_review_exists( $collaboration->id ) ) {
			set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => 'Već ste ostavili recenziju za ovu saradnju.', 'type' => 'error' ), MINUTE_IN_SECONDS );
			wp_safe_redirect( $redirect . '#nekoko-listing-reviews-title' ); exit;
		}
		wp_safe_redirect( add_query_arg( 'nekoko_review_form', '1', $redirect ) . '#nekoko-listing-reviews-title' ); exit;
	}
	if ( $collaboration ) {
		set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => 'Zahtev za potvrdu saradnje je već poslat i čeka potvrdu pružaoca usluge.', 'type' => 'success' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( $redirect . '#nekoko-listing-reviews-title' ); exit;
	}
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$inserted = $wpdb->insert( $tables['collaborations'], array( 'listing_id' => $listing_id, 'requester_id' => get_current_user_id(), 'provider_id' => $provider_id, 'status' => 'pending', 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%s', '%s' ) );
	set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => $inserted ? 'Zahtev za potvrdu saradnje je poslat. Kada ga pružalac usluge potvrdi, ovde ćete moći da ostavite recenziju.' : 'Zahtev za potvrdu saradnje trenutno nije moguće poslati.', 'type' => $inserted ? 'success' : 'error' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( $redirect . '#nekoko-listing-reviews-title' ); exit;
}
add_action( 'admin_post_nekoko_start_review', 'nekoko_core_start_review' );

function nekoko_core_submit_review() {
	if ( ! is_user_logged_in() ) { auth_redirect(); }
	$listing_id = absint( $_POST['listing_id'] ?? 0 );
	if ( ! $listing_id || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_submit_review_' . $listing_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$listing = get_post( $listing_id );
	$redirect = $listing ? get_permalink( $listing ) : home_url( '/' );
	$provider_id = $listing ? (int) $listing->post_author : 0;
	$collaboration = $listing && 'oglas' === $listing->post_type && 'publish' === $listing->post_status ? nekoko_core_confirmed_collaboration( $listing_id, get_current_user_id(), $provider_id ) : false;
	$rating = absint( $_POST['rating'] ?? 0 );
	$text = sanitize_textarea_field( wp_unslash( $_POST['review_text'] ?? '' ) );
	if ( ! $collaboration || $provider_id === get_current_user_id() || $rating < 1 || $rating > 5 || mb_strlen( $text ) < 10 || mb_strlen( $text ) > 2000 || nekoko_core_review_exists( $collaboration->id ) ) {
		set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => 'Recenzija nije sačuvana. Proverite ocenu i unesite najmanje 10 karaktera.', 'type' => 'error' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( $redirect ); exit;
	}
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$inserted = $wpdb->insert( $tables['reviews'], array( 'collaboration_id' => $collaboration->id, 'listing_id' => $listing_id, 'reviewer_id' => get_current_user_id(), 'reviewed_user_id' => $provider_id, 'rating' => $rating, 'review_text' => $text, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%d', '%d', '%s', '%s' ) );
	set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => $inserted ? 'Hvala, vaša recenzija je objavljena.' : 'Recenziju trenutno nije moguće sačuvati.', 'type' => $inserted ? 'success' : 'error' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( $redirect ); exit;
}
add_action( 'admin_post_nekoko_submit_review', 'nekoko_core_submit_review' );
