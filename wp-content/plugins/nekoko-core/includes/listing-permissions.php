<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_listing_access() {
	if ( ! is_page( 'postavite-oglas' ) ) { return; }
	if ( ! is_user_logged_in() ) { nekoko_core_require_login( home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? 'postavite-oglas' ) ) ); }
	if ( ! nekoko_core_is_verified_user() ) { wp_die( wp_kses_post( nekoko_core_form_notice( 'Potvrdite email adresu pre postavljanja oglasa. <a href="' . esc_url( nekoko_core_page_url( 'prijava' ) ) . '">Zatražite novi link</a>.', 'error' ) ), 'Potvrdite email' ); }
}
add_action( 'template_redirect', 'nekoko_core_listing_access', 5 );

/**
 * Pending and draft listings are never part of public listing queries.
 * Their author and site administrators may still open the individual listing.
 */
function nekoko_core_listing_from_public_query( $query ) {
	$listing_id = absint( $query->get( 'p' ) );
	if ( $listing_id ) {
		$listing = get_post( $listing_id );
		return $listing && 'oglas' === $listing->post_type ? $listing : null;
	}

	$slug = $query->get( 'oglas' );
	if ( ! $slug && 'oglas' === $query->get( 'post_type' ) ) {
		$slug = $query->get( 'name' );
	}
	return $slug ? get_page_by_path( sanitize_title( $slug ), OBJECT, 'oglas' ) : null;
}

function nekoko_core_limit_public_listing_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) { return; }

	$listing = nekoko_core_listing_from_public_query( $query );
	if ( $listing && 'publish' !== $listing->post_status ) {
		if ( nekoko_core_current_user_can_manage_listing( $listing->ID ) ) {
			$query->set( 'post_status', array( 'publish', $listing->post_status ) );
		} else {
			$query->set( 'post_status', 'publish' );
		}
		return;
	}

	$listing_archive = 'oglas' === $query->get( 'post_type' )
		|| $query->get( 'kategorija_oglasa' )
		|| $query->get( 'oglas_tag' );
	if ( $listing_archive ) {
		$query->set( 'post_status', 'publish' );
	}
}
add_action( 'pre_get_posts', 'nekoko_core_limit_public_listing_queries', 5 );

function nekoko_core_protect_unapproved_listing() {
	if ( ! is_singular( 'oglas' ) ) { return; }
	$listing = get_queried_object();
	if ( ! $listing || 'publish' === $listing->post_status || nekoko_core_current_user_can_manage_listing( $listing->ID ) ) { return; }

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	exit;
}
add_action( 'template_redirect', 'nekoko_core_protect_unapproved_listing', 4 );

function nekoko_core_limit_media_library_to_owner( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || current_user_can( 'manage_options' ) ) { return; }
	if ( 'attachment' === $query->get( 'post_type' ) ) { $query->set( 'author', get_current_user_id() ); }
}
add_action( 'pre_get_posts', 'nekoko_core_limit_media_library_to_owner' );
function nekoko_core_limit_media_ajax_to_owner( $query ) { if ( ! current_user_can( 'manage_options' ) ) { $query['author'] = get_current_user_id(); } return $query; }
add_filter( 'ajax_query_attachments_args', 'nekoko_core_limit_media_ajax_to_owner' );
