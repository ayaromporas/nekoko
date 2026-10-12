<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_delete_listing() {
	if ( empty( $_GET['nekoko_delete_listing'] ) ) { return; }
	$post_id = absint( $_GET['nekoko_delete_listing'] );
	if ( ! nekoko_core_current_user_can_manage_listing( $post_id ) || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'nekoko_delete_listing_' . $post_id ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	wp_trash_post( $post_id );
	wp_safe_redirect( nekoko_core_page_url( 'moji-oglasi' ) );
	exit;
}
add_action( 'template_redirect', 'nekoko_core_delete_listing', 7 );

function nekoko_core_dashboard_nav() {
	return '<nav class="nekoko-dashboard-nav" aria-label="Korisnički meni"><a href="' . esc_url( nekoko_core_page_url( 'moji-oglasi' ) ) . '">Moji oglasi</a><a href="' . esc_url( nekoko_core_page_url( 'sacuvani-oglasi' ) ) . '">Sačuvani oglasi</a><a href="' . esc_url( nekoko_core_page_url( 'postavite-oglas' ) ) . '">Postavite oglas</a><a href="' . esc_url( nekoko_core_page_url( 'moj-profil' ) ) . '">Moj profil</a><a href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Odjava</a></nav>';
}

function nekoko_core_shortcode_user_listings() { nekoko_core_require_login(); return nekoko_core_template( 'user-listings' ); }
add_shortcode( 'nekoko_user_listings', 'nekoko_core_shortcode_user_listings' );
