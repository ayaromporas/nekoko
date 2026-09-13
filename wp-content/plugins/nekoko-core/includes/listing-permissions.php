<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_listing_access() {
	if ( ! is_page( 'postavite-oglas' ) ) { return; }
	if ( ! is_user_logged_in() ) { nekoko_core_require_login( home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? 'postavite-oglas' ) ) ); }
	if ( ! nekoko_core_is_verified_user() ) { wp_die( wp_kses_post( nekoko_core_form_notice( 'Potvrdite email adresu pre postavljanja oglasa. <a href="' . esc_url( nekoko_core_page_url( 'prijava' ) ) . '">Zatražite novi link</a>.', 'error' ) ), 'Potvrdite email' ); }
}
add_action( 'template_redirect', 'nekoko_core_listing_access', 5 );

function nekoko_core_limit_media_library_to_owner( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || current_user_can( 'manage_options' ) ) { return; }
	if ( 'attachment' === $query->get( 'post_type' ) ) { $query->set( 'author', get_current_user_id() ); }
}
add_action( 'pre_get_posts', 'nekoko_core_limit_media_library_to_owner' );
function nekoko_core_limit_media_ajax_to_owner( $query ) { if ( ! current_user_can( 'manage_options' ) ) { $query['author'] = get_current_user_id(); } return $query; }
add_filter( 'ajax_query_attachments_args', 'nekoko_core_limit_media_ajax_to_owner' );
