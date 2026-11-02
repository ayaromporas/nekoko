<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SrbTransLatin accepts the stl_script cookie. The MU plugin used to inject
 * pismo=lat after WP Rocket had already checked the page-cache key, which
 * produced cache files that could never be reused on the next request.
 */
function nekoko_core_keep_latin_cache_key_stable() {
	unset( $_GET['pismo'], $_REQUEST['pismo'] );
	$_COOKIE['stl_script'] = 'lat';
}
add_action( 'plugins_loaded', 'nekoko_core_keep_latin_cache_key_stable', 2 );

/** Authentication forms contain expiring nonces and request-specific notices. */
function nekoko_core_auth_cache_exclusions( $uris ) {
	foreach ( array( 'registracija', 'prijava', 'potvrda-emaila', 'sve-poruke', 'poruke-sa-clanom' ) as $slug ) {
		$uris[] = wp_parse_url( home_url( '/' . $slug . '/' ), PHP_URL_PATH ) . '?';
	}
	return array_unique( $uris );
}
add_filter( 'rocket_cache_reject_uri', 'nekoko_core_auth_cache_exclusions' );

function nekoko_core_no_cache_auth_pages() {
	if ( ! is_page( array( 'registracija', 'prijava', 'potvrda-emaila', 'sve-poruke', 'poruke-sa-clanom' ) ) ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
}
add_action( 'template_redirect', 'nekoko_core_no_cache_auth_pages', 0 );
