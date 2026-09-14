<?php
/**
 * Plugin Name: Nekoko Core
 * Description: Osnovni model oglasa, taksonomije i administrativna polja portala Nekoko.
 * Version: 2.1.0
 * Author: Nekoko
 * Text Domain: nekoko-core
 * Requires at least: 6.3
 * Requires PHP: 7.4
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'NEKOKO_CORE_FILE', __FILE__ );
require_once __DIR__ . '/includes/content-model.php';
require_once __DIR__ . '/includes/classification.php';
require_once __DIR__ . '/includes/classification-admin.php';
require_once __DIR__ . '/includes/category-media.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/header-fields.php';
require_once __DIR__ . '/includes/home-hero-block.php';
require_once __DIR__ . '/includes/search-block.php';
require_once __DIR__ . '/includes/featured-offers-block.php';
require_once __DIR__ . '/includes/top-offers-block.php';
require_once __DIR__ . '/includes/form-helpers.php';
require_once __DIR__ . '/includes/frontend-fields.php';
require_once __DIR__ . '/includes/form-pages.php';
require_once __DIR__ . '/includes/email-verification.php';
require_once __DIR__ . '/includes/email-verification-admin.php';
require_once __DIR__ . '/includes/authentication.php';
require_once __DIR__ . '/includes/registration.php';
require_once __DIR__ . '/includes/listing-permissions.php';
require_once __DIR__ . '/includes/listing-form.php';
require_once __DIR__ . '/includes/user-dashboard.php';
require_once __DIR__ . '/includes/profile.php';

function nekoko_core_activate() {
	nekoko_core_register_model();
	nekoko_core_register_oglasivac_role();
	nekoko_core_maybe_setup_frontend_pages();
	// Soft flush: retain the existing Apache rules and other content URLs.
	flush_rewrite_rules( false );
}
register_activation_hook( __FILE__, 'nekoko_core_activate' );

function nekoko_core_deactivate() {
	global $wp_rewrite;
	foreach ( array_keys( nekoko_core_rewrite_rules() ) as $regex ) {
		unset( $wp_rewrite->extra_rules_top[ $regex ] );
	}
	foreach ( array( 'kategorija_oglasa', 'oglas_tag' ) as $taxonomy ) {
		unregister_taxonomy( $taxonomy );
	}
	unregister_post_type( 'oglas' );
	flush_rewrite_rules( false );
}
register_deactivation_hook( __FILE__, 'nekoko_core_deactivate' );
