<?php
/** Private member conversations. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_messages_setup() {
	if ( '2' === get_option( 'nekoko_messages_version' ) ) { return; }
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table = $wpdb->prefix . 'nekoko_messages';
	dbDelta( "CREATE TABLE $table (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		sender_id bigint(20) unsigned NOT NULL,
		recipient_id bigint(20) unsigned NOT NULL,
		listing_id bigint(20) unsigned NOT NULL DEFAULT 0,
		listing_title text NOT NULL,
		body text NOT NULL,
		created_at datetime NOT NULL,
		read_at datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY conversation (sender_id,recipient_id,id),
		KEY listing_conversation (sender_id,recipient_id,listing_id,id),
		KEY inbox (recipient_id,read_at,id)
	) " . $wpdb->get_charset_collate() . ';' );
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) !== $table ) { return; }
	foreach ( array( 'sve-poruke' => 'Sve poruke', 'poruke-sa-clanom' => 'Poruke sa članom' ) as $slug => $title ) {
		if ( ! get_page_by_path( $slug ) ) { wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title ) ); }
	}
	if ( ! $wpdb->get_var( "SHOW COLUMNS FROM $table LIKE 'listing_id'" ) ) { return; }
	update_option( 'nekoko_messages_version', '2', false );
}
add_action( 'init', 'nekoko_core_messages_setup', 21 );

/** Older conversation links used the reserved post-type query variable. */
function nekoko_core_messages_legacy_request( $vars ) {
	if ( isset( $vars['pagename'] ) && 'poruke-sa-clanom' === trim( $vars['pagename'], '/' ) && isset( $_GET['oglas'] ) ) {
		if ( ! isset( $_GET['oglas_id'] ) ) { $_GET['oglas_id'] = absint( $_GET['oglas'] ); }
		unset( $vars['oglas'], $vars['post_type'], $vars['name'] );
	}
	return $vars;
}
add_filter( 'request', 'nekoko_core_messages_legacy_request' );

function nekoko_core_conversation_url( $member, $listing = 0 ) {
	$url = add_query_arg( 'clan', absint( $member ), home_url( '/poruke-sa-clanom/' ) );
	if ( $listing ) { $url = add_query_arg( 'oglas_id', absint( $listing ), $url ); }
	return is_user_logged_in() ? $url : add_query_arg( 'redirect_to', $url, home_url( '/prijava/' ) );
}

function nekoko_core_unread_messages() {
	if ( ! get_current_user_id() ) { return 0; }
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}nekoko_messages WHERE recipient_id=%d AND read_at IS NULL", get_current_user_id() ) );
}

function nekoko_core_send_member_message( $recipient, $body, $listing = 0 ) {
	global $wpdb;
	$sender = get_current_user_id();
	$listing = absint( $listing );
	$body = sanitize_textarea_field( $body );
	if ( ! $sender || $sender === $recipient || ! get_userdata( $recipient ) || '' === trim( $body ) || mb_strlen( $body ) > 5000 ) {
		return new WP_Error( 'invalid_message', 'Izaberite člana i unesite poruku do 5000 znakova.' );
	}
	if ( ! nekoko_core_message_listing_allowed( $listing, $sender, $recipient ) ) {
		return new WP_Error( 'invalid_listing', 'Oglas nije dostupan za ovaj razgovor.' );
	}
	$result = $wpdb->insert( $wpdb->prefix . 'nekoko_messages', array( 'sender_id' => $sender, 'recipient_id' => $recipient, 'listing_id' => $listing, 'listing_title' => $listing ? get_the_title( $listing ) : '', 'body' => $body, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%s', '%s', '%s' ) );
	return $result ? (int) $wpdb->insert_id : new WP_Error( 'send_failed', 'Slanje nije uspelo. Pokušajte ponovo.' );
}

/** An existing private thread remains usable if its listing is removed. */
function nekoko_core_message_listing_allowed( $listing, $sender, $recipient ) {
	if ( ! $listing ) { return true; }
	global $wpdb;
	if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}nekoko_messages WHERE listing_id=%d AND ((sender_id=%d AND recipient_id=%d) OR (sender_id=%d AND recipient_id=%d)) LIMIT 1", $listing, $sender, $recipient, $recipient, $sender ) ) ) { return true; }
	$post = get_post( $listing );
	return $post && 'oglas' === $post->post_type && 'publish' === $post->post_status && in_array( (int) $post->post_author, array( (int) $sender, (int) $recipient ), true );
}

function nekoko_core_messages_request() {
	if ( ! is_page( array( 'sve-poruke', 'poruke-sa-clanom' ) ) ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
	if ( ! is_user_logged_in() ) {
		$target = is_page( 'poruke-sa-clanom' ) ? add_query_arg( array( 'clan' => absint( $_GET['clan'] ?? 0 ), 'oglas_id' => absint( $_GET['oglas_id'] ?? 0 ) ), home_url( '/poruke-sa-clanom/' ) ) : home_url( '/sve-poruke/' );
		wp_safe_redirect( add_query_arg( 'redirect_to', $target, home_url( '/prijava/' ) ) ); exit;
	}
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['nekoko_member_message'] ) ) {
		check_admin_referer( 'nekoko_member_message' );
		$recipient = absint( $_GET['clan'] ?? 0 );
		$listing = absint( $_POST['listing_id'] ?? 0 );
		$result = nekoko_core_send_member_message( $recipient, wp_unslash( $_POST['body'] ?? '' ), $listing );
		if ( is_wp_error( $result ) ) { $GLOBALS['nekoko_message_error'] = $result->get_error_message(); }
		else { wp_safe_redirect( nekoko_core_conversation_url( $recipient, $listing ) . '#razgovor-' . $listing ); exit; }
	}
}
add_action( 'template_redirect', 'nekoko_core_messages_request' );

function nekoko_core_messages_template( $template ) {
	return is_page( array( 'sve-poruke', 'poruke-sa-clanom' ) ) ? dirname( __DIR__ ) . '/templates/messages.php' : $template;
}
add_filter( 'template_include', 'nekoko_core_messages_template' );

function nekoko_core_messages_assets() {
	wp_enqueue_style( 'nekoko-messages', plugins_url( 'assets/messages.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( __DIR__ ) . '/assets/messages.css' ) );
	if ( is_user_logged_in() ) {
		wp_enqueue_script( 'nekoko-messages-refresh', plugins_url( 'assets/js/messages-refresh.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( __DIR__ ) . '/assets/js/messages-refresh.js' ), true );
		wp_localize_script( 'nekoko-messages-refresh', 'nekokoMessagesRefresh', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nekoko_messages_refresh' ), 'state' => nekoko_core_messages_state() ) );
	}
}
add_action( 'wp_enqueue_scripts', 'nekoko_core_messages_assets' );

/** Only the current member's message activity; checking never marks messages read. */
function nekoko_core_messages_state() {
	global $wpdb;
	$me = get_current_user_id();
	if ( ! $me ) { return ''; }
	$state = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS total, COALESCE(MAX(id),0) AS latest, COUNT(read_at) AS seen FROM {$wpdb->prefix}nekoko_messages WHERE sender_id=%d OR recipient_id=%d", $me, $me ), ARRAY_A );
	return md5( wp_json_encode( $state ) );
}

function nekoko_core_ajax_messages_state() {
	check_ajax_referer( 'nekoko_messages_refresh', 'nonce' );
	nocache_headers();
	wp_send_json_success( array( 'state' => nekoko_core_messages_state(), 'unread' => nekoko_core_unread_messages() ) );
}
add_action( 'wp_ajax_nekoko_messages_state', 'nekoko_core_ajax_messages_state' );
