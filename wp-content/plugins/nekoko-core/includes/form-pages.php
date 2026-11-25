<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_frontend_pages() {
	return array(
		'prijava' => array( 'title' => 'Prijava', 'content' => '[nekoko_login]' ),
		'registracija' => array( 'title' => 'Registracija', 'content' => '[nekoko_registration]' ),
		'potvrda-emaila' => array( 'title' => 'Potvrda emaila', 'content' => '[nekoko_verify_email]' ),
		'postavite-oglas' => array( 'title' => 'Postavite oglas', 'content' => '[nekoko_listing_form]' ),
		'moji-oglasi' => array( 'title' => 'Moji oglasi', 'content' => '[nekoko_user_listings]' ),
		'sacuvani-oglasi' => array( 'title' => 'Sačuvani oglasi', 'content' => '[nekoko_saved_listings]' ),
		'moj-profil' => array( 'title' => 'Moj profil', 'content' => '[nekoko_profile_form]' ),
		'zahtevi-za-saradnju' => array( 'title' => 'Zahtevi za saradnju', 'content' => '[nekoko_collaboration_requests]' ),
		'podesavanje-naloga' => array( 'title' => 'Podešavanje naloga', 'content' => '[nekoko_account_settings]' ),
	);
}

function nekoko_core_ensure_collaboration_menu_item() {
	$menu = wp_get_nav_menu_object( 'Moj nalog' );
	if ( ! $menu ) { return; }
	foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $item ) {
		if ( 'zahtevi-za-saradnju' === sanitize_title( $item->title ) || untrailingslashit( $item->url ) === untrailingslashit( nekoko_core_page_url( 'zahtevi-za-saradnju' ) ) ) {
			return;
		}
	}
	wp_update_nav_menu_item( $menu->term_id, 0, array(
		'menu-item-title'  => 'Zahtevi za saradnju',
		'menu-item-url'    => nekoko_core_page_url( 'zahtevi-za-saradnju' ),
		'menu-item-status' => 'publish',
		'menu-item-type'   => 'custom',
	) );
}

function nekoko_core_maybe_setup_frontend_pages() {
	$version = '5';
	if ( get_option( 'nekoko_frontend_pages_version' ) === $version ) { return; }
	foreach ( nekoko_core_frontend_pages() as $slug => $page ) {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $existing ) { wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $page['title'], 'post_name' => $slug, 'post_content' => $page['content'] ) ); }
		elseif ( false === strpos( $existing->post_content, $page['content'] ) ) { wp_update_post( array( 'ID' => $existing->ID, 'post_content' => rtrim( $existing->post_content ) . "\n\n" . $page['content'] ) ); }
	}
	nekoko_core_ensure_collaboration_menu_item();
	update_option( 'nekoko_frontend_pages_version', $version, false );
}
add_action( 'init', 'nekoko_core_maybe_setup_frontend_pages', 20 );

function nekoko_core_template( $name, $args = array() ) {
	$file = dirname( __DIR__ ) . '/templates/' . $name . '.php';
	if ( ! is_readable( $file ) ) { return ''; }
	ob_start(); extract( $args, EXTR_SKIP ); include $file; return ob_get_clean();
}

function nekoko_core_frontend_assets() {
	if ( is_admin() ) { return; }
	wp_enqueue_script( 'nekoko-upload-limits', plugins_url( 'assets/js/upload-limits.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/upload-limits.js' ), true );
	wp_localize_script( 'nekoko-upload-limits', 'nekokoUploadLimits', array( 'maxBytes' => 4 * MB_IN_BYTES, 'postBytes' => wp_convert_hr_to_bytes( ini_get( 'post_max_size' ) ), 'maxFiles' => (int) ini_get( 'max_file_uploads' ) ) );
	wp_enqueue_style( 'nekoko-image-crop', plugins_url( 'assets/css/image-crop.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/css/image-crop.css' ) );
	wp_enqueue_script( 'nekoko-image-crop', plugins_url( 'assets/js/image-crop.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/image-crop.js' ), true );
	wp_enqueue_style( 'nekoko-core-forms', plugins_url( 'assets/css/forms.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/css/forms.css' ) );
	wp_enqueue_style( 'nekoko-test-listing-badges', plugins_url( 'assets/test-listings.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/test-listings.css' ) );
	wp_enqueue_script( 'nekoko-core-forms', plugins_url( 'assets/js/forms.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/forms.js' ), true );
	if ( is_page( 'sacuvani-oglasi' ) ) {
		wp_enqueue_style( 'nekoko-saved-listings', plugins_url( 'assets/saved-listings.css', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/saved-listings.css' ) );
		wp_enqueue_script( 'nekoko-saved-listings-script', plugins_url( 'assets/js/saved-listings.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/saved-listings.js' ), true );
		wp_localize_script( 'nekoko-saved-listings-script', 'nekokoSavedListings', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nekoko_listing_interaction' ) ) );
	}
}
add_action( 'wp_enqueue_scripts', 'nekoko_core_frontend_assets' );

function nekoko_core_shortcode_login() { return nekoko_core_template( 'login-form' ); }
function nekoko_core_shortcode_registration() { return nekoko_core_template( 'registration-form' ); }
function nekoko_core_shortcode_verify() { return nekoko_core_template( 'verify-email' ); }
add_shortcode( 'nekoko_login', 'nekoko_core_shortcode_login' );
add_shortcode( 'nekoko_registration', 'nekoko_core_shortcode_registration' );
add_shortcode( 'nekoko_verify_email', 'nekoko_core_shortcode_verify' );

/** Searchable multi-selects are only needed on the member listing form. */
function nekoko_core_listing_select2_assets() {
    if ( ! is_page( 'postavite-oglas' ) ) { return; }
    wp_enqueue_style( 'nekoko-select2', plugins_url( 'assets/vendor/select2/select2.min.css', NEKOKO_CORE_FILE ), array(), '4.0.13' );
    wp_enqueue_style( 'nekoko-listing-select2', plugins_url( 'assets/css/listing-select2.css', NEKOKO_CORE_FILE ), array( 'nekoko-select2', 'nekoko-core-forms' ), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/css/listing-select2.css' ) );
    wp_enqueue_script( 'nekoko-select2', plugins_url( 'assets/vendor/select2/select2.full.min.js', NEKOKO_CORE_FILE ), array( 'jquery' ), '4.0.13', true );
    wp_enqueue_script( 'nekoko-listing-select2', plugins_url( 'assets/js/listing-select2.js', NEKOKO_CORE_FILE ), array( 'jquery', 'nekoko-select2' ), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/listing-select2.js' ), true );
}
add_action( 'wp_enqueue_scripts', 'nekoko_core_listing_select2_assets', 21 );
