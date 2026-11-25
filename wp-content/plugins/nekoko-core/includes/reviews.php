<?php
/** Collaboration confirmation and listing-specific reviews. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_review_tables() {
	global $wpdb;
	return array(
		'collaborations' => $wpdb->prefix . 'nekoko_collaborations',
		'user_collaborations' => $wpdb->prefix . 'nekoko_user_collaborations',
		'reviews'        => $wpdb->prefix . 'nekoko_reviews',
	);
}

function nekoko_core_maybe_install_review_tables() {
	if ( '3' === get_option( 'nekoko_review_tables_version' ) ) { return; }
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
	dbDelta( "CREATE TABLE {$tables['user_collaborations']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		listing_id bigint(20) unsigned NOT NULL,
		requester_id bigint(20) unsigned NOT NULL,
		provider_id bigint(20) unsigned NOT NULL,
		status varchar(20) NOT NULL DEFAULT 'pending',
		created_at datetime NOT NULL,
		confirmed_at datetime NULL DEFAULT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY listing_collaboration (listing_id,requester_id,provider_id),
		KEY provider_status (provider_id,status),
		KEY requester_status (requester_id,status)
	) $charset;" );
	dbDelta( "CREATE TABLE {$tables['reviews']} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		collaboration_id bigint(20) unsigned NOT NULL,
		user_collaboration_id bigint(20) unsigned NULL DEFAULT NULL,
		listing_id bigint(20) unsigned NOT NULL,
		reviewer_id bigint(20) unsigned NOT NULL,
		reviewed_user_id bigint(20) unsigned NOT NULL,
		rating tinyint(3) unsigned NOT NULL,
		review_text longtext NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY collaboration_id (collaboration_id),
		KEY listing_id (listing_id),
		KEY reviewed_user_id (reviewed_user_id),
		KEY user_collaboration_id (user_collaboration_id),
		UNIQUE KEY listing_reviewer (listing_id,reviewer_id)
	) $charset;" );
	$index = $wpdb->get_var( "SHOW INDEX FROM {$tables['reviews']} WHERE Key_name = 'collaboration_id'" );
	if ( $index ) { $wpdb->query( "ALTER TABLE {$tables['reviews']} DROP INDEX collaboration_id" ); }
	$listing_reviewer_index = $wpdb->get_var( "SHOW INDEX FROM {$tables['reviews']} WHERE Key_name = 'listing_reviewer'" );
	if ( ! $listing_reviewer_index ) { $wpdb->query( "ALTER TABLE {$tables['reviews']} ADD UNIQUE KEY listing_reviewer (listing_id,reviewer_id)" ); }
	$old_collaborations = $wpdb->get_results( "SELECT * FROM {$tables['collaborations']}" );
	$has_listing_id = $wpdb->get_var( "SHOW COLUMNS FROM {$tables['user_collaborations']} LIKE 'listing_id'" );
	if ( ! $has_listing_id ) {
		$wpdb->query( "ALTER TABLE {$tables['user_collaborations']} ADD listing_id bigint(20) unsigned NOT NULL DEFAULT 0 AFTER id" );
	}
	$old_unique = $wpdb->get_var( "SHOW INDEX FROM {$tables['user_collaborations']} WHERE Key_name = 'user_collaboration'" );
	if ( $old_unique ) {
		$wpdb->query( "ALTER TABLE {$tables['user_collaborations']} DROP INDEX user_collaboration" );
	}
	$new_unique = $wpdb->get_var( "SHOW INDEX FROM {$tables['user_collaborations']} WHERE Key_name = 'listing_collaboration'" );
	if ( ! $new_unique ) {
		$wpdb->query( "ALTER TABLE {$tables['user_collaborations']} ADD UNIQUE KEY listing_collaboration (listing_id,requester_id,provider_id)" );
	}
	foreach ( $old_collaborations as $old_collaboration ) {
		$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$tables['user_collaborations']} (listing_id,requester_id,provider_id,status,created_at,confirmed_at) VALUES (%d,%d,%d,%s,%s,%s)", $old_collaboration->listing_id, $old_collaboration->requester_id, $old_collaboration->provider_id, $old_collaboration->status, $old_collaboration->created_at, $old_collaboration->confirmed_at ) );
	}
	$wpdb->query( "UPDATE {$tables['reviews']} r INNER JOIN {$tables['collaborations']} c ON c.id = r.collaboration_id INNER JOIN {$tables['user_collaborations']} uc ON uc.listing_id = c.listing_id AND uc.requester_id = c.requester_id AND uc.provider_id = c.provider_id SET r.user_collaboration_id = uc.id WHERE r.user_collaboration_id IS NULL" );
	update_option( 'nekoko_review_tables_version', '3', false );
}
add_action( 'init', 'nekoko_core_maybe_install_review_tables', 1 );

function nekoko_core_collaboration_notice_key( $user_id ) { return 'nekoko_collaboration_notice_' . absint( $user_id ); }
function nekoko_core_set_collaboration_notice( $message, $type = 'success', $user_id = 0 ) { set_transient( nekoko_core_collaboration_notice_key( $user_id ?: get_current_user_id() ), array( 'message' => $message, 'type' => $type ), MINUTE_IN_SECONDS ); }
function nekoko_core_take_collaboration_notice( $user_id = 0 ) { $key = nekoko_core_collaboration_notice_key( $user_id ?: get_current_user_id() ); $notice = get_transient( $key ); delete_transient( $key ); return is_array( $notice ) ? $notice : false; }

function nekoko_core_user_collaboration( $requester_id, $provider_id, $listing_id = 0 ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	if ( $listing_id ) {
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['user_collaborations']} WHERE listing_id = %d AND requester_id = %d AND provider_id = %d", $listing_id, $requester_id, $provider_id ) );
	}
	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['user_collaborations']} WHERE requester_id = %d AND provider_id = %d ORDER BY id DESC LIMIT 1", $requester_id, $provider_id ) );
}

/**
 * A collaboration is tied to the specific listing for which a review can be left.
 */
