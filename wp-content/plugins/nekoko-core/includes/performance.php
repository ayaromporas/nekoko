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
