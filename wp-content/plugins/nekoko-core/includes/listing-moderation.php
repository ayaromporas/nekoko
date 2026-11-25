<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', static function() {
	register_post_status( 'reject', array( 'label' => 'Odbijen', 'public' => false, 'protected' => true, 'internal' => false, 'exclude_from_search' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Odbijen <span class="count">(%s)</span>', 'Odbijeni <span class="count">(%s)</span>' ) ) );
} );

/** Explicit negative decision with a mandatory reason and administrator identity. */
function nekoko_core_reject_listing( $id, $reason ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) ) { return new WP_Error( 'forbidden', 'Nemate dozvolu za odbijanje oglasa.' ); }
	$post = get_post( $id );
	if ( ! $post || 'oglas' !== $post->post_type || 'trash' === $post->post_status ) { return new WP_Error( 'invalid_listing', 'Oglas nije dostupan.' ); }
	$reason = trim( sanitize_textarea_field( $reason ) );
	if ( '' === $reason || mb_strlen( $reason ) > 3000 ) { return new WP_Error( 'reason', 'Unesite razlog odbijanja (najviše 3000 znakova).' ); }
	$result = wp_update_post( array( 'ID' => $id, 'post_status' => 'reject' ), true );
	if ( is_wp_error( $result ) ) { return $result; }
	$decision = array( 'reason' => $reason, 'administrator' => get_current_user_id(), 'administrator_name' => wp_get_current_user()->display_name, 'date' => current_time( 'mysql', true ) );
	update_post_meta( $id, '_nekoko_rejection', $decision );
	add_post_meta( $id, '_nekoko_rejection_history', $decision );
	nekoko_core_send_listing_email( $id, 'rejected', $reason );
	return $result;
}

function nekoko_core_rejection_admin_url( $id ) {
	return add_query_arg( array( 'page' => 'nekoko-reject-listing', 'listing_id' => absint( $id ) ), admin_url( 'admin.php' ) );
}
add_filter( 'post_row_actions', static function( $actions, $post ) {
	if ( 'oglas' === $post->post_type && 'trash' !== $post->post_status && current_user_can( 'manage_options' ) && current_user_can( 'edit_post', $post->ID ) ) {
		$actions['nekoko_reject'] = '<a href="' . esc_url( nekoko_core_rejection_admin_url( $post->ID ) ) . '">Odbij oglas</a>';
	}
	return $actions;
}, 10, 2 );

add_filter( 'display_post_states', static function( $states, $post ) {
	if ( 'oglas' === $post->post_type && 'reject' === $post->post_status ) { $states['reject'] = 'Odbijen'; }
	return $states;
}, 10, 2 );

/** Keep the negative decision discoverable even before the first rejection. */
add_filter( 'views_edit-oglas', static function( $views ) {
	if ( ! current_user_can( 'manage_options' ) ) { return $views; }
	$count = wp_count_posts( 'oglas' );
	$active = 'reject' === ( $_GET['post_status'] ?? '' );
	$views['reject'] = '<a href="' . esc_url( admin_url( 'edit.php?post_type=oglas&post_status=reject' ) ) . '"' . ( $active ? ' class="current" aria-current="page"' : '' ) . '>Odbijeni <span class="count">(' . absint( $count->reject ?? 0 ) . ')</span></a>';
	return $views;
} );

add_action( 'admin_menu', static function() {
	add_submenu_page( null, 'Odbij oglas', 'Odbij oglas', 'manage_options', 'nekoko-reject-listing', 'nekoko_core_render_rejection_admin' );
} );

add_action( 'admin_init', static function() {
	if ( empty( $_POST['nekoko_reject_listing'] ) ) { return; }
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nemate dozvolu.', '', array( 'response' => 403 ) ); }
	$id = absint( $_POST['listing_id'] ?? 0 );
	check_admin_referer( 'nekoko_reject_listing_' . $id );
	$result = nekoko_core_reject_listing( $id, wp_unslash( $_POST['reason'] ?? '' ) );
	if ( is_wp_error( $result ) ) { $GLOBALS['nekoko_rejection_error'] = $result->get_error_message(); return; }
	wp_safe_redirect( add_query_arg( array( 'post_type' => 'oglas', 'nekoko_rejected' => 1 ), admin_url( 'edit.php' ) ) ); exit;
} );