function nekoko_core_collaboration( $listing_id, $requester_id, $provider_id ) {
	return nekoko_core_user_collaboration( $requester_id, $provider_id, $listing_id );
}

function nekoko_core_confirmed_collaboration( $listing_id, $requester_id, $provider_id ) {
	$collaboration = nekoko_core_collaboration( $listing_id, $requester_id, $provider_id );
	return $collaboration && 'confirmed' === $collaboration->status ? $collaboration : false;
}

function nekoko_core_confirmed_collaboration_count( $user_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tables['user_collaborations']} WHERE status = 'confirmed' AND (requester_id = %d OR provider_id = %d)", $user_id, $user_id ) );
}

function nekoko_core_send_collaboration_email( $recipient_id, $subject, $message ) {
	$user = get_userdata( $recipient_id );
	if ( ! $user || ! is_email( $user->user_email ) ) { return false; }
	return wp_mail( $user->user_email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ) );
}

function nekoko_core_request_collaboration() {
	if ( ! is_user_logged_in() ) { auth_redirect(); }
	$listing_id = absint( $_POST['listing_id'] ?? 0 );
	$profile_id = absint( $_POST['profile_id'] ?? 0 );
	$requester_id = absint( $_POST['requester_id'] ?? 0 );
	$submitted_provider_id = absint( $_POST['provider_id'] ?? 0 );
	if ( ! $listing_id || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_request_collaboration_' . $listing_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$listing = get_post( $listing_id );
	$provider_id = $listing ? (int) $listing->post_author : 0;
	$redirect = $profile_id ? get_author_posts_url( $profile_id ) : get_permalink( $listing_id );
	if ( ! $listing || 'oglas' !== $listing->post_type || 'publish' !== $listing->post_status || ! $provider_id || ( $profile_id && $provider_id !== $profile_id ) || ( $requester_id && $requester_id !== get_current_user_id() ) || ( $submitted_provider_id && $submitted_provider_id !== $provider_id ) || $provider_id === get_current_user_id() ) {
		nekoko_core_set_collaboration_notice( 'Zahtev za saradnju nije moguće poslati za ovaj oglas.', 'error' );
		wp_safe_redirect( $redirect ); exit;
	}
	$existing = nekoko_core_collaboration( $listing_id, get_current_user_id(), $provider_id );
	if ( $existing ) {
		nekoko_core_set_collaboration_notice( 'Zahtev za potvrdu saradnje sa ovim korisnikom već postoji.', 'error' );
		wp_safe_redirect( $redirect ); exit;
	}
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$inserted = $wpdb->insert( $tables['user_collaborations'], array( 'listing_id' => $listing_id, 'requester_id' => get_current_user_id(), 'provider_id' => $provider_id, 'status' => 'pending', 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%s', '%s' ) );
	if ( $inserted ) {
		$requester = wp_get_current_user();
		nekoko_core_send_collaboration_email( $provider_id, 'Novi zahtev za potvrdu saradnje', sprintf( "%s želi da potvrdi saradnju za oglas \"%s\". Zahtev možete odobriti na stranici Moj profil.\n\n%s", $requester->display_name, get_the_title( $listing_id ), nekoko_core_page_url( 'moj-profil' ) ) );
	}
	$message = $inserted ? 'Zahtev za potvrdu saradnje je poslat pružaocu usluge.' : 'Zahtev za saradnju trenutno nije moguće poslati.';
	nekoko_core_set_collaboration_notice( $message, $inserted ? 'success' : 'error' );
	set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => $message, 'type' => $inserted ? 'success' : 'error' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( $redirect ); exit;
}
add_action( 'admin_post_nekoko_request_collaboration', 'nekoko_core_request_collaboration' );

function nekoko_core_incoming_collaboration_requests( $provider_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return $wpdb->get_results( $wpdb->prepare( "SELECT c.*, u.display_name, p.post_title FROM {$tables['user_collaborations']} c INNER JOIN {$wpdb->users} u ON u.ID = c.requester_id LEFT JOIN {$wpdb->posts} p ON p.ID = c.listing_id WHERE c.provider_id = %d AND c.status = 'pending' ORDER BY c.created_at ASC", $provider_id ) );
}

function nekoko_core_has_incoming_collaboration_requests( $provider_id ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['user_collaborations']} WHERE provider_id = %d AND status = 'pending' LIMIT 1", $provider_id ) );
}

function nekoko_core_confirm_collaboration() {
	if ( ! is_user_logged_in() ) { auth_redirect(); }
	$collaboration_id = absint( $_POST['collaboration_id'] ?? 0 );
	$profile_id = absint( $_POST['profile_id'] ?? 0 );
	if ( ! $collaboration_id || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_confirm_collaboration_' . $collaboration_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$collaboration = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['user_collaborations']} WHERE id = %d", $collaboration_id ) );
	$can_confirm = $collaboration && (int) $collaboration->provider_id === get_current_user_id();
	$can_confirm = $can_confirm || ( $collaboration && current_user_can( 'manage_options' ) );
	$redirect = $profile_id && current_user_can( 'manage_options' ) ? add_query_arg( 'nekoko_edit_user', $profile_id, nekoko_core_page_url( 'moj-profil' ) ) : nekoko_core_page_url( 'zahtevi-za-saradnju' );
	if ( ! $can_confirm || 'pending' !== $collaboration->status ) {
		nekoko_core_set_collaboration_notice( 'Ovaj zahtev više nije dostupan.', 'error' );
		wp_safe_redirect( $redirect ); exit;
	}
	$updated = $wpdb->update( $tables['user_collaborations'], array( 'status' => 'confirmed', 'confirmed_at' => current_time( 'mysql', true ) ), array( 'id' => $collaboration_id ), array( '%s', '%s' ), array( '%d' ) );
	if ( false !== $updated ) {
		nekoko_core_send_collaboration_email( (int) $collaboration->requester_id, 'Saradnja je potvrđena', sprintf( "Vaš zahtev za saradnju za oglas \"%s\" je potvrđen. Sada možete ostaviti recenziju na stranici oglasa.\n\n%s", get_the_title( (int) $collaboration->listing_id ), get_permalink( (int) $collaboration->listing_id ) ) );
	}
	nekoko_core_set_collaboration_notice( false !== $updated ? 'Saradnja je potvrđena. Korisnik sada može da ostavi recenziju za odgovarajući oglas.' : 'Potvrdu saradnje nije moguće sačuvati.', false !== $updated ? 'success' : 'error', (int) $collaboration->provider_id );
	wp_safe_redirect( $redirect ); exit;
}
add_action( 'admin_post_nekoko_confirm_collaboration', 'nekoko_core_confirm_collaboration' );

function nekoko_core_shortcode_collaboration_requests() {
	nekoko_core_require_login();
	return nekoko_core_template( 'collaboration-requests' );
}
add_shortcode( 'nekoko_collaboration_requests', 'nekoko_core_shortcode_collaboration_requests' );

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

function nekoko_core_user_reviews( $user_id, $limit = 0 ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$limit_sql = $limit ? $wpdb->prepare( ' LIMIT %d', absint( $limit ) ) : '';
	return $wpdb->get_results( $wpdb->prepare( "SELECT r.*, u.display_name, p.post_title FROM {$tables['reviews']} r INNER JOIN {$wpdb->users} u ON u.ID = r.reviewer_id INNER JOIN {$wpdb->posts} p ON p.ID = r.listing_id WHERE r.reviewed_user_id = %d ORDER BY r.created_at DESC" . $limit_sql, $user_id ) );
}

function nekoko_core_review_exists( $listing_id, $reviewer_id = 0 ) {
	global $wpdb;
	$tables = nekoko_core_review_tables();
	if ( $reviewer_id ) {
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['reviews']} WHERE listing_id = %d AND reviewer_id = %d", $listing_id, $reviewer_id ) );
	}
	return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['reviews']} WHERE collaboration_id = %d", $listing_id ) );
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
		if ( nekoko_core_review_exists( $listing_id, get_current_user_id() ) ) {
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
	$inserted = $wpdb->insert( $tables['user_collaborations'], array( 'listing_id' => $listing_id, 'requester_id' => get_current_user_id(), 'provider_id' => $provider_id, 'status' => 'pending', 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%s', '%s' ) );
	if ( $inserted ) {
		$requester = wp_get_current_user();
		nekoko_core_send_collaboration_email( $provider_id, 'Novi zahtev za potvrdu saradnje', sprintf( "%s želi da potvrdi saradnju za oglas \"%s\". Zahtev možete odobriti na stranici Moj profil.\n\n%s", $requester->display_name, get_the_title( $listing_id ), nekoko_core_page_url( 'moj-profil' ) ) );
	}
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
	if ( ! $collaboration || $provider_id === get_current_user_id() || $rating < 1 || $rating > 5 || mb_strlen( $text ) < 10 || mb_strlen( $text ) > 2000 || nekoko_core_review_exists( $listing_id, get_current_user_id() ) ) {
		set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => 'Recenzija nije sačuvana. Proverite ocenu i unesite najmanje 10 karaktera.', 'type' => 'error' ), MINUTE_IN_SECONDS );
		wp_safe_redirect( $redirect ); exit;
	}
	global $wpdb;
	$tables = nekoko_core_review_tables();
	$inserted = $wpdb->insert( $tables['reviews'], array( 'collaboration_id' => $collaboration->id, 'user_collaboration_id' => $collaboration->id, 'listing_id' => $listing_id, 'reviewer_id' => get_current_user_id(), 'reviewed_user_id' => $provider_id, 'rating' => $rating, 'review_text' => $text, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' ) );
	set_transient( 'nekoko_review_notice_' . get_current_user_id(), array( 'message' => $inserted ? 'Hvala, vaša recenzija je objavljena.' : 'Recenziju trenutno nije moguće sačuvati.', 'type' => $inserted ? 'success' : 'error' ), MINUTE_IN_SECONDS );
	wp_safe_redirect( $redirect ); exit;
}
add_action( 'admin_post_nekoko_submit_review', 'nekoko_core_submit_review' );
