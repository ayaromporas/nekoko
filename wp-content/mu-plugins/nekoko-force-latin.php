<?php
/**
 * Plugin Name: Nekoko Force Latin
 * Description: Keeps the Nekoko frontend and WordPress administration in the Latin script.
 * Version: 1.2.0
 *
 * @package Nekoko
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force SrbTransLatin to use Latin even when a visitor has an old Cyrillic
 * cookie or manually adds the Cyrillic script parameter to the URL.
 */
function nekoko_force_latin_script() {
	$_GET['pismo']         = 'lat';
	$_REQUEST['pismo']     = 'lat';
	$_COOKIE['stl_script'] = 'lat';
}
add_action( 'plugins_loaded', 'nekoko_force_latin_script', 1 );

/**
 * Convert an output buffer through SrbTransLatin without passing the output
 * buffering phase as the plugin's optional transliteration mode argument.
 *
 * @param string $output Buffered administration output.
 * @return string
 */
function nekoko_transliterate_admin_output( $output ) {
	if ( ! function_exists( 'STL' ) || empty( STL()->engine ) ) {
		return $output;
	}

	if ( wp_doing_ajax() ) {
		return STL()->engine->ajax_buffer_end( $output );
	}

	return STL()->engine->convert_to_latin( $output );
}

/**
 * Transliterate regular wp-admin pages. SrbTransLatin handles AJAX itself.
 */
function nekoko_start_admin_latin_buffer() {
	if ( wp_doing_cron() ) {
		return;
	}

	ob_start( 'nekoko_transliterate_admin_output' );
}
add_action( 'admin_init', 'nekoko_start_admin_latin_buffer', -9998 );
add_action( 'login_init', 'nekoko_start_admin_latin_buffer', -9998 );

/**
 * Recursively transliterate strings returned by WordPress APIs.
 *
 * @param mixed $value Value to transliterate.
 * @return mixed
 */
function nekoko_transliterate_value_to_latin( $value ) {
	if ( is_string( $value ) ) {
		return function_exists( 'STL' ) && ! empty( STL()->engine )
			? STL()->engine->convert_to_latin( $value )
			: $value;
	}

	if ( is_array( $value ) ) {
		return array_map( 'nekoko_transliterate_value_to_latin', $value );
	}

	if ( is_object( $value ) ) {
		foreach ( get_object_vars( $value ) as $property => $property_value ) {
			$value->{$property} = nekoko_transliterate_value_to_latin( $property_value );
		}
	}

	return $value;
}

/**
 * Ensure Gutenberg and other REST clients receive Latin-script JSON.
 *
 * @param mixed $result REST response data.
 * @return mixed
 */
function nekoko_transliterate_rest_response( $result ) {
	return nekoko_transliterate_value_to_latin( $result );
}
add_filter( 'rest_pre_echo_response', 'nekoko_transliterate_rest_response', PHP_INT_MAX );

/**
 * Permanently save core post fields in Latin script.
 *
 * @param array $data Slashed and sanitized post data.
 * @return array
 */
function nekoko_save_post_content_in_latin( $data ) {
	foreach ( array( 'post_title', 'post_excerpt', 'post_content', 'post_name' ) as $field ) {
		if ( isset( $data[ $field ] ) ) {
			$data[ $field ] = nekoko_transliterate_value_to_latin( $data[ $field ] );
		}
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'nekoko_save_post_content_in_latin', PHP_INT_MAX );

/**
 * Load live Latin-script enforcement in the block editor.
 */
function nekoko_enqueue_gutenberg_latin_script() {
	$script_path = __DIR__ . '/assets/nekoko-gutenberg-latin.js';

	wp_enqueue_script(
		'nekoko-gutenberg-latin',
		plugins_url( 'assets/nekoko-gutenberg-latin.js', __FILE__ ),
		array(),
		file_exists( $script_path ) ? (string) filemtime( $script_path ) : '1.0.0',
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'nekoko_enqueue_gutenberg_latin_script' );