/** The last decision and its history remain available after resubmission/approval. */
function nekoko_core_rejection_history( $id ) {
    $history = array_values( array_filter( get_post_meta( $id, '_nekoko_rejection_history', false ), static function( $decision ) { return is_array( $decision ) && isset( $decision['reason'] ); } ) );
    $latest = get_post_meta( $id, '_nekoko_rejection', true );
    if ( ! $history && is_array( $latest ) && isset( $latest['reason'] ) ) { $history[] = $latest; }
    return array_reverse( $history );
}
function nekoko_core_rejection_administrator( $decision ) {
    if ( ! empty( $decision['administrator_name'] ) ) { return $decision['administrator_name'] . ' (#' . absint( $decision['administrator'] ?? 0 ) . ')'; }
    $id = absint( $decision['administrator'] ?? 0 );
    $user = get_userdata( $id );
    return ( $user ? $user->display_name : 'Obrisan administrator' ) . ' (#' . $id . ')';
}
function nekoko_core_rejection_timestamp( $decision ) {
    $timestamp = ! empty( $decision['date'] ) ? strtotime( $decision['date'] . ' UTC' ) : false;
    return $timestamp ? wp_date( 'd.m.Y. H:i:s T', $timestamp ) : 'Vreme nije zabeleženo';
}
function nekoko_core_render_rejection_admin() {
    $id = absint( $_GET['listing_id'] ?? 0 );
    if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'edit_post', $id ) || 'oglas' !== get_post_type( $id ) ) { wp_die( 'Nemate dozvolu za ovaj oglas.', '', array( 'response' => 403 ) ); }
    $history = nekoko_core_rejection_history( $id );
    $latest = $history ? $history[0] : array();
    $reason = isset( $_POST['reason'] ) ? wp_unslash( $_POST['reason'] ) : ( $latest['reason'] ?? '' );
    echo '<div class="wrap"><h1>Odbijanje oglasa: ' . esc_html( get_the_title( $id ) ) . '</h1>';
    if ( ! empty( $GLOBALS['nekoko_rejection_error'] ) ) { echo '<div class="notice notice-error"><p>' . esc_html( $GLOBALS['nekoko_rejection_error'] ) . '</p></div>'; }
    if ( $history ) {
        echo '<h2>Sačuvani razlozi odbijanja</h2><p>Istorija ostaje sačuvana i kada se oglas ponovo pošalje na pregled ili naknadno odobri. Najnovija odluka je na vrhu.</p><table class="widefat striped"><thead><tr><th>Datum i vreme</th><th>Odbio administrator</th><th>Razlog odbijanja</th></tr></thead><tbody>';
        foreach ( $history as $decision ) {
            echo '<tr><td>' . esc_html( nekoko_core_rejection_timestamp( $decision ) ) . '</td><td>' . esc_html( nekoko_core_rejection_administrator( $decision ) ) . '</td><td style="white-space:normal;overflow-wrap:anywhere">' . nl2br( esc_html( $decision['reason'] ) ) . '</td></tr>';
        }
        echo '</tbody></table><h2>Sačuvaj novu odluku o odbijanju</h2>';
    }
    echo '<p>Oglas neće biti javno vidljiv. Vlasnik će videti razlog i moći će da izmeni oglas i pošalje ga ponovo na pregled. Čuvanje dodaje novu odluku u istoriju.</p><form method="post">';
    wp_nonce_field( 'nekoko_reject_listing_' . $id );
    echo '<input type="hidden" name="listing_id" value="' . absint( $id ) . '"><p><label for="rejection-reason">Razlog odbijanja (obavezno)</label></p><textarea id="rejection-reason" name="reason" class="large-text" rows="6" maxlength="3000" required>' . esc_textarea( $reason ) . '</textarea><p><button name="nekoko_reject_listing" value="1" class="button button-primary">Sačuvaj razlog i odbij oglas</button> <a href="' . esc_url( admin_url( 'edit.php?post_type=oglas' ) ) . '">Nazad na oglase</a></p></form></div>';
}
add_action( 'add_meta_boxes_oglas', static function() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	add_meta_box( 'nekoko-listing-moderation', 'Odobravanje oglasa', static function( $post ) {
		echo '<p><a class="button" href="' . esc_url( nekoko_core_rejection_admin_url( $post->ID ) ) . '">Odbij oglas</a></p>';
		$history = nekoko_core_rejection_history( $post->ID );
		foreach ( $history as $decision ) {
			$admin = get_userdata( $decision['administrator'] );
			echo '<p><strong>' . esc_html( nekoko_core_rejection_timestamp( $decision ) ) . '</strong> · ' . esc_html( nekoko_core_rejection_administrator( $decision ) ) . '<br>' . nl2br( esc_html( $decision['reason'] ) ) . '</p>';
		}
	}, 'oglas', 'side' );
} );

add_action( 'admin_notices', static function() {
	if ( isset( $_GET['nekoko_rejected'] ) && current_user_can( 'manage_options' ) ) { echo '<div class="notice notice-success is-dismissible"><p>Oglas je odbijen. Razlog je vidljiv vlasniku oglasa.</p></div>'; }
} );
