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
	foreach ( array( 'registracija', 'prijava', 'zaboravljena-lozinka', 'nova-lozinka', 'odjava', 'potvrda-zahteva', 'potvrda-emaila', 'sve-poruke', 'poruke-sa-clanom', 'moj-profil', 'postavite-oglas' ) as $slug ) {
		$uris[] = wp_parse_url( home_url( '/' . $slug . '/' ), PHP_URL_PATH ) . '?';
	}
	return array_unique( $uris );
}
add_filter( 'rocket_cache_reject_uri', 'nekoko_core_auth_cache_exclusions' );

function nekoko_core_no_cache_auth_pages() {
	if ( ! is_page( array( 'registracija', 'prijava', 'zaboravljena-lozinka', 'nova-lozinka', 'odjava', 'potvrda-zahteva', 'potvrda-emaila', 'sve-poruke', 'poruke-sa-clanom', 'moj-profil', 'postavite-oglas' ) ) ) { return; }
	if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
	nocache_headers();
}
add_action( 'template_redirect', 'nekoko_core_no_cache_auth_pages', 0 );

/** File-only deployments must invalidate cached header markup and bundled assets. */
function nekoko_core_refresh_deployed_header() {
	$files = array( get_template_directory() . '/header.php', get_stylesheet_directory() . '/style.css', get_template_directory() . '/assets/js/theme.js', dirname( __DIR__ ) . '/assets/js/forms.js', dirname( __DIR__ ) . '/assets/css/forms.css', __FILE__ );
	$hashes = array();
	foreach ( $files as $file ) {
		if ( ! is_readable( $file ) ) { return false; }
		$hashes[] = hash_file( 'sha256', $file );
	}
	$revision = hash( 'sha256', implode( ':', $hashes ) );
	if ( get_option( 'nekoko_deployed_header_revision' ) === $revision ) { return false; }
	if ( function_exists( 'rocket_generate_config_file' ) ) { rocket_generate_config_file(); }
	if ( function_exists( 'rocket_clean_minify' ) ) { rocket_clean_minify( array( 'css', 'js' ) ); }
	if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
	update_option( 'nekoko_deployed_header_revision', $revision, false );
	return true;
}
add_action( 'init', 'nekoko_core_refresh_deployed_header', 99 );
